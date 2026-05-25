@php(
    /** @var \Spatie\Mailcoach\Domain\TransactionalMail\Models\TransactionalMail $template */
    $template = $getRecord()
)
<div class="fi-ta-text-item px-3 tabular-nums text-right">
    @if (! $template->openCount())
        &ndash;
    @else
        {{ number_format($template->uniqueOpenCount()) }}
        <span class="text-xs text-navy-bleak-extra-light w-9 inline-block text-left">&nbsp;{{ round($template->openRate() / 100, 2) }}%</span>
    @endif
</div>
