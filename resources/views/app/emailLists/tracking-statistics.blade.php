<section class="card-grid" wire:init="loadData">
    @if (! $readyToLoad || $chart?->count())
        <h2 class="text-xl font-medium">{{ __mc('Recent campaigns') }}</h2>
        <x-mailcoach::card>
            @if ($readyToLoad && $chart?->count())
                <div x-data="emailListTrackingStatisticsChart" x-init="renderChart({
                    labels: @js($chart->pluck('label')->values()->toArray()),
                    openRate: @js($chart->pluck('openRate')->values()->toArray()),
                    clickRate: @js($chart->pluck('clickRate')->values()->toArray()),
                    unsubscribeRate: @js($chart->pluck('unsubscribeRate')->values()->toArray()),
                    bounceRate: @js($chart->pluck('bounceRate')->values()->toArray()),
                })">
                    <canvas id="chart-tracking" style="position: relative; max-height:300px; width:100%; max-width: 100%;"></canvas>
                </div>
            @elseif (! $readyToLoad)
                <div class="min-h-[21rem] flex items-center justify-center">
                    {{ __mc('Loading...') }}
                </div>
            @endif
        </x-mailcoach::card>
    @endif
    <h2 class="text-xl font-medium">{{ __mc('List averages') }}</h2>
    <x-mailcoach::card class="py-0 sm:py-6 grid gap-12">
        <div class="grid sm:grid-cols-4 divide-y sm:divide-x sm:divide-y-0 divide-snow">
            <x-mailcoach::statistic :stat="$readyToLoad ? number_format($averageOpenRate, 2) : '—'" :label="__mc('Average Open Rate')" suffix="%"/>
            <x-mailcoach::statistic :stat="$readyToLoad ? number_format($averageClickRate, 2) : '—'" :label="__mc('Average Click Rate')" suffix="%"/>
            <x-mailcoach::statistic :stat="$readyToLoad ? number_format($averageUnsubscribeRate, 2) : '—'" :label="__mc('Average Unsubscribe Rate')" suffix="%"/>
            <x-mailcoach::statistic :stat="$readyToLoad ? number_format($averageBounceRate, 2) : '—'" :label="__mc('Average Bounce Rate')" suffix="%"/>
        </div>
    </x-mailcoach::card>
</section>
