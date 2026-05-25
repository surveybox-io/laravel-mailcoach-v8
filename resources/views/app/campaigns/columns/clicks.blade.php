@php(
    /** @var \Spatie\Mailcoach\Domain\Campaign\Models\Campaign $campaign */
    $campaign = $getRecord()
)

<div class="fi-ta-text-item px-3 tabular-nums text-right">
    @if($campaign->clickCount())
        {{ number_format($campaign->uniqueClickCount()) }}
        <span class="text-xs text-navy-bleak-extra-light w-9 inline-block text-left">&nbsp;{{ round($campaign->clickRate() / 100, 2) }}%</span>
    @else
        &ndash;
    @endif
</div>
