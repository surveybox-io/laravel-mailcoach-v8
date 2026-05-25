<div class="card-grid">
    <form method="POST">
        @csrf
        @method('PUT')
        <x-mailcoach::fieldset card :legend="__mc('Interval')">
            @if ($automation->interval === '1 minute')
                <x-mailcoach::alert type="warning">
                    {{ __mc('An interval of 1 minute can generate a lot of queued jobs for subscribers pending in an action. Make sure you really need this granularity.') }}
                </x-mailcoach::alert>
            @endif

            <div class="flex items-end">

                <x-mailcoach::select-field
                    class="w-48"
                    name="interval"
                    wire:model="interval"
                    :sort="false"
                    :options="[
                        '1 minute' => 'Every minute',
                        '10 minutes' => 'Every 10 minutes',
                        '1 hour' => 'Hourly',
                        '1 day' => 'Daily',
                        '1 week' => 'Weekly',
                    ]"
                    required
                />

                <x-mailcoach::button
                        class="ml-1"
                        :label="__mc('Save')"
                        wire:click.prevent="save"
                        @keydown.prevent.window.cmd.s="$wire.call('save')"
                        @keydown.prevent.window.ctrl.s="$wire.call('save')"
                />
            </div>
        </x-mailcoach::fieldset>
    </form>

    <x-mailcoach::fieldset card :legend="__mc('Manual trigger')">
        <x-mailcoach::alert type="info">
            <p>{{ __mc('This triggers the automation for all subscribers in :description, regardless of the trigger that is set.', ['description' => $automation->emailList->name . ' (' . $automation->getSegment()->description() . ')']) }}</p>
            @if ($automation->repeat_enabled)
                <p>{{ __mc('Repeating is enabled for this automation which will result in subscribers already in the automation to go through it again.') }}</p>
                @if ($automation->repeat_only_after_halt)
                    <p>{{ __mc('Only halted subscribers will be repeated.') }}</p>
                @endif
            @endif
        </x-mailcoach::alert>
        <div class="relative">
            <x-mailcoach::confirm-button
                wire:key="trigger-button"
                class="button disabled:pointer-events-none"
                type="button"
                confirm-text="{{ __mc('Are you sure you want to trigger this automation for :count subscribers?', ['count' => $automation->segmentSubscriberCount()]) }}"
                on-confirm="() => $wire.triggerAutomation()"
                wire:loading.class="opacity-75 pointer-events-none"
                wire:target="triggerAutomation"
            >
                <span class="flex items-center">
                    <x-heroicon-s-play wire:loading.remove wire:target="triggerAutomation" class="w-4" />
                    <x-heroicon-s-arrow-path wire:loading wire:target="triggerAutomation" class="w-4 animate-spin" />
                    <span class="ml-2">{{ __mc('Trigger') }}</span>
                </span>
            </x-mailcoach::confirm-button>
        </div>
    </x-mailcoach::fieldset>

    <x-mailcoach::fieldset card :legend="__mc('Activate automation')">
        @if ($error)
            <x-mailcoach::alert type="error">
                {{ $error }}
            </x-mailcoach::alert>
        @endif
        @if ($automation->actions->filter(fn ($action) => $action->action::class === \Spatie\Mailcoach\Domain\Automation\Support\Actions\HaltAction::class)->count() === 0)
            <x-mailcoach::alert type="info">
                {{ __mc('This automation will keep running so any actions added later will be run on existing subscribers. If this automation has a desired end, we recommend adding a Halt action.') }}
            </x-mailcoach::alert>
        @endif
        <div>
            @if ($automation->status === \Spatie\Mailcoach\Domain\Automation\Enums\AutomationStatus::Started)
                <button wire:key="pause" class="button button-orange" type="button" wire:click.prevent="pause">
                    <span class="flex items-center">
                        <x-heroicon-s-pause class="w-4" />
                        <span class="ml-2">{{ __mc('Pause') }}</span>
                    </span>
                </button>
            @else
                <button wire:key="start" class="button button-green" type="button" wire:click.prevent="start">
                    <span class="flex items-center">
                        <x-heroicon-s-play class="w-4" />
                        <span class="ml-2">{{ __mc('Start') }}</span>
                    </span>
                </button>
            @endif
        </div>
    </x-mailcoach::fieldset>
</div>
