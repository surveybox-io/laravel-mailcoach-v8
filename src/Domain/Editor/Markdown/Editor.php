<?php

namespace Spatie\Mailcoach\Domain\Editor\Markdown;

use Illuminate\Contracts\View\View;
use Spatie\Mailcoach\Domain\Shared\Actions\RenderMarkdownToHtmlAction;
use Spatie\Mailcoach\Domain\Template\Support\TemplateRenderer;
use Spatie\Mailcoach\Livewire\Editor\EditorComponent;

class Editor extends EditorComponent
{
    public static bool $supportsTemplates = false;

    public function render(): View
    {
        if (! $this->templateId) {
            $template = self::getTemplateClass()::first();

            $this->templateId = $template?->id;
            $this->template = $template;
        }

        if ($this->template?->containsPlaceHolders()) {
            foreach ($this->template->placeHolderNames() as $placeHolderName) {
                if (! is_array($this->templateFieldValues[$placeHolderName] ?? '')) {
                    $this->templateFieldValues[$placeHolderName] = [
                        'markdown' => $this->templateFieldValues[$placeHolderName] ?? '',
                    ];
                }

                $this->templateFieldValues[$placeHolderName]['html'] ??= '';
                $this->templateFieldValues[$placeHolderName]['markdown'] ??= '';
                $this->templateFieldValues[$placeHolderName]['theme'] ??= 'nord';
            }
        } else {
            if (! is_array($this->templateFieldValues['html'] ?? '')) {
                $this->templateFieldValues['html'] = [
                    'markdown' => $this->templateFieldValues['html'] ?? '',
                ];
            }

            $this->templateFieldValues['html']['html'] ??= '';
            $this->templateFieldValues['html']['markdown'] ??= '';
            $this->templateFieldValues['html']['theme'] ??= 'nord';
        }

        return view('mailcoach::editors.markdown.editor');
    }

    public function renderFullHtml(): void
    {
        $this->templateFieldValues = collect($this->templateFieldValues)
            ->map(function ($value) {
                if (! is_array($value) || ! isset($value['markdown'])) {
                    return $value;
                }

                $html = (string) app(RenderMarkdownToHtmlAction::class)->execute(
                    markdown: $value['markdown'],
                    theme: $value['theme'] ?? 'nord',
                );

                $value['html'] = $html;

                return $value;
            })->toArray();

        if (! $this->template) {
            $this->fullHtml = $this->templateFieldValues['html']['html'] ?? '';

            return;
        }

        parent::renderFullHtml();

        $templateRenderer = (new TemplateRenderer($this->template->html ?? ''));
        $this->fullHtml = $templateRenderer->render(collect($this->templateFieldValues)->map(function ($values) {
            if (is_string($values)) {
                return $values;
            }

            return $values['html'] ?? '';
        })->toArray());
    }
}
