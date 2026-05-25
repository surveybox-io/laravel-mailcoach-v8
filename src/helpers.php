<?php

use Filament\Notifications\Notification;
use Spatie\Mailcoach\Mailcoach;

function __mc(string $key, array $replace = [], ?string $locale = null): string
{
    $result = __('mailcoach::mailcoach.'.$key, $replace, $locale);

    return str_replace('mailcoach::mailcoach.', '', $result);
}

function __mc_choice(string $key, int $number, array $replace = [], ?string $locale = null): string
{
    $result = trans_choice('mailcoach::mailcoach.'.$key, $number, $replace, $locale);

    return str_replace('mailcoach::mailcoach.', '', $result);
}

function database_date_format_function(string $column, string $format): string
{
    $connection = Mailcoach::getDatabaseConnection() ?: config('database.default');
    $driver = config("database.connections.{$connection}.driver");

    if ($driver === 'pgsql') {
        $format = match ($format) {
            '%Y-%m-%d %H:%i' => 'YYYY-MM-DD HH24:MI',
            '%Y-%m-%d' => 'YYYY-MM-DD',
        };

        return "TO_CHAR({$column}, '{$format}')";
    }

    if ($driver === 'sqlite') {
        $format = match ($format) {
            '%Y-%m-%d %H:%i' => '%Y-%m-%d %H:%M',
            '%Y-%m-%d' => '%Y-%m-%d',
        };

        return "strftime('{$format}', {$column})";
    }

    return "DATE_FORMAT({$column}, '{$format}')";
}

function notify(string $message, string $level = 'success'): void
{
    Notification::make()->title($message)->color($level)->send();
}

function notifySuccess(string $message): void
{
    notify($message);
}

function notifyWarning(string $message): void
{
    notify($message, 'warning');
}

function notifyError(string $message): void
{
    notify($message, 'error');
}

function containsMjml(?string $html): bool
{
    return str_starts_with(trim($html), '<mjml');
}

function emptyToNull(?string $value): ?string
{
    return ($value === '') ? null : $value;
}
