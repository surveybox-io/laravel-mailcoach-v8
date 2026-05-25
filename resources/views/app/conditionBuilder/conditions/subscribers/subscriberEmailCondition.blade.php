<x-mailcoach::condition :index="$index" :title="$title" :read-only="$readOnly">
    <div class="col-span-6">
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
    <div class="col-span-6">
        <x-mailcoach::text-field
            :label="__mc('Value')"
            name="value-{{ $index }}"
            wire:model.live.debounce.250ms="storedCondition.value"
            :disabled="$readOnly"
            required
        />
    </div>
</x-mailcoach::condition>
