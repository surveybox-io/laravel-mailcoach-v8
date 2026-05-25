<?php

namespace Spatie\Mailcoach\Domain\Shared\Models;

use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Spatie\Mailcoach\Database\Factories\SendFactory;
use Spatie\Mailcoach\Domain\Audience\Enums\SuppressionReason;
use Spatie\Mailcoach\Domain\Audience\Events\ComplaintRegisteredEvent;
use Spatie\Mailcoach\Domain\Audience\Events\SubscriberSuppressedEvent;
use Spatie\Mailcoach\Domain\Audience\Models\Subscriber;
use Spatie\Mailcoach\Domain\Content\Actions\StripUtmTagsFromUrlAction;
use Spatie\Mailcoach\Domain\Content\Events\ContentOpenedEvent;
use Spatie\Mailcoach\Domain\Content\Events\LinkClickedEvent;
use Spatie\Mailcoach\Domain\Content\Models\Click;
use Spatie\Mailcoach\Domain\Content\Models\Open;
use Spatie\Mailcoach\Domain\Shared\Enums\SendFeedbackType;
use Spatie\Mailcoach\Domain\Shared\Events\BounceRegisteredEvent;
use Spatie\Mailcoach\Domain\Shared\Events\SoftBounceRegisteredEvent;
use Spatie\Mailcoach\Domain\Shared\Models\Concerns\UsesDatabaseConnection;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;

/**
 * @method static Builder|static query()
 *
 * @property-read ?Subscriber $subscriber
 */
class Send extends Model
{
    use HasFactory;
    use HasUuid;
    use MassPrunable;
    use UsesDatabaseConnection;
    use UsesMailcoachModels;

    public $table = 'mailcoach_sends';

    public $guarded = [];

