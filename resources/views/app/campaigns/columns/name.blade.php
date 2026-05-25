@php($campaign = $getRecord())
<div class="fi-ta-text-item gap-1.5 px-3 items-center">
    <span class="link max-w-2xl truncate inline-block">{{ $campaign->name }}</span>
    @if ($failedCount = $campaign->failCount())
        <span class="ml-2 text-orange text-xs inline-block relative" style="bottom: 5px">
            {{ $failedCount }} {{ __mc_choice('failed send|failed sends', $failedCount) }}
        </span>
    @endif
</div>
