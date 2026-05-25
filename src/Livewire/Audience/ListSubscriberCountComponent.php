<?php

namespace Spatie\Mailcoach\Livewire\Audience;

use Livewire\Component;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;

class ListSubscriberCountComponent extends Component
{
    public ?int $result = null;

    public EmailList $emailList;

    public function mount(EmailList $emailList): void
    {
        $this->emailList = $emailList;
        $this->result = $this->emailList->subscribers()->count();
    }

    public function placeholder(): string
    {
        return <<<'blade'
            <span class="animate-pulse">
                ...
            </span>
        blade;
    }

    public function render(): string
    {
        return <<<'blade'
            <span title="{{ number_format($result) }}">
                {{ Str::shortNumber($result) }}
            </span>
        blade;
    }
}
