@php($mail = $getRecord())
<div class="fi-ta-text-item px-3 tabular-nums text-right">
    @if($mail->contentItem->open_rate)
        {{ number_format($mail->contentItem->unique_open_count) }}
        <span class="text-xs text-navy-bleak-extra-light w-9 inline-block text-left">&nbsp;{{ round($mail->contentItem->open_rate / 100, 2) }}%</span>
    @else
        &ndash;
    @endif
</div>
