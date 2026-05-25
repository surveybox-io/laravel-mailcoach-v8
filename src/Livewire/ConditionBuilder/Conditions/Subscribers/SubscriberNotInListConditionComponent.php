<?php

namespace Spatie\Mailcoach\Livewire\ConditionBuilder\Conditions\Subscribers;

use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Livewire\ConditionBuilder\ConditionComponent;

class SubscriberNotInListConditionComponent extends ConditionComponent
{
    public EmailList $emailList;

    public array $options = [];

    public function mount(): void
    {
        parent::mount();

        $this->options = self::getEmailListClass()::query()
            ->where('id', '!=', $this->emailList->id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function render()
    {
        return view('mailcoach::app.conditionBuilder.conditions.subscribers.subscriberNotInListCondition');
    }
}
