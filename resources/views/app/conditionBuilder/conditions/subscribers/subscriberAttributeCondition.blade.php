@use(Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ComparisonOperator)
<x-mailcoach::condition :index="$index" :title="$title" :read-only="$readOnly">
    <x-mailcoach::alert type="info" class="col-span-12" full>
        {{ __mc('Segments using attribute conditions can get slow on large email lists. Consider using tags first.') }}
    </x-mailcoach::alert>
    <div class="col-span-4">
        <x-mailcoach::text-field
            :label="__mc('Attribute')"
            name="attribute-{{ $index }}"
            wire:model="storedCondition.value.attribute"
            :disabled="$readOnly"
        />
    </div>
    <div class="col-span-4">
        <x-mailcoach::select-field
            :label="__mc('Comparison')"
            name="operator-{{ $index }}"
            wire:model="storedCondition.comparison_operator"
            :options="$storedCondition['condition']['comparison_operators'] ?? []"
            :sort="false"
            :disabled="$readOnly"
            required
        />
    </div>
    <div class="col-span-4">
        @if (in_array($storedCondition['comparison_operator'], [ComparisonOperator::In->value, ComparisonOperator::NotIn->value]))
            <x-mailcoach::tags-field
                :label="__mc('Value')"
                name="value-{{ $index }}"
                :value="$storedCondition['value']['value'] ?? []"
                :tags="$storedCondition['value']['value'] ?? []"
                :disabled="$readOnly"
                allow-create
                multiple
            />
        @else
            <x-mailcoach::text-field
                :label="__mc('Value')"
                name="value-{{ $index }}"
                :disabled="$readOnly"
                wire:model.live.debounce.250ms="storedCondition.value.value"
            />
        @endif
    </div>
</x-mailcoach::condition>
