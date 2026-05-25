@php(
    /** @var \Spatie\Mailcoach\Domain\Campaign\Models\Campaign $campaign */
    $campaign = $getRecord()
)
<div class="fi-ta-text-item inline-flex items-center gap-1.5 px-3 tabular-nums text-right">
    @if (! $campaign->isCancelled() && $campaign->sentToNumberOfSubscribers())
        {{ number_format($campaign->sentToNumberOfSubscribers()) }}
    @elseif ($sentSendsCount = $campaign->contentItems()->withCount('sentSends')->get()->sum(fn (\Spatie\Mailcoach\Domain\Content\Models\ContentItem $contentItem) => $contentItem->sent_sends_count))
        {{ number_format($sentSendsCount) }}
    @else
        &ndash;
    @endif
</div>
