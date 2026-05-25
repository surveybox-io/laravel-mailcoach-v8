@use(\Spatie\Mailcoach\Domain\ConditionBuilder\Enums\EngagementType)
<x-mailcoach::condition :index="$index" :title="$title" :read-only="$readOnly">
    @if(\Laravel\Pennant\Feature::store('array')->for('')->active('mailcoach::subscriber-engagement'))
        <div class="col-span-3">
            <x-mailcoach::select-field
                :label="__mc('Type')"
                name="type-{{ $index }}"
                wire:model.live="storedCondition.value.type"
                :options="[
                    EngagementType::OpenRate->value => __mc('Open rate'),
                    EngagementType::ClickRate->value => __mc('Click rate'),
                    EngagementType::LastOpenAt->value => __mc('Last open'),
                    EngagementType::LastClickAt->value => __mc('Last click'),
                ]"
                :disabled="$readOnly"
                :sort="false"
                required
            />
        </div>
        <div class="col-span-3">
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
            @if (in_array($storedCondition['value']['type'], [EngagementType::OpenRate->value, EngagementType::ClickRate->value]))
                <div class="flex items-center gap-2 w-full">
                    <x-mailcoach::text-field
                        wrapper-class="w-full"
                        :label="__mc('Value')"
                        name="value-{{ $index }}"
                        type="number"
                        max="100"
                        wire:model.live.debounce.250ms="storedCondition.value.value"
                        :disabled="$readOnly"
                        required
                    />
                    <span class="mt-6 text-lg">%</span>
                </div>
            @else
                <div class="form-field w-full min-w-full">
                    <label class="label label-required" for="date">{{ __mc('Date & time') }}</label>
                    <x-mailcoach::date-time-field
                        name="date"
                        :value="$date_parsed"
                        :min-date="now()->subYears(10)"
                        :disabled="$readOnly"
                        required
                    />
                </div>
            @endif
        </div>
    @else
        <div class="col-span-12">
            {!! __mc('Your database does not contain the necessary columns to support this feature. Check out the <a target="_blank" class="text-blue underline" href="https://www.mailcoach.app/resources/blog/subscriber-engagement-statistics-now-available">subscriber engagement</a> announcement to run the necessary migrations.') !!}
        </div>
    @endif
</x-mailcoach::condition>
