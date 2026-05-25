<?php

namespace Spatie\Mailcoach\Livewire\ConditionBuilder\Conditions\Subscribers;

use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Campaign\Rules\DateTimeFieldRule;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ComparisonOperator;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\EngagementType;
use Spatie\Mailcoach\Livewire\ConditionBuilder\ConditionComponent;

class SubscriberEngagementConditionComponent extends ConditionComponent
{
    public EmailList $emailList;

    public array $date;

    public CarbonInterface $date_parsed;

    public function mount(): void
    {
        parent::mount();

        $this->storedCondition['value']['type'] ??= EngagementType::OpenRate->value;
        $this->storedCondition['value']['value'] ??= null;
        $this->date_parsed = Date::parse($this->storedCondition['value']['date'] ?? now()->setTimezone(config('mailcoach.timezone') ?? config('app.timezone'))->startOfHour());
        $this->date = [
            'date' => $this->date_parsed->format('Y-m-d'),
            'hours' => (int) $this->date_parsed->format('H'),
            'minutes' => (int) $this->date_parsed->format('i'),
        ];

        $this->changeLabels();
    }

    public function updated(): void
    {
        $this->storedCondition['value']['date'] = (new DateTimeFieldRule)->parseDateTime($this->date);

        parent::updated();
    }

    public function changeLabels(): void
    {
        foreach ($this->storedCondition['condition']['comparison_operators'] as $operator => $label) {
            $newLabel = match ($operator) {
                ComparisonOperator::SmallerThanOrEquals->value => __mc('Smaller than or equals'),
                ComparisonOperator::GreaterThanOrEquals->value => __mc('Greater than or equals'),
                default => $label,
            };

            $this->storedCondition['condition']['comparison_operators'][$operator] = $newLabel;
        }
    }

    public function render(): View
    {
        return view('mailcoach::app.conditionBuilder.conditions.subscribers.subscriberEngagementCondition');
    }
}
