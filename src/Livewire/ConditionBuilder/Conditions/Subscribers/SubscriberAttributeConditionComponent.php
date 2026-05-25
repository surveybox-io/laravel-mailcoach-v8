<?php

namespace Spatie\Mailcoach\Livewire\ConditionBuilder\Conditions\Subscribers;

use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Spatie\Mailcoach\Livewire\ConditionBuilder\ConditionComponent;

class SubscriberAttributeConditionComponent extends ConditionComponent
{
    public function mount(): void
    {
        parent::mount();

        $this->changeLabels();

        $this->storedCondition['value']['attribute'] ??= '';
        $this->storedCondition['value']['value'] ??= null;
    }

    #[On('tags-updated')]
    public function updateTags(array|string ...$tags): void
    {
        $this->storedCondition['value']['value'] = Arr::wrap($tags);
        $this->dispatch('storedConditionUpdated', $this->index, $this->storedCondition);
    }

    public function changeLabels(): void
    {
        foreach ($this->storedCondition['condition']['comparison_operators'] as $operator => $label) {
            $newLabel = match ($operator) {
                'greater-than-or-equals' => __mc('Greater than or equals'),
                'smaller-than-or-equals' => __mc('Smaller than or equals'),
                default => $label,
            };

            $this->storedCondition['condition']['comparison_operators'][$operator] = $newLabel;
        }
    }

    public function render()
    {
        return view('mailcoach::app.conditionBuilder.conditions.subscribers.subscriberAttributeCondition');
    }
}
