<?php

namespace Spatie\Mailcoach\Livewire\MailConfiguration\Ses\Steps;

use Spatie\LivewireWizard\Components\StepComponent;
use Spatie\Mailcoach\Domain\Vendor\Ses\MailcoachSes;
use Spatie\Mailcoach\Domain\Vendor\Ses\MailcoachSesConfig;
use Spatie\Mailcoach\Livewire\MailConfiguration\Concerns\UsesMailer;

class SummaryStepComponent extends StepComponent
{
    use UsesMailer;

    public int $mailerId;

    public function render()
    {
        $mailer = $this->mailer();

        $config = new MailcoachSesConfig(
            $mailer->get('ses_key'),
            $mailer->get('ses_secret'),
            $mailer->get('ses_region'),
        );
        $config->setConfigurationName($mailer->get('ses_configuration_set'));

        $mailcoachSes = (new MailcoachSes($config));

        $isInSandboxMode = $mailcoachSes->isInSandboxMode();
        $configurationSet = $mailcoachSes->getConfigurationSet();
        $eventDestination = $mailcoachSes->getConfigurationSetEventDestination();
        $snsTopic = $mailcoachSes->getSnsTopicArn();
        $snsSubscription = $snsTopic ? $mailcoachSes->getSnsSubscription() : null;

        if (! empty($eventDestination)) {
            $mailer->merge([
                'open_tracking_enabled' => in_array('OPEN', $eventDestination['MatchingEventTypes']),
                'click_tracking_enabled' => in_array('CLICK', $eventDestination['MatchingEventTypes']),
            ]);
        }

        return view('mailcoach::app.configuration.mailers.wizards.ses.summary', [
            'isInSandboxMode' => $isInSandboxMode,
            'configurationSet' => $configurationSet,
            'eventDestination' => $eventDestination,
            'snsTopic' => $snsTopic,
            'snsSubscription' => $snsSubscription,
            'mailer' => $mailer,
        ]);
    }

    public function sendTestEmail() {}

    public function stepInfo(): array
    {
        return [
            'label' => 'Summary',
        ];
    }
}
