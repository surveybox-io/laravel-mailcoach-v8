<?php

namespace Spatie\Mailcoach\Livewire\Webhooks;

use Closure;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Spatie\Mailcoach\Domain\Settings\Models\WebhookConfiguration;
use Spatie\Mailcoach\Livewire\TableComponent;

class WebhooksComponent extends TableComponent
{
    public function getTitle(): string
    {
        return __mc('Webhooks');
    }

    public function getLayout(): string
    {
        return 'mailcoach::app.layouts.settings';
    }

    public function getLayoutData(): array
    {
        return [
            'title' => __mc('Webhooks'),
            'create' => 'webhook',
            'createText' => __mc('Create webhook'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return self::getWebhookConfigurationClass()::query();
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return __mc('No webhooks configurations. You can use webhooks to get notified immediately when certain events (such as subscriptions) occur.');
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-s-arrow-top-right-on-square';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Action::make('docs')
                ->label(__mc('Learn more about webhooks'))
                ->link()
                ->url('https://mailcoach.app/resources/learn-mailcoach/advanced/webhooks')
                ->openUrlInNewTab(),
        ];
    }

    public function deleteWebhook(WebhookConfiguration $webhook)
    {
        $webhook->delete();

        notify(__mc('Webhook :webhook successfully deleted', ['webhook' => $webhook->name]));
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__mc('Name'))
                ->searchable()
                ->extraAttributes(['class' => 'link'])
                ->sortable(),
            IconColumn::make('enabled')
                ->label(__mc('Enabled'))
                ->boolean()
                ->sortable(),
            IconColumn::make('use_for_all_lists')
                ->label(__mc('All lists'))
                ->boolean()
                ->sortable(),
            TextColumn::make('events')
                ->label(__mc('Events')),
            TextColumn::make('failed_attempts')
                ->label(__mc('Failed attempts'))
                ->numeric()
                ->extraAttributes([
                    'class' => 'tabular-nums',
                ]),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('disable')
                    ->label(__mc('Disable'))
                    ->hidden(fn (WebhookConfiguration $record) => ! $record->enabled)
                    ->color('warning')
                    ->icon('heroicon-s-x-circle')
                    ->action(fn (WebhookConfiguration $record) => $record->update(['enabled' => false])),
                Action::make('enable')
                    ->label(__mc('Enable'))
                    ->hidden(fn (WebhookConfiguration $record) => $record->enabled)
                    ->color('success')
                    ->icon('heroicon-s-check-circle')
                    ->action(fn (WebhookConfiguration $record) => $record->update(['enabled' => true])),
                Action::make('delete')
                    ->icon('heroicon-s-trash')
                    ->color('danger')
                    ->label(__mc('Delete'))
                    ->requiresConfirmation()
                    ->modalHeading(fn (WebhookConfiguration $record) => __mc('Delete :resource', ['resource' => __mc('webhook')]))
                    ->modalDescription(fn (WebhookConfiguration $record) => new HtmlString(__mc('Are you sure you want to delete :resource<br/><strong>:name</strong>?', [
                        'resource' => __mc('webhook'),
                        'name' => $record->name,
                    ])))
                    ->action(fn (WebhookConfiguration $record) => $this->deleteWebhook($record)),
            ]),
        ];
    }

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return fn (WebhookConfiguration $record) => route('webhooks.edit', $record);
    }
}
