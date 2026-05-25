@php($emailList = $getRecord())

<div  class="fi-ta-text-item inline-flex items-center gap-1.5 px-3">
    <livewire:mailcoach::list-subscriber-count wire:key="{{ \Illuminate\Support\Str::random() }}" :email-list="$emailList" lazy />
</div>
