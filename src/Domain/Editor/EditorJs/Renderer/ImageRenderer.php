<?php

namespace Spatie\Mailcoach\Domain\Editor\EditorJs\Renderer;

class ImageRenderer extends Renderer
{
    public function render(): string
    {
        $classes = 'image';

        if ($this->data['withBorder'] ?? false) {
            $classes .= ' border';
        }

        if ($this->data['stretched'] ?? false) {
            $classes .= ' stretched';
        }

        if ($this->data['withBackground'] ?? false) {
            $classes .= ' background';
        }

        $classes = trim($classes);

        $caption = '';

        if (trim($this->data['caption'])) {
            $caption = <<<HTML
                <p class="caption">{$this->data['caption']}</p>
            HTML;
        }

        return <<<HTML
        <div class="{$classes}">
            <img src="{$this->data['file']['url']}" alt="">
            {$caption}
        </div>
        HTML;
    }
}
