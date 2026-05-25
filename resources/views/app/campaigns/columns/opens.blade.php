@php(
    /** @var \Spatie\Mailcoach\Domain\Campaign\Models\Campaign $campaign */
    $campaign = $getRecord()
)
<div class="fi-ta-text-item px-3 tabular-nums text-right">
    @if (! $campaign->openCount())
        &ndash;
    @else
        {{ number_format($campaign->uniqueOpenCount()) }}
        <span class="text-xs text-navy-bleak-extra-light w-9 inline-block text-left">&nbsp;{{ round($campaign->openRate() / 100, 2) }}%</span>
    @endif
</div>
