<div class="grid gap-9">
    @foreach ($storedConditions as $index => $storedCondition)
        @php($condition = app(\Spatie\Mailcoach\Domain\ConditionBuilder\Actions\CreateConditionFromKeyAction::class)->execute($storedCondition['condition']['key']))
        <div>
            @livewire($condition->getComponent(), [
                'index' => $index,
                'storedCondition' => $storedCondition,
                'emailList' => $emailList,
                'readOnly' => $readOnly,
            ], key('stored-condition-' . $storedCondition['condition']['key'] . '-' . $index))

            @unless($loop->last)
                <div class="text-center uppercase font-bold tracking-wider text-xs mt-4 -mb-5">{{ __mc('And') }}</div>
            @endunless
        </div>
    @endforeach

    @if (! $readOnly)
        @include('mailcoach::app.conditionBuilder.components.conditionsDropdown')
    @endif

    <hr class="border-t border-sand-bleak" />
</div>
