<div class="card-grid">
    @include('mailcoach::app.configuration.mailers.wizards.wizardNavigation')

    <x-mailcoach::fieldset card :legend="__mc('Summary')">
        <x-mailcoach::alert type="success">
            <p>
                Your SES account has been set up. We highly recommend sending a small test campaign to yourself to check if
                everything is working as expected.
            </p>
        </x-mailcoach::alert>

        @if($isInSandboxMode)
            <x-mailcoach::alert type="warning">
                <p>
                    Your SES account is currently in <a href="https://docs.aws.amazon.com/ses/latest/dg/request-production-access.html" class="link" target="_blank">sandbox mode</a>. This means that you can only send to emails that are verified with Amazon.
                </p>
            </x-mailcoach::alert>
        @elseif($mailer->get('timespan_in_seconds') === 1 && $mailer->get('mails_per_timespan') === 1)
            <x-mailcoach::alert type="warning">
                Your account is not in sandbox mode but your throttling settings are set to 1 mail / second. You can find your sending limit in your SES Account Dashboard to update the throttling config for faster campaigns.
            </x-mailcoach::alert>
        @endif

        <table>
            <x-mailcoach::checklist-item
                neutral
                :label="__mc('Access Key')"
                :value="$mailer->get('ses_key')"
            />
            <x-mailcoach::checklist-item
                neutral
                :label="__mc('Region')"
                :value="$mailer->get('ses_region')"
            />
            <x-mailcoach::checklist-item
                :test="isset($configurationSet['ConfigurationSetName'])"
                :label="__mc('Configuration set')"
                :value="isset($configurationSet['ConfigurationSetName']) ? $configurationSet['ConfigurationSetName'] : __mc('Configuration set does not exist in Amazon SES')"
            />
            @if (empty($eventDestination))
                <x-mailcoach::checklist-item
                    :label="__mc('Configuration set event destination')"
                    :test="false"
                    :value="__mc('No event destination in configuration set.')"
                />
            @elseif(! $eventDestination['Enabled'])
                <x-mailcoach::checklist-item
                    :label="__mc('Configuration set event destination')"
                    :test="false"
                    :value="__mc('Event destination is disabled.')"
                />
            @endif
            @if (empty($eventDestination['SnsDestination']))
                <x-mailcoach::checklist-item
                    :label="__mc('Configuration set event destination')"
                    :test="false"
                    :value="__mc('Event destination is not an SNS topic.')"
                />
            @endif
            @if ($snsTopic && ! $snsSubscription)
                <x-mailcoach::checklist-item
                    :label="__mc('SNS Subscription')"
                    :test="false"
                    :value="__mc('No SNS subscription found.')"
                />
            @elseif ($snsTopic && $snsSubscription && $snsSubscription['Endpoint'] !== action([\Spatie\Mailcoach\Http\Api\Controllers\Vendor\Ses\SesWebhookController::class], $mailer->configName()))
                <x-mailcoach::checklist-item
                    :label="__mc('Feedback endpoint')"
                    :test="false"
                >
                    <x-slot:value>
                        <p>{{ __mc('Endpoint should be') }}</p>
                        <p><code>{{ action([\Spatie\Mailcoach\Http\Api\Controllers\Vendor\Ses\SesWebhookController::class], $mailer->configName()) }}</code></p>
                        <p>{{ __mc('But SNS subscription is set to') }}</p>
                        <p><code>{{ $snsSubscription['Endpoint'] }}</code></p>
                    </x-slot:value>
                </x-mailcoach::checklist-item>
            @endif
            <x-mailcoach::checklist-item
                :label="__mc('Open tracking')"
                :test="$mailer->get('open_tracking_enabled')"
                :value="$mailer->get('open_tracking_enabled') ? __mc('Enabled') : __mc('Disabled')"
            />
            <x-mailcoach::checklist-item
                :label="__mc('Click tracking')"
                :test="$mailer->get('click_tracking_enabled')"
                :value="$mailer->get('click_tracking_enabled') ? __mc('Enabled') : __mc('Disabled')"
            />
            <x-mailcoach::checklist-item
                neutral
                :label="__mc('Throttling')"
            >
                <x-slot:value>
                    <p><strong>{{ $mailer->get('mails_per_timespan', 10) }}</strong> {{ __mc('mails every') }} <strong>{{ $mailer->get('timespan_in_seconds', 1) }}</strong> {{ __mc_choice('second|seconds', $mailer->get('timespan_in_seconds', 1)) }}</p>
                </x-slot:value>
            </x-mailcoach::checklist-item>
        </table>
    </x-mailcoach::fieldset>

    @include('mailcoach::app.configuration.mailers.partials.mailerName')

    <x-mailcoach::card buttons>
        <x-mailcoach::button :label="__mc('Send test email')" x-on:click.prevent="$dispatch('open-modal', { id: 'send-test' })" />
    </x-mailcoach::card>
</div>
