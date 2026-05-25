<x-mailcoach::layout :origin-title="__mc('Automations')">
    <div class="flex flex-col gap-y-20">
        <div>
            @include('mailcoach::app.partials.header', [
                'title' => __mc('Automations'),
                'create' => Auth::user()->can('create', \Spatie\Mailcoach\Mailcoach::getAutomationClass())
                    ? 'automation'
                    : false,
            ])
            <livewire:mailcoach::automations />
        </div>

        <div>
            @include('mailcoach::app.partials.header', [
                'title' => __mc('Emails'),
                'create' => Auth::user()->can('create', \Spatie\Mailcoach\Mailcoach::getAutomationMailClass())
                    ? 'automation-mail'
                    : false,
                'createText' => __mc('New automation mail'),
            ])
            <livewire:mailcoach::automation-mails />
        </div>
    </div>
</x-mailcoach::layout>
