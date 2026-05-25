<?php

namespace Spatie\Mailcoach\Domain\Editor\Unlayer;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Validation\Rule;
use Spatie\Mailcoach\Domain\Settings\Support\EditorConfiguration\Editors\EditorConfigurationDriver;

class UnlayerEditorConfigurationDriver extends EditorConfigurationDriver
{
    public static function label(): string
    {
        return 'Unlayer';
    }

    public function getClass(): string
    {
        return Editor::class;
    }

    public function validationRules(): array
    {
        return [
            'project_id' => ['nullable', 'string'],
            'text_direction' => ['nullable', 'string', Rule::in('ltr', 'rtl')],
        ];
    }

    public function defaults(): array
    {
        return [
            'project_id' => '',
            'text_direction' => 'ltr',
        ];
    }

    public function registerConfigValues(Repository $config, array $values): void
    {
        parent::registerConfigValues($config, $values);

        config()->set('mailcoach.unlayer.options.projectId', $values['project_id'] ?? null);
        config()->set('mailcoach.unlayer.options.textDirection', $values['text_direction'] ?? 'ltr');
    }

    public static function settingsPartial(): ?string
    {
        return 'mailcoach::app.configuration.editor.partials.unlayer';
    }
}
