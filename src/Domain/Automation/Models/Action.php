<?php

namespace Spatie\Mailcoach\Domain\Automation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\Mailcoach\Database\Factories\ActionFactory;
use Spatie\Mailcoach\Domain\Audience\Models\Subscriber;
use Spatie\Mailcoach\Domain\Automation\Jobs\RunActionForActionSubscriberJob;
use Spatie\Mailcoach\Domain\Automation\Support\Actions\AutomationAction;
use Spatie\Mailcoach\Domain\Shared\Models\Concerns\UsesDatabaseConnection;
use Spatie\Mailcoach\Domain\Shared\Models\HasUuid;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Throwable;

class Action extends Model
{
    use HasFactory;
    use HasUuid;
    use UsesDatabaseConnection;
    use UsesMailcoachModels;

    public $table = 'mailcoach_automation_actions';

    protected $guarded = [];

    protected $casts = [
        'order' => 'int',
    ];

    public function setActionAttribute(AutomationAction $value): void
    {
        $this->attributes['action'] = json_encode(array_merge($value->toArray(), [
            'class' => $value::class,
            'uuid' => $this->uuid,
        ]));
    }

    public function getActionAttribute(string $value): AutomationAction
    {
        Carbon::useStrictMode(false);

        // If the action is base64 encoded, decode it first
        // @todo Remove next major version
        if ($value === base64_encode(base64_decode($value, true))) {
            $value = base64_decode($value);
        }

        try {
            // @todo Remove next major version, only keep json_decode
            /** @var AutomationAction $action */
            $action = unserialize($value);
        } catch (Throwable $initialException) {
            try {
                $actionData = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
                $actionClass = Arr::pull($actionData, 'class');

                $action = $actionClass::make($actionData);
            } catch (Throwable) {
                throw $initialException;
            }
        }

        $action->uuid = $this->uuid;

        return $action;
    }

    /**
     * We have this method which accepts the previous pivot as a way to override the Action
     * model and add custom logic to the automation actions process. For example to add
     * some extra context on the pivot model and pass that context along to the next.
     */
    public function attachSubscriber(Subscriber $subscriber, ActionSubscriber $previousPivot): void
    {
        $this->subscribers()->attach($subscriber);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\Spatie\Mailcoach\Domain\Audience\Models\Subscriber, $this>
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(static::getSubscriberClass(), static::getActionSubscriberTableName())
            ->withPivot(['completed_at', 'halted_at', 'run_at'])
            ->using(self::getActionSubscriberClass())
            ->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Automation\Models\ActionSubscriber, $this>
     */
    public function actionSubscribers(): HasMany
    {
        return $this->hasMany(self::getActionSubscriberClass());
    }

    public function pendingActionSubscribers(): HasMany
    {
        return $this
            ->actionSubscribers()
            ->whereNull(['halted_at', 'completed_at', 'job_dispatched_at']);
    }

    public function activeSubscribers(): BelongsToMany
    {
        return $this->subscribers()
            ->wherePivotNull('halted_at')
            ->wherePivotNull('run_at');
    }

    public function completedSubscribers(): BelongsToMany
    {
        return $this->subscribers()
            ->wherePivotNotNull('run_at');
    }

    public function haltedSubscribers(): BelongsToMany
    {
        return $this->subscribers()
            ->wherePivotNotNull('halted_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Automation\Models\Automation, $this>
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(static::getAutomationClass());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Automation\Models\Action, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::getAutomationActionClass(), 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Spatie\Mailcoach\Domain\Automation\Models\Action, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::getAutomationActionClass(), 'parent_id')->orderBy('order');
    }

    public function toLivewireArray(): array
    {
        /** @var AutomationAction $action */
        $action = $this->action;

        return [
            'uuid' => $this->uuid,
            'class' => $action::class,
            'data' => $action->toArray(),
            'active' => (int) ($this->active_subscribers_count ?? 0),
            'completed' => ($this->completed_subscribers_count ?? 0) - ($this->halted_subscribers_count ?? 0),
            'halted' => (int) ($this->halted_subscribers_count ?? 0),
        ];
    }

    public function run(): void
    {
        $this->action->getActionSubscribersQuery($this)
            ->lazyById()
            ->each(function (ActionSubscriber $actionSubscriber): void {
                $actionSubscriber->update(['job_dispatched_at' => now()]);

                dispatch(new RunActionForActionSubscriberJob($actionSubscriber))->afterCommit();
            });
    }

    protected static function newFactory(): ActionFactory
    {
        return new ActionFactory;
    }
}
