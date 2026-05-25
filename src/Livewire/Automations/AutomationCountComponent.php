<?php

namespace Spatie\Mailcoach\Livewire\Automations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Mailcoach\Domain\Automation\Models\Automation;

class AutomationCountComponent extends Component
{
    public int $result;

    public function mount(Automation $automation)
    {
        $this->result = Cache::remember(
            "automation-count:{$automation->uuid}a",
            now()->addMinute(),
            fn () => $automation->actionSubscribers()->select('subscriber_id')->count(DB::raw('distinct subscriber_id')),
        );
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <span>…</span>
        HTML;
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
