@php(
    /** @var \Spatie\Mailcoach\Domain\TransactionalMail\Models\TransactionalMail $template */
    $template = $getRecord()
)

<div class="fi-ta-text-item px-3 tabular-nums text-right">
    @if($template->clickCount())
        {{ number_format($template->uniqueClickCount()) }}
        <span class="text-xs text-navy-bleak-extra-light w-9 inline-block text-left">&nbsp;{{ round($template->clickRate() / 100, 2) }}%</span>
    @else
        &ndash;
    @endif
</div>