    public $casts = [
        'sending_job_dispatched_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function getSendable(): ?Sendable
    {
        return $this->contentItem->model;
    }

    public function getMailerKey(): ?string
    {
        return $this->contentItem->getMailerKey($this->subscriber);
    }

    public static function findByTransportMessageId(string $transportMessageId): ?Model
    {
        return static::where('transport_message_id', $transportMessageId)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Audience\Models\Subscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(self::getSubscriberClass(), 'subscriber_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Content\Models\ContentItem, $this>
     */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(self::getContentItemClass());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Content\Models\Open, $this>
     */
    public function opens(): HasMany
    {
        return $this->hasMany(self::getOpenClass(), 'send_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Content\Models\Click, $this>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(self::getClickClass(), 'send_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Shared\Models\SendFeedbackItem, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(self::getSendFeedbackItemClass(), 'send_id');
    }

    public function latestFeedback(): HasOne
    {
        return $this->feedback()->one()->latestOfMany();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Shared\Models\SendFeedbackItem, $this>
     */
    public function bounces(): HasMany
    {
        return $this
            ->hasMany(self::getSendFeedbackItemClass())
            ->whereIn('type', [SendFeedbackType::Bounce, SendFeedbackType::SoftBounce]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Shared\Models\SendFeedbackItem, $this>
     */
    public function hardBounces(): HasMany
    {
        return $this
            ->hasMany(self::getSendFeedbackItemClass())
            ->where('type', SendFeedbackType::Bounce);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Shared\Models\SendFeedbackItem, $this>
     */
    public function softBounces(): HasMany
    {
        return $this
            ->hasMany(self::getSendFeedbackItemClass())
            ->where('type', SendFeedbackType::SoftBounce);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Shared\Models\SendFeedbackItem, $this>
     */
    public function complaints(): HasMany
    {
        return $this
            ->hasMany(self::getSendFeedbackItemClass())
            ->where('type', SendFeedbackType::Complaint);
    }

    public function markAsSent(): self
    {
        $this->sent_at = now();
        $this->save();

        return $this;
    }

    public function wasAlreadySent(): bool
    {
        return ! is_null($this->sent_at);
    }

    public function markAsSendingJobDispatched(): self
    {
        $this->update([
            'sending_job_dispatched_at' => now(),
        ]);

        return $this;
    }

    public function mailSendingJobWasDispatched(): bool
    {
        return ! is_null($this->sending_job_dispatched_at);
    }

    public function storeTransportMessageId(string $transportMessageId)
    {
        $this->update(['transport_message_id' => $transportMessageId]);

        return $this;
    }

    public function registerOpen(?DateTimeInterface $openedAt = null): ?Open
    {
        if (! $this->contentItem) {
            return null;
        }

        if ($this->wasOpenedInTheLastSeconds($this->opens(), 5)) {
            return null;
        }

        if ($this->subscriber && Feature::store('array')->for('')->active('mailcoach::subscriber-engagement')) {
            if (! $this->subscriber->opens()->where('content_item_id', $this->content_item_id)->exists()) {
                $this->subscriber->increment('emails_opened', extra: [
                    'last_open_at' => $openedAt ?? now(),
                ]);
            } else {
                $this->subscriber->update([
                    'last_open_at' => $openedAt ?? now(),
                ]);
            }
        }

        $open = static::getOpenClass()::create([
            'send_id' => $this->id,
            'content_item_id' => $this->content_item_id,
            'subscriber_id' => $this->subscriber?->id,
            'created_at' => $openedAt ?? now(),
        ]);

        event(new ContentOpenedEvent($open));

        $this->contentItem->dispatchCalculateStatistics();

        return $open;
    }

    protected function wasOpenedInTheLastSeconds(HasMany $relation, int $seconds): bool
    {
        /** @var Send $latestOpen */
        $latestOpen = $relation->latest()->first();

        if (! $latestOpen) {
            return false;
        }

        return $seconds > $latestOpen->created_at->diffInSeconds(absolute: true);
    }

    public function registerClick(string $url, ?DateTimeInterface $clickedAt = null): ?Click
    {
        if (! $this->contentItem) {
            return null;
        }

        if (! $this->opens()->count()) {
            $this->registerOpen($clickedAt);
        }

        $url = resolve(StripUtmTagsFromUrlAction::class)->execute($url);
        $unsubscribeUrlPrefix = str_replace(
            search: urlencode('<subscriber-uuid>'),
            replace: '',
            subject: route('mailcoach.unsubscribe', '<subscriber-uuid>')
        );

        if (Str::startsWith($url, $unsubscribeUrlPrefix)) {
            return null;
        }

        if ($this->subscriber && Feature::store('array')->for('')->active('mailcoach::subscriber-engagement')) {
            if (! $this->subscriber->clicks()->whereRelation('link', 'content_item_id', $this->content_item_id)->exists()) {
                $this->subscriber->increment('emails_clicked', extra: [
                    'last_click_at' => $clickedAt ?? now(),
                ]);
            } else {
                $this->subscriber->update([
                    'last_click_at' => $clickedAt ?? now(),
                ]);
            }
        }

        $link = self::getLinkClass()::firstOrCreate([
            'content_item_id' => $this->content_item_id,
            'url' => $url,
        ], ['uuid' => Str::uuid()]);

        $click = $link->registerClick($this, $clickedAt);

        event(new LinkClickedEvent($click));

        $this->contentItem->dispatchCalculateStatistics();

        return $click;
    }

    public function registerSoftBounce(?DateTimeInterface $bouncedAt = null, array $extraAttributes = []): self
    {
        return $this->registerBounce($bouncedAt, true, $extraAttributes);
    }

    public function registerSuppressed(): self
    {
        if (! $this->contentItem) {
            return $this;
        }

        $this->feedback()->create([
            'type' => SendFeedbackType::Suppressed,
            'uuid' => Str::uuid(),
            'created_at' => now(),
        ]);

        $this->subscriber->update(['unsubscribed_at' => now()]);

        event(new SubscriberSuppressedEvent($this->subscriber));

        $this->contentItem->dispatchCalculateStatistics();

        return $this;
    }

    public function registerBounce(?DateTimeInterface $bouncedAt = null, bool $softBounce = false, array $extraAttributes = []): self
    {
        if (! $this->contentItem) {
            return $this;
        }

        $this->feedback()->create([
            'type' => $softBounce ? SendFeedbackType::SoftBounce : SendFeedbackType::Bounce,
            'uuid' => Str::uuid(),
            'created_at' => $bouncedAt ?? now(),
            'extra_attributes' => $extraAttributes,
        ]);

        if ($softBounce) {
            event(new SoftBounceRegisteredEvent($this));
        }

        if (! $softBounce) {
            if ($this->subscriber) {
                $this->subscriber->update(['unsubscribed_at' => now()]);
                self::getSuppressionClass()::for($this->subscriber->email);
            }

            event(new BounceRegisteredEvent($this));
        }

        $this->contentItem->dispatchCalculateStatistics();

        return $this;
    }

    public function registerComplaint(?DateTimeInterface $complainedAt = null, array $extraAttributes = []): self
    {
        if (! $this->contentItem) {
            return $this;
        }

        $this->feedback()->create([
            'type' => SendFeedbackType::Complaint,
            'uuid' => Str::uuid(),
            'created_at' => $complainedAt ?? now(),
            'extra_attributes' => $extraAttributes,
        ]);

        if ($this->subscriber) {
            $this->subscriber->unsubscribe($this);

            self::getSuppressionClass()::for($this->subscriber->email, SuppressionReason::spamComplaint);
        }

        event(new ComplaintRegisteredEvent($this));

        $this->contentItem->dispatchCalculateStatistics();

        return $this;
    }

    public function scopeUndispatched(Builder $query): void
    {
        $query->whereNull('sending_job_dispatched_at');
    }

    public function scopeDispatched(Builder $query): void
    {
        $query->whereNotNull('sending_job_dispatched_at');
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('sent_at');
    }

    public function scopeSent(Builder $query): void
    {
        $query
            ->whereNotNull('sent_at')
            ->whereNull('failed_at');
    }

    public function scopeInvalidated(Builder $query): void
    {
        $query->whereNotNull('invalidated_at');
    }

    public function scopeFailed(Builder $query): void
    {
        $query->whereNotNull('failed_at');
    }

    public function scopeBounced(Builder $query): void
    {
        $query->whereHas('feedback', function (Builder $query) {
            $query->whereIn('type', [SendFeedbackType::Bounce, SendFeedbackType::SoftBounce]);
        });
    }

    public function scopeComplained(Builder $query): void
    {
        $query->whereHas('feedback', function (Builder $query) {
            $query->where('type', SendFeedbackType::Complaint);
        });
    }

    public function invalidate(): self
    {
        $this->update([
            'sent_at' => now(),
            'invalidated_at' => now(),
        ]);

        return $this;
    }

    public function markAsFailed(string $failureReason): self
    {
        if (! $this->exists) {
            return $this;
        }

        $this->update([
            'sent_at' => now(),
            'failed_at' => now(),
            'failure_reason' => $failureReason,
        ]);

        return $this;
    }

    public function prepareRetryAfterFailedSend()
    {
        $this->update([
            'sent_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'sending_job_dispatched_at' => now(),
        ]);
    }

    protected static function newFactory(): SendFactory
    {
        return new SendFactory;
    }

    public function prunable(): Builder
    {
        if (! config('mailcoach.prune_after_days.sends')) {
            throw new Exception('No prune setting defined for '.self::class);
        }

        return static::where('created_at', '<=', now()->subDays(config('mailcoach.prune_after_days.sends')));
    }
}
