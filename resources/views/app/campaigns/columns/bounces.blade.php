@php(
    /** @var \Spatie\Mailcoach\Domain\Campaign\Models\Campaign $campaign */
    $campaign = $getRecord()
)

<div class="fi-ta-text-item px-3 tabular-nums text-right">
    {{ number_format($campaign->bounceCount()) }}
    <span class="text-xs text-navy-bleak-extra-light w-9 inline-block text-left">&nbsp;{{ round($campaign->bounceRate() / 100, 2) }}%</span>
</div>
