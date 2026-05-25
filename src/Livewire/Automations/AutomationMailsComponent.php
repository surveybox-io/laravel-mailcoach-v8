<?php

namespace Spatie\Mailcoach\Livewire\Automations;

use Closure;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Spatie\Mailcoach\Domain\Automation\Models\AutomationMail;
use Spatie\Mailcoach\Livewire\TableComponent;

class AutomationMailsComponent extends TableComponent
{
    protected function getTableQuery(): Builder
    {
        return self::getAutomationMailClass()::query();
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'name';
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->sortable()
                ->searchable($this->getAutomationMailCount() > $this->getTableRecordsPerPageSelectOptions()[0])
                ->label(__mc('Name'))
                ->size('base')
                ->extraAttributes(['class' => 'link']),
            TextColumn::make('contentItem.sent_to_number_of_subscribers')
                ->sortable()
                ->label(__mc('Emails'))
                ->width(0)
                ->numeric()
                ->extraAttributes([
                    'class' => 'tabular-nums',
                ])
                ->size('base')
                ->getStateUsing(fn (AutomationMail $record) => number_format($record->contentItem->sent_to_number_of_subscribers) ?: '–'),
            TextColumn::make('contentItem.unique_open_count')
                ->sortable()
                ->label(__mc('Opens'))
                ->width(0)
                ->numeric()
                ->extraAttributes([
                    'class' => 'tabular-nums',
                ])
                ->view('mailcoach::app.automations.mails.columns.opens'),
            TextColumn::make('contentItem.unique_click_count')
                ->sortable()
                ->label(__mc('Clicks'))
                ->width(0)
                ->numeric()
                ->extraAttributes([
                    'class' => 'tabular-nums',
                ])
                ->view('mailcoach::app.automations.mails.columns.clicks'),
            TextColumn::make('created_at')
                ->alignRight()
                ->width(0)
                ->sortable()
                ->label(__mc('Created'))
                ->size('base')
                ->extraAttributes([
                    'class' => 'tabular-nums',
                ])
                ->date(config('mailcoach.date_format'), config('mailcoach.timezone')),
        ];
    }

    protected function getTableFilters(): array
    {
        if ($this->getAutomationMailCount() <= $this->getTableRecordsPerPageSelectOptions()[0]) {
            return [];
        }

        return [
            SelectFilter::make('automation_uuid')
                ->label(__mc('Automation'))
                ->options(fn () => self::getAutomationClass()::pluck('name', 'uuid'))
                ->multiple()
                ->query(function (Builder $query, array $data) {
                    if (! $data['values']) {
                        return;
                    }

                    /** @var \Illuminate\Database\Eloquent\Builder<AutomationMail> $query */
                    $query->inAutomations($data['values']);
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('Duplicate')
                    ->visible(fn (AutomationMail $record) => Auth::user()->can('create', self::getAutomationMailClass()))
                    ->action(fn (AutomationMail $record) => $this->duplicateAutomationMail($record))
                    ->icon('heroicon-s-document-duplicate')
                    ->label(__mc('Duplicate'))
                    ->hidden(fn (AutomationMail $record) => ! Auth::guard(config('mailcoach.guard'))->user()->can('create', self::getAutomationMailClass())),
                Action::make('Delete')
                    ->visible(fn (AutomationMail $record) => Auth::user()->can('delete', $record))
                    ->action(function (AutomationMail $record) {
                        $record->delete();
                        notify(__mc('Automation email :automationMail was deleted.', ['automationMail' => $record->name]));
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (AutomationMail $record) => __mc('Delete :resource', ['resource' => __mc('automation mail')]))
                    ->modalDescription(fn (AutomationMail $record) => new HtmlString(__mc('Are you sure you want to delete :resource<br/><strong>:name</strong>?', [
                        'resource' => __mc('automation mail'),
                        'name' => $record->name,
                    ])))
                    ->label(__mc('Delete'))
                    ->icon('heroicon-s-trash')
                    ->color('danger')
                    ->hidden(fn (AutomationMail $record) => ! Auth::guard(config('mailcoach.guard'))->user()->can('delete', self::getAutomationMailClass())),
            ]),
        ];
    }

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return function (AutomationMail $record) {
            return route('mailcoach.automations.mails.summary', $record);
        };
    }

    public function duplicateAutomationMail(AutomationMail $automationMail)
    {
        $this->authorize('create', $automationMail);

        /** @var AutomationMail $newAutomationMail */
        $newAutomationMail = self::getAutomationMailClass()::create([
            'name' => __mc('Duplicate of').' '.$automationMail->name,
        ]);

        $newAutomationMail->contentItem->update([
            'subject' => $automationMail->contentItem->subject,
            'template_id' => $automationMail->contentItem->template_id,
            'html' => $automationMail->contentItem->html,
            'structured_html' => $automationMail->contentItem->structured_html,
            'webview_html' => $automationMail->contentItem->webview_html,
            'utm_tags' => $automationMail->contentItem->utm_tags,
        ]);

        notify(__mc('Email :name was created.', ['name' => $newAutomationMail->name]));

        return redirect()->route('mailcoach.automations.mails.settings', $newAutomationMail);
    }

    protected function getAutomationMailCount(): int
    {
        return once(fn () => self::getAutomationMailClass()::count());
    }

    public function getTitle(): string
    {
        return __mc('Emails');
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return __mc('No emails');
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return __mc('You haven\'t created any automation emails.');
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-s-envelope';
    }

    public function getLayoutData(): array
    {
        return [
            'create' => 'automation-mail',
            'createText' => __mc('Create automation mail'),
        ];
    }
}
