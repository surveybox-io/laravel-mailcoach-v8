<?php

namespace Spatie\Mailcoach\Domain\Automation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Mailcoach\Database\Factories\AutomationMailFactory;
use Spatie\Mailcoach\Domain\Automation\Actions\SendAutomationMailToSubscriberAction;
use Spatie\Mailcoach\Domain\Automation\Jobs\SendAutomationMailTestJob;
use Spatie\Mailcoach\Domain\Automation\Models\Action as ActionModel;
use Spatie\Mailcoach\Domain\Content\Models\ContentItem;
use Spatie\Mailcoach\Domain\Settings\Models\Mailer;
use Spatie\Mailcoach\Domain\Shared\Models\Sendable;
use Spatie\Mailcoach\Mailcoach;

class AutomationMail extends Sendable
{
    public $table = 'mailcoach_automation_mails';

    protected $casts = [
        'add_subscriber_tags' => 'boolean',
        'add_subscriber_link_tags' => 'boolean',
    ];

    public static function booted(): void
    {
        static::created(function (AutomationMail $automationMail) {
            if (! $automationMail->contentItem) {
                $contentItem = $automationMail->contentItem()->firstOrCreate();
                $automationMail->setRelation('contentItem', $contentItem);
            }
        });

        static::deleting(function (AutomationMail $automationMail) {
            $automationMail->contentItem->delete();
        });
    }

    public function scopeInAutomations(Builder $query, array|Collection $automationUuids): void
    {
        $shortname = (new ReflectionClass(new self))->getShortName();

        $automationMailIds = self::getAutomationActionClass()::query()
            ->whereHas('automation', fn (Builder $query) => $query->whereIn('uuid', $automationUuids))
            ->get()
            ->filter(function (ActionModel $action) use ($shortname) {
                $value = $action->getRawOriginal('action');

                // @todo: Remove next major version
                if ($value === base64_encode(base64_decode($value, true))) {
                    $value = base64_decode($action->getRawOriginal('action'));
                }

                return str_contains($value, $shortname);
            })
            ->map(function (ActionModel $action) use ($shortname) {
                $rawAction = $action->getRawOriginal('action');

                // @todo: Remove full if block in next major version
                if ($rawAction === base64_encode(base64_decode($rawAction, true))) {
                    /**
                     * We want to get any action that has an automation email
                     * referenced. Therefore, we need to parse serialized
                     * string of the action to get the model identifier.
                     */
                    $rawAction = base64_decode($action->getRawOriginal('action'));
                    $idPart = Str::after($rawAction, $shortname.'";s:2:"id";i:');
                    $id = Str::before($idPart, ';');

                    return (int) $id;
                }

                $idPart = Str::after($rawAction, 'automation_mail_id":');
                $id = Str::before($idPart, ',');

                return (int) $id;
            });

        $query->whereIn('id', $automationMailIds);
    }

    /**
     * Returns a tuple with open & click tracking values
     */
    public function tracking(): array
    {
        $mailer = $this->getMailer();

        return [
            $mailer?->get('open_tracking_enabled', false),
            $mailer?->get('click_tracking_enabled', false),
        ];
    }

    public function isReady(): bool
    {
        return $this->contentItem->isReady();
    }

    public function send(ActionSubscriber $actionSubscriber): self
    {
        $this->ensureSendable();

        if ($this->hasCustomMailable()) {
            $this->pullSubjectFromMailable();

            $this->content($this->contentFromMailable());
        }

        /** @var \Spatie\Mailcoach\Domain\Automation\Actions\SendAutomationMailToSubscriberAction $sendAutomationMailToSubscriberAction */
        $sendAutomationMailToSubscriberAction = Mailcoach::getAutomationActionClass('send_automation_mail_to_subscriber',
            SendAutomationMailToSubscriberAction::class);
        $sendAutomationMailToSubscriberAction->execute($this, $actionSubscriber);

        return $this;
    }

    public function sendTestMail(string|array $emails, ?ContentItem $contentItem = null): void
    {
        if ($this->hasCustomMailable($contentItem)) {
            $this->pullSubjectFromMailable($contentItem);
        }

        collect($emails)->each(function (string $email) use ($contentItem) {
            (new SendAutomationMailTestJob($this, $email, $contentItem))->handle();
        });
    }

    public function webviewUrl(): string
    {
        return url(route('mailcoach.automations.webview', $this->uuid));
    }

    protected static function newFactory(): AutomationMailFactory
    {
        return new AutomationMailFactory;
    }

    public function getMailerKey(): ?string
    {
        return Mailcoach::defaultAutomationMailer();
    }

    public function getMailer(): ?Mailer
    {
        $mailerClass = config('mailcoach.models.mailer');

        if (! class_exists($mailerClass)) {
            return null;
        }

        if (! $mailerKey = $this->getMailerKey()) {
            return null;
        }

        return $mailerClass::all()->first(fn ($mailerModel) => $mailerKey === $mailerModel->configName());
    }

    /**
     * We override this to prevent a MissingAttributeException
     * when accessing the emailList on an AutomationMail
     */
    public function getAttribute($key): mixed
    {
        if ($key === 'emailList') {
            return null;
        }

        return parent::getAttribute($key);
    }
}
