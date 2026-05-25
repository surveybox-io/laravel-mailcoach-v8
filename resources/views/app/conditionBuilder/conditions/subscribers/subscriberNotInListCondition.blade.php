<x-mailcoach::condition :index="$index" :title="$title" :read-only="$readOnly">
    <div class="col-span-6">
        <x-mailcoach::select-field
            :label="__mc('Value')"
            name="value-{{ $index }}"
            :options="$options"
            :sort="false"
            multiple
            wire:model.live.debounce.250ms="storedCondition.value"
            required
            :disabled="$readOnly"
        />
    </div>
</x-mailcoach::condition>
