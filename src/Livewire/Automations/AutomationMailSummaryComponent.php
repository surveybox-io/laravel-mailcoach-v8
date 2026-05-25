<?php

namespace Spatie\Mailcoach\Livewire\Automations;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Spatie\Mailcoach\Domain\Automation\Models\AutomationMail;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\MainNavigation;

class AutomationMailSummaryComponent extends Component
{
    use AuthorizesRequests;
    use UsesMailcoachModels;

    public AutomationMail $mail;

    public int $failedSendsCount = 0;

    public function mount(AutomationMail $automationMail)
    {
        $this->mail = $automationMail;

        $this->authorize('view', $this->mail);

        app(MainNavigation::class)->activeSection()?->add($this->mail->name, route('mailcoach.automations'));
    }

    public function render(): View
    {
        $this->failedSendsCount = $this->mail->contentItem->sends()->failed()->count();

        return view('mailcoach::app.automations.mails.summary')
            ->layout('mailcoach::app.automations.mails.layouts.automationMail', [
                'title' => __mc('Performance'),
                'mail' => $this->mail,
            ]);
    }
}
