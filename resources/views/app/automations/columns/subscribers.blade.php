@php($automation = $getRecord())

<div  class="fi-ta-text-item inline-flex items-center gap-1.5 px-3">
    <livewire:mailcoach::automation-count wire:key="{{ \Illuminate\Support\Str::random() }}" :automation="$automation" lazy />
</div>
