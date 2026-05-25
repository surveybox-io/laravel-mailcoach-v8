<?php

namespace Spatie\Mailcoach\Domain\Settings\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Spatie\Mailcoach\Domain\Settings\Models\WebhookConfiguration;

class WebhookDisabledMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $theme = 'mailcoach::mails.layout.mailcoach';

    public function __construct(private string|array $recipients, public WebhookConfiguration $webhookConfiguration) {}

    public function build()
    {
        return $this
            ->subject(__mc('Mailcoach Webhook :name disabled', [
                'name' => $this->webhookConfiguration->name,
            ]))
            ->to($this->recipients)
            ->markdown('mailcoach::mails.webhookDisabled');
    }
}
