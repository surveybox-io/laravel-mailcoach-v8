<?php

namespace Spatie\Mailcoach\Domain\Editor\EditorJs;

use Illuminate\Contracts\View\View;
use Spatie\Mailcoach\Domain\Editor\EditorJs\Renderer\Renderer;
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
                        'json' => $this->templateFieldValues[$placeHolderName] ?? '',
                    ];
                }

                $this->templateFieldValues[$placeHolderName]['html'] ??= '';
                $this->templateFieldValues[$placeHolderName]['json'] ??= '';
            }
        } else {
            if (! is_array($this->templateFieldValues['html'] ?? '')) {
                $this->templateFieldValues['html'] = [
                    'json' => $this->templateFieldValues['html'] ?? '',
                ];
            }

            $this->templateFieldValues['html']['html'] ??= '';
            $this->templateFieldValues['html']['json'] ??= '';
        }

        return view('mailcoach::editors.editorjs.editor');
    }

    public function renderFullHtml(): void
    {
        $this->templateFieldValues = collect($this->templateFieldValues)
            ->map(function ($value) {
                if (! is_array($value)) {
                    return $value;
                }

                if (! isset($value['json']['blocks'])) {
                    return $value;
                }

                $value['html'] = self::renderBlocks($value['json']['blocks']);

                return $value;
            })->all();

        parent::renderFullHtml();
    }

    public static function renderBlocks(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $rendererClass = config("mailcoach.editor.editor_js.renderers.{$block['type']}");

            if ($rendererClass && is_subclass_of($rendererClass, Renderer::class)) {
                $renderer = new $rendererClass($block['data']);
                $html .= $renderer->render();
                $html .= "\n";
            }
        }

        // Replace this in the generated html as Editor.js likes to automatically add the protocol to links
        return str_replace('http://::', '::', $html);
    }
}
