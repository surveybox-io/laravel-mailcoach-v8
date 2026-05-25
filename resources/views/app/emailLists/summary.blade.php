<div class="card-grid" wire:init="loadData">
    <h2 class="text-xl font-medium">{{ __mc('Subscribers') }}</h2>
    <x-mailcoach::card class="py-0 sm:py-6">
        <div class="grid sm:grid-cols-3 divide-y sm:divide-x sm:divide-y-0 divide-snow">
            <x-mailcoach::statistic
                :href="route('mailcoach.emailLists.subscribers', $emailList)"
                class="col-start-1"
                :stat="$readyToLoad ? number_format($totalSubscriptionsCount) : '—'"
                :label="__mc('Total subscribers')"
            />
            <x-mailcoach::statistic
                :stat="$readyToLoad
                    ? ($totalSubscriptionsCount - $startSubscriptionsCount > 0 ? '+' : '') . number_format($totalSubscriptionsCount - $startSubscriptionsCount)
                    : '—'"
                :label="__mc('Change in last :daterange days', ['daterange' => \Illuminate\Support\Facades\Date::parse($start)->diffInDays($end, absolute: true) + 1])"
            />
            <x-mailcoach::statistic
                :stat="$readyToLoad ? number_format($growthRate, 2) : '—'"
                :label="__mc('Growth Rate')"
                suffix="%"
            />
        </div>
    </x-mailcoach::card>

<x-mailcoach::card>
    <div class="flex flex-col sm:flex-row gap-4 sm:items-center mb-8">
        <x-mailcoach::date-field
            min-date=""
            max-date="{{ $end }}"
            position="auto"
            name="start"
            wire:model.live="start"
            label="{{ __mc('From') }}"
            class="flex-row gap-0"
            inputClass="w-40"
        />
        <x-mailcoach::date-field
            min-date="{{ $start }}"
            max-date="{{ now()->format('Y-m-d') }}"
            position="auto"
            name="end"
            wire:model.live="end"
            label="{{ __mc('To') }}"
            class="flex-row gap-0"
            inputClass="w-40"
        />
    </div>
    @if ($readyToLoad)
        <div wire:loading.class.delay.long="opacity-50" wire:target="start,end" x-data="emailListStatisticsChart" x-init="renderChart({
            labels: @js($stats->pluck('label')->values()->toArray()),
            subscribers: @js($stats->pluck('subscribers')->values()->toArray()),
            subscribes: @js($stats->pluck('subscribes')->values()->toArray()),
            unsubscribes: @js($stats->pluck('unsubscribes')->values()->toArray()),
        })">
            <canvas id="chart-summary" style="position: relative; max-height:300px; width:100%; max-width: 100%;"></canvas>
            <div class="mt-4 text-right">
                <small class="text-gray-500 text-sm">{{ __mc('You can drag the chart to zoom.') }}</small>
                <a x-show="zoomed" x-cloak class="text-gray-500 text-sm underline" href="#" x-on:click.prevent="resetZoom">Reset zoom</a>
            </div>
        </div>
    @else
        <div class="min-h-[21rem] flex items-center justify-center">
            {{ __mc('Loading...') }}
        </div>
    @endif
</x-mailcoach::card>

<h2 class="text-xl font-medium">{{ __mc('Unsubscribes') }}</h2>
<x-mailcoach::card class="py-0 sm:py-6">
    <div class="grid sm:grid-cols-3 divide-y sm:divide-x sm:divide-y-0 divide-snow">
        <x-mailcoach::statistic
            :href="route('mailcoach.emailLists.subscribers', $emailList) . '?tableFilters[status][value]=unsubscribed'"
            class="col-start-1"
            :stat="$readyToLoad ? number_format($totalUnsubscribeCount) : '—'"
            :label="__mc('Total unsubscribes')"
        />
        <x-mailcoach::statistic
            :stat="$readyToLoad
                    ? ($startUnsubscribeCount > 0 ? '+' : '') . number_format($startUnsubscribeCount)
                    : '—'"
            :label="__mc('Change in last :daterange days', ['daterange' => \Illuminate\Support\Facades\Date::parse($start)->diffInDays($end, absolute: true) + 1])"
        />
        <x-mailcoach::statistic
            :stat="$readyToLoad ? number_format($churnRate, 2) : '—'"
            :label="__mc('Churn Rate')"
            suffix="%"
        />
    </div>
</x-mailcoach::card>
<livewire:mailcoach::list-tracking-statistics :email-list="$emailList" />
</div>
