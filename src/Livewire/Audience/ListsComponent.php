<?php

namespace Spatie\Mailcoach\Livewire\Audience;

use Closure;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Spatie\Mailcoach\Domain\Audience\Actions\EmailLists\DuplicateEmailListAction;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Livewire\TableComponent;

class ListsComponent extends TableComponent
{
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__mc('Name'))
                ->sortable()
                ->searchable(self::getEmailListClass()::count() > $this->getTableRecordsPerPageSelectOptions()[0])
                ->view('mailcoach::app.emailLists.columns.name'),
            TextColumn::make('from')
                ->label(__mc('From'))
                ->html()
                ->getStateUsing(fn (EmailList $record) => <<<"html"
                    {$record->default_from_name} <span class="text-xs text-navy-bleak-extra-light">{$record->default_from_email}</span>
                html),
            TextColumn::make('reply_to')
                ->label(__mc('Reply to'))
                ->html()
                ->getStateUsing(fn (EmailList $record) => <<<"html"
                    {$record->default_reply_to_name} <span class="text-xs text-navy-bleak-extra-light">{$record->default_reply_to_email}</span>
                html),
            TextColumn::make('active_subscribers_count')
                ->label(__mc('Subscribers'))
                ->numeric()
                ->extraAttributes([
                    'class' => 'tabular-nums',
                ])
                ->alignRight()
                /** @phpstan-ignore-next-line The query adds this field */
                ->view('mailcoach::app.emailLists.columns.subscribers'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('Duplicate')
                    ->visible(fn (EmailList $record) => Auth::user()->can('create', self::getEmailListClass()))
                    ->action(function (EmailList $record) {
                        $this->duplicateEmailList($record);
                    })
                    ->icon('heroicon-s-document-duplicate')
                    ->label(__mc('Duplicate')),
                Action::make('Delete')
                    ->visible(fn (EmailList $record) => Auth::user()->can('delete', $record))
                    ->action(function (EmailList $record) {
                        $this->authorize('delete', $record);

                        $record->delete();

                        notify(__mc('List :list was deleted.', ['list' => $record->name]));
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (EmailList $record) => __mc('Delete :resource', ['resource' => __mc('list')]))
                    ->modalDescription(fn (EmailList $record) => new HtmlString(__mc('Are you sure you want to delete :resource<br/><strong>:name</strong>?', [
                        'resource' => __mc('list'),
                        'name' => $record->name,
                    ])))
                    ->label(__mc('Delete'))
                    ->icon('heroicon-s-trash')
                    ->color('danger'),
            ]),
        ];
    }

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return function (EmailList $record) {
            return route('mailcoach.emailLists.summary', $record);
        };
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'name';
    }

    public function mount(): void
    {
        $this->authorize('viewAny', static::getEmailListClass());
    }

    protected function getTableQuery(): Builder
    {
        return self::getEmailListClass()::query();
    }

    public function getTitle(): string
    {
        return __mc('Lists');
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return __mc('No lists');
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-s-user-group';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Action::make('learn')
                ->url('https://mailcoach.app/resources/learn-mailcoach/features/email-lists')
                ->label(__mc('Learn more about email lists'))
                ->openUrlInNewTab()
                ->link(),
        ];
    }

    public function duplicateEmailList(EmailList $emailList): void
    {
        $this->authorize('create', self::getCampaignClass());

        $duplicateEmailList = app(DuplicateEmailListAction::class)->execute($emailList);

        notify(__mc('EmailList :emailList was created.', ['emailList' => $emailList->name]));

        $this->redirect(route('mailcoach.emailLists.general-settings', $duplicateEmailList));
    }

    protected function getTableEmptyStateDescription(): string
    {
        return __mc('You\'ll need at least one list to gather subscribers.');
    }

    public function getLayoutData(): array
    {
        if (! Auth::guard(config('mailcoach.guard'))->user()->can('create', self::getEmailListClass())) {
            return [
                'hideBreadcrumbs' => true,
            ];
        }

        return [
            'create' => 'list',
            'createText' => __mc('Create list'),
            'hideBreadcrumbs' => true,
        ];
    }
}
