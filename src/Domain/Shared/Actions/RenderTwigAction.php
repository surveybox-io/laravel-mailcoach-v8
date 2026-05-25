<?php

namespace Spatie\Mailcoach\Domain\Shared\Actions;

use Throwable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class RenderTwigAction
{
    public function execute(string $html, array $context = [], bool $throw = true): string
    {
        try {
            $twig = new Environment(new ArrayLoader, [
                'autoescape' => 'html',
            ]);

            $html = rawurldecode($html);

            /** This is in case an editor urlencodes {{ variable }} to {{+variable+}} */
            $html = str_replace(['{{+', '+}}'], ['{{ ', ' }}'], $html);

            return $twig->createTemplate($html)->render($context);
        } catch (Throwable $e) {
            if ($throw) {
                throw $e;
            }

            report($e);

            return $html;
        }
    }
}
