<div class="grid gap-y-3">
    @if ($readOnly)
        <p>{{ $automation->getTrigger()->date?->toMailcoachFormat() }}</p>
    @else
        <x-mailcoach::date-time-field
            :label="__mc('Date')"
            name="date"
            :value="$automation->getTrigger()->date ?? null"
            :min-date="$automation->created_at"
            required
        />
    @endif
    <x-mailcoach::select-field
        name="repeat"
        :label="__mc('Repeat')"
        wire:model="repeat"
        :sort="false"
        :options="[
            '' => __mc('Don\'t repeat'),
            'daily' => __mc('Daily'),
            'monthly' => __mc('Monthly'),
            'yearly' => __mc('Yearly'),
        ]"
        :disabled="$readOnly"
    />
    @if ($repeat)
        <x-mailcoach::alert type="info">
            {{ __mc('Repeating will pick up new subscribers added after the initial date.') }}
        </x-mailcoach::alert>
    @endif
</div>
