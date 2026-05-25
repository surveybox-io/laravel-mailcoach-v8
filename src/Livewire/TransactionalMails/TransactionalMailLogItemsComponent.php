<?php

namespace Spatie\Mailcoach\Livewire\TransactionalMails;

use Auth;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Spatie\Mailcoach\Domain\TransactionalMail\Models\TransactionalMailLogItem;
use Spatie\Mailcoach\Livewire\TableComponent;

class TransactionalMailLogItemsComponent extends TableComponent
{
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableQuery(): Builder
    {
        return self::getTransactionalMailLogItemClass()::query()
            ->with(['contentItem' => function (Builder $query) {
                $query->withCount(['opens', 'clicks']);
            }, 'contentItem.sends' => function (Builder $query) {
                $query->withCount(['bounces', 'complaints']);
            }]);
    }

    protected function getTableColumns(): array
    {
        $searchable = self::getTransactionalMailLogItemClass()::count() > $this->getTableRecordsPerPageSelectOptions()[0];

        return [
            IconColumn::make('fake')
                ->label('')
                ->alignCenter()
                ->icon(fn (TransactionalMailLogItem $record) => match (true) {
                    $record->fake => 'heroicon-s-command-line',
                    $record->getSend()?->complaints_count > 0 => 'heroicon-s-x-circle',
                    $record->getSend()?->bounces_count > 0 => 'heroicon-s-no-symbol',
                    ! is_null($record->getSend()?->failed_at) || is_null($record->getSend()) => 'heroicon-s-exclamation-circle',
                    default => 'heroicon-s-envelope',
                })
                ->tooltip(fn (TransactionalMailLogItem $record) => match (true) {
                    $record->fake => __mc('Fake send'),
                    $record->getSend()?->complaints_count > 0 => __mc('Complained'),
                    $record->getSend()?->bounces_count > 0 => __mc('Bounced'),
                    ! is_null($record->getSend()?->failed_at) => $record->getSend()->failure_reason,
                    is_null($record->getSend()) => __mc('Suppressed'),
                    default => __mc('Sent'),
                })
                ->color(fn (TransactionalMailLogItem $record) => match (true) {
                    $record->fake => 'primary',
                    $record->getSend()?->complaints_count > 0 => 'danger',
                    $record->getSend()?->bounces_count > 0 => 'danger',
                    ! is_null($record->getSend()?->failed_at) || is_null($record->getSend()) => 'danger',
                    default => 'success',
                }),
            TextColumn::make('mail_name')
                ->label(__mc('Email')),
            TextColumn::make('contentItem.subject')
                ->extraAttributes(['class' => 'link'])
                ->size('base')
                ->label(__mc('Subject'))
                ->searchable($searchable),
            TextColumn::make('to')
                ->size('base')
                ->getStateUsing(fn (TransactionalMailLogItem $record) => $record->toString())
                ->searchable($searchable ? self::getTransactionalMailLogItemTableName().'.to' : false)
                ->forceSearchCaseInsensitive(),
            TextColumn::make('contentItem.opens_count')->size('sm')->width(0)->alignRight()->label(__mc('Opens'))->numeric()->extraAttributes([
                'class' => 'tabular-nums',
            ]),
            TextColumn::make('contentItem.clicks_count')->size('sm')->width(0)->alignRight()->label(__mc('Clicks'))->numeric()->extraAttributes([
                'class' => 'tabular-nums',
            ]),
            TextColumn::make('created_at')
                ->alignRight()
                ->width(0)
                ->label(__mc('Sent'))
                ->sortable()
                ->size('base')
                ->extraAttributes([
                    'class' => 'tabular-nums',
                ])
                ->date(config('mailcoach.date_format'), config('mailcoach.timezone')),
        ];
    }

    protected function getTableFilters(): array
    {
        $searchable = self::getTransactionalMailLogItemClass()::count() > $this->getTableRecordsPerPageSelectOptions()[0];

        if (! $searchable) {
            return [];
        }

        return [
            SelectFilter::make('mail_name')
                ->label(__mc('Mail name'))
                ->options(self::getTransactionalMailClass()::pluck('name', 'name')),
            Filter::make('created_at')
                ->form([
                    DateTimePicker::make('from')->label(__mc('From')),
                    DateTimePicker::make('until')->label(__mc('Until')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if ($data['from'] ?? null) {
                        $indicators['from'] = __mc('Created from :date', ['date' => Carbon::parse($data['from'])->toMailcoachFormat()]);
                    }

                    if ($data['until'] ?? null) {
                        $indicators['until'] = __mc('Created until :date', ['date' => Carbon::parse($data['until'])->toMailcoachFormat()]);
                    }

                    return $indicators;
                })
                ->label(__mc('Sent at')),
            SelectFilter::make('type')
                ->options([
                    'failed' => __mc('Failed'),
                    'sent' => __mc('Sent'),
                    'bounced' => __mc('Bounced'),
                    'complained' => __mc('Complained'),
                ])
                ->query(function (Builder $query, array $data) {
                    $query->whereHas('contentItem.sends', function (Builder $query) use ($data) {
                        /** @var \Illuminate\Database\Eloquent\Builder<\Spatie\Mailcoach\Domain\Shared\Models\Send> $query */
                        return match ($data['value']) {
                            'failed' => $query->failed(),
                            'sent' => $query->sent(),
                            'bounced' => $query->bounced(),
                            'complained' => $query->complained(),
                            default => $query,
                        };
                    });
                }),
            Filter::make('opens')
                ->label(__mc('Has opens'))
                ->query(fn (Builder $query) => $query->whereRelation('contentItem', 'open_count', '>', 0))
                ->toggle(),
            Filter::make('clicks')
                ->label(__mc('Has clicks'))
                ->query(fn (Builder $query) => $query->whereRelation('contentItem', 'click_count', '>', 0))
                ->toggle(),
            Filter::make('fake')
                ->query(fn (Builder $query) => $query->where('fake', true))
                ->toggle(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('Delete')
                ->visible(fn (TransactionalMailLogItem $record) => Auth::user()->can('delete', $record))
                ->action(function (TransactionalMailLogItem $record) {
                    $record->delete();
                    notify(__mc('Log was deleted.'));
                })
                ->requiresConfirmation()
                ->modalHeading(fn (TransactionalMailLogItem $record) => __mc('Delete :resource', ['resource' => __mc('log')]))
                ->modalDescription(fn (TransactionalMailLogItem $record) => new HtmlString(__mc('Are you sure you want to delete :resource<br/><strong>:name</strong>?', [
                    'resource' => __mc('log'),
                    'name' => $record->toString(),
                ])))
                ->label(' ')
                ->icon('heroicon-s-trash')
                ->color('danger'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('delete')
                ->visible(fn (TransactionalMailLogItem $record) => Auth::user()->can('delete', $record))
                ->requiresConfirmation()
                ->icon('heroicon-s-trash')
                ->color('danger')
                ->deselectRecordsAfterCompletion()
                ->action(fn (Collection $records) => $records->each->delete()),
        ];
    }

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return fn (TransactionalMailLogItem $record) => route('mailcoach.transactionalMails.show', $record);
    }

    public function getTitle(): string
    {
        return __mc('Log');
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return __mc('No transactional emails logged');
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-s-envelope';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return __mc('Transactional emails sent through Mailcoach will be logged here.');
    }
}
