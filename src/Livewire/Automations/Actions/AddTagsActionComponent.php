<?php

namespace Spatie\Mailcoach\Livewire\Automations\Actions;

use Spatie\Mailcoach\Livewire\Automations\AutomationActionComponent;
use Spatie\ValidationRules\Rules\Delimited;

class AddTagsActionComponent extends AutomationActionComponent
{
    public string $tags = '';

    public function getData(): array
    {
        $tags = explode(',', $this->tags);

        $tags = array_map('trim', $tags);

        $tags = implode(',', $tags);

        return [
            'tags' => $tags,
        ];
    }

    public function rules(): array
    {
        return [
            'tags' => ['required', new Delimited('string')],
        ];
    }

    public function render()
    {
        return view('mailcoach::app.automations.components.actions.addTagsAction');
    }
}
