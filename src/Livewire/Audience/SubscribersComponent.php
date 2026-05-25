<?php

namespace Spatie\Mailcoach\Livewire\Audience;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Spatie\Mailcoach\Domain\Audience\Actions\Subscribers\DeleteSubscriberAction;
use Spatie\Mailcoach\Domain\Audience\Actions\Subscribers\SendConfirmSubscriberMailAction;
use Spatie\Mailcoach\Domain\Audience\Enums\SubscriptionStatus;
use Spatie\Mailcoach\Domain\Audience\Enums\TagType;
use Spatie\Mailcoach\Domain\Audience\Events\SubscribedEvent;
use Spatie\Mailcoach\Domain\Audience\Jobs\AddTagsToSubscribersJob;
use Spatie\Mailcoach\Domain\Audience\Jobs\DeleteSubscribersJob;
use Spatie\Mailcoach\Domain\Audience\Jobs\ExportSubscribersJob;
use Spatie\Mailcoach\Domain\Audience\Jobs\RemoveTagsFromSubscribersJob;
use Spatie\Mailcoach\Domain\Audience\Jobs\ResendConfirmSubscriberMailJob;
use Spatie\Mailcoach\Domain\Audience\Jobs\UnsubscribeSubscribersJob;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Audience\Models\Subscriber;
use Spatie\Mailcoach\Domain\Campaign\Enums\CampaignStatus;
use Spatie\Mailcoach\Livewire\TableComponent;
use Spatie\Mailcoach\Mailcoach;
use Spatie\Mailcoach\MainNavigation;

class SubscribersComponent extends TableComponent
{
    public EmailList $emailList;

    public function mount(EmailList $emailList)
    {
        $this->authorize('view', $emailList);

        $this->emailList = $emailList;

        app(MainNavigation::class)->activeSection()?->add($this->emailList->name.' ', route('mailcoach.emailLists'));
    }

    public function getTableQuery(): Builder
    {
        return self::getSubscriberClass()::query()
            ->where(self::getSubscriberTableName().'.email_list_id', $this->emailList->id)
            ->with('emailList', 'tags');
    }

    protected function getTableColumns(): array
    {
        $searchable = $this->emailList->subscribers()->count() > $this->getTableRecordsPerPageSelectOptions()[0];

        return array_filter([
            IconColumn::make('status')
                ->label('')
                ->icon(fn (Subscriber $record) => match (true) {
                    $record->isUnconfirmed() => 'heroicon-s-question-mark-circle',
                    $record->isSubscribed() => 'heroicon-s-check-circle',
                    $record->isUnsubscribed() => 'heroicon-s-x-circle',
                    default => '',
                })
                ->color(fn (Subscriber $record) => match (true) {
                    $record->isUnconfirmed() => 'warning',
                    $record->isSubscribed() => 'success',
                    $record->isUnsubscribed() => 'danger',
                    default => '',
                })
                ->alignCenter()
                ->width(0)
                ->tooltip(fn (Subscriber $record) => match (true) {
                    $record->isUnconfirmed() => __mc('Unconfirmed'),
                    $record->isSubscribed() => __mc('Subscribed'),
                    $record->isUnsubscribed() => __mc('Unsubscribed'),
                    default => '',
                }),
            TextColumn::make('email')
                ->label(__mc('Email'))
                ->searchable($searchable, query: function (Builder $query, string $search) {
                    if (str_starts_with($search, '@')) {
                        $search = Str::after($search, '@');
                    }

                    $query->emailSearch($search, $this->emailList->id);
                })
                ->sortable(),
            TextColumn::make('tags.name')
                ->view('mailcoach::app.emailLists.subscribers.columns.tags')
                ->searchable($searchable),
            Feature::store('array')->for('')->active('mailcoach::subscriber-engagement') ? TextColumn::make('emails_received')->label(__mc('Received'))->alignRight()->numeric()->extraAttributes([
                'class' => 'tabular-nums',
            ]) : null,
            Feature::store('array')->for('')->active('mailcoach::subscriber-engagement') ? TextColumn::make('emails_opened')->label(__mc('Opened'))->alignRight()->numeric()->extraAttributes([
                'class' => 'tabular-nums',
            ]) : null,
            Feature::store('array')->for('')->active('mailcoach::subscriber-engagement') ? TextColumn::make('emails_clicked')->label(__mc('Clicked'))->alignRight()->numeric()->extraAttributes([
                'class' => 'tabular-nums',
            ]) : null,
            TextColumn::make('date')
                ->label(__mc('Date'))
                ->sortable(query: function (Builder $query) {
                    $query->orderBy(DB::raw('unsubscribed_at, subscribed_at, created_at'), $this->tableSortDirection);
                })
                ->getStateUsing(fn (Subscriber $record) => match (true) {
                    $record->isUnsubscribed() => $record->unsubscribed_at?->toMailcoachFormat(),
                    $record->isUnconfirmed() => $record->created_at->toMailcoachFormat(),
                    default => $record->subscribed_at?->toMailcoachFormat(),
                }),
        ]);
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('resend_confirmation')
                    ->label(__mc('Resend confirmation mail'))
                    ->icon('heroicon-s-envelope')
                    ->requiresConfirmation()
                    ->action(fn (Subscriber $record) => $this->resendConfirmation($record))
                    ->visible(fn (Subscriber $record) => Auth::user()->can('update', $record) && $record->isUnconfirmed()),
                Action::make('confirm')
                    ->label(__mc('Confirm'))
                    ->icon('heroicon-s-check-circle')
                    ->requiresConfirmation()
                    ->action(fn (Subscriber $record) => $this->confirm($record))
                    ->visible(fn (Subscriber $record) => Auth::user()->can('update', $record) && $record->isUnconfirmed()),
                Action::make('unsubscribe')
                    ->label(__mc('Unsubscribe'))
                    ->icon('heroicon-s-x-circle')
                    ->requiresConfirmation()
                    ->action(fn (Subscriber $record) => $this->unsubscribe($record))
                    ->visible(fn (Subscriber $record) => Auth::user()->can('update', $record) && $record->isSubscribed()),
                Action::make('resubscribe')
                    ->label(__mc('Resubscribe'))
                    ->icon('heroicon-s-check-circle')
                    ->requiresConfirmation()
                    ->action(fn (Subscriber $record) => $this->resubscribe($record))
                    ->visible(fn (Subscriber $record) => Auth::user()->can('update', $record) && ! $record->isSubscribed() && ! $record->isUnconfirmed()),
                Action::make('delete')
                    ->visible(fn (Subscriber $record) => Auth::user()->can('delete', $record))
                    ->label(__mc('Delete'))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Subscriber $record) => __mc('Delete :resource', ['resource' => __mc('subscriber')]))
                    ->modalDescription(fn (Subscriber $record) => new HtmlString(__mc('Are you sure you want to delete :resource<br/><strong>:name</strong>?', [
                        'resource' => __mc('subscriber'),
                        'name' => $record->email,
                    ])))
                    ->color('danger')
                    ->icon('heroicon-s-trash')
                    ->action(fn (Subscriber $record) => $this->deleteSubscriber($record)),
            ]),
        ];
    }

    protected function applyGlobalSearchToTableQuery(
        \Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $search = trim(strtolower($this->getTableSearch() ?? ''));

        if (str_contains($search, '@')) {
            $clone = clone $query;

            if ($clone->where('email', $search)->count() > 0) {
                return $query->where('email', $search);
            }
        }

        return parent::applyGlobalSearchToTableQuery($query);
    }

    protected function getTableFilters(): array
    {
        if ($this->emailList->subscribers()->count() <= $this->getTableRecordsPerPageSelectOptions()[0]) {
            return [];
        }

        return [
            SelectFilter::make('status')
                ->label(__mc('Status'))
                ->options([
                    'subscribed' => __mc('Subscribed'),
                    'unsubscribed' => __mc('Unsubscribed'),
                    'unconfirmed' => __mc('Unconfirmed'),
                ])
                ->query(fn (Builder $query, array $data) => match ($data['value'] ?? '') {
                    'subscribed' => $query->subscribed(),
                    'unsubscribed' => $query->unsubscribed(),
                    'unconfirmed' => $query->unconfirmed(),
                    default => $query,
                })
                ->columnspan(3),
            SelectFilter::make('tags')
                ->label(__mc('Tags'))
                ->multiple()
                ->options(fn () => $this->emailList->tags()->where('type', TagType::Default)->pluck('name', 'uuid'))
                ->query(fn (Builder $query, array $data) => $this->applyTagsQuery($query, $data['values'] ?? [])),
            SelectFilter::make('mailcoach_tags')
                ->label(__mc('Mailcoach tags'))
                ->multiple()
                ->options(fn () => $this->emailList->tags()->where('type', TagType::Mailcoach)->pluck('name', 'uuid'))
                ->query(fn (Builder $query, array $data) => $this->applyTagsQuery($query, $data['values'] ?? [])),
            SelectFilter::make('opened_campaign')
                ->label(__mc('Opened campaign'))
                ->multiple()
                ->placeholder('')
                ->options(fn () => $this->emailList->campaigns()->where('status', CampaignStatus::Sent)->pluck('name', 'uuid'))
                ->query(function (Builder $query, array $data) {
                    if (! isset($data['values']) || ! $data['values']) {
                        return $query;
                    }

                    return $query->whereHas('opens', function (Builder $query) use ($data) {
                        $query->whereIn(
                            'content_item_id',
                            self::getContentItemClass()::query()
                                ->where('model_type', (new (self::getCampaignClass()))->getMorphClass())
                                ->whereIn('model_id', self::getCampaignClass()::whereIn('uuid', $data['values'])->select('id'))
                                ->select('id')
                        );
                    });
                }),
            SelectFilter::make('opened_automation_mail')
                ->label(__mc('Opened automation mail'))
                ->multiple()
                ->placeholder('')
                ->options(fn () => self::getAutomationMailClass()::query()->pluck('name', 'uuid'))
                ->query(function (Builder $query, array $data) {
                    if (! isset($data['values']) || ! $data['values']) {
                        return $query;
                    }

                    return $query->whereHas('opens', function (Builder $query) use ($data) {
                        $query->whereIn(
                            'content_item_id',
                            self::getContentItemClass()::query()
                                ->where('model_type', (new (self::getAutomationMailClass()))->getMorphClass())
                                ->whereIn('model_id', self::getAutomationMailClass()::whereIn('uuid', $data['values'])->select('id'))
                                ->select('id')
                        );
                    });
                }),
            SelectFilter::make('clicked_campaign')
                ->label(__mc('Clicked campaign'))
                ->multiple()
                ->placeholder('')
                ->options(fn () => $this->emailList->campaigns()->where('status', CampaignStatus::Sent)->pluck('name', 'uuid'))
                ->query(function (Builder $query, array $data) {
                    if (! isset($data['values']) || ! $data['values']) {
                        return $query;
                    }

                    return $query->whereHas('clicks', function (Builder $query) use ($data) {
                        $query->whereHas('link', function (Builder $query) use ($data) {
                            $query->whereIn(
                                'content_item_id',
                                self::getContentItemClass()::query()
                                    ->where('model_type', (new (self::getCampaignClass()))->getMorphClass())
                                    ->whereIn('model_id', self::getCampaignClass()::whereIn('uuid', $data['values'])->select('id'))
                                    ->select('id')
                            );
                        });
                    });
                }),
            SelectFilter::make('clicked_automation_mail')
                ->label(__mc('Clicked automation mail'))
                ->multiple()
                ->placeholder('')
                ->options(fn () => self::getAutomationMailClass()::query()->pluck('name', 'uuid'))
                ->query(function (Builder $query, array $data) {
                    if (! isset($data['values']) || ! $data['values']) {
                        return $query;
                    }

                    return $query->whereHas('clicks', function (Builder $query) use ($data) {
                        $query->whereHas('link', function (Builder $query) use ($data) {
                            $query->whereIn(
                                'content_item_id',
                                self::getContentItemClass()::query()
                                    ->where('model_type', (new (self::getAutomationMailClass()))->getMorphClass())
                                    ->whereIn('model_id', self::getAutomationMailClass()::whereIn('uuid', $data['values'])->select('id'))
                                    ->select('id')
                            );
                        });
                    });
                }),
            SelectFilter::make('emails')
                ->label(__mc('Received emails'))
                ->options([
                    'any' => __mc('Has received at least one email'),
                    'none' => __mc('Has received no emails'),
                ])
                ->query(fn (Builder $query, array $data) => $query->where(function (Builder $query) use ($data) {
                    if ($data['value'] === 'any') {
                        if (Feature::store('array')->for('')->active('mailcoach::subscriber-engagement')) {
                            return $query->where('emails_received', '>', 0);
                        }

                        return $query->whereHas('sends');
                    }

                    if ($data['value'] === 'none') {
                        if (Feature::store('array')->for('')->active('mailcoach::subscriber-engagement')) {
                            return $query->where('emails_received', '=', 0);
                        }

                        return $query->whereDoesntHave('sends');
                    }

                    return $query;
                })),
            SelectFilter::make('opens')
                ->label(__mc('Opens'))
                ->options([
                    'any' => __mc('Has opened at least one email'),
                    'none' => __mc('Has opened no emails'),
                ])
                ->query(fn (Builder $query, array $data) => $query->where(function (Builder $query) use ($data) {
                    if ($data['value'] === 'any') {
                        if (Feature::store('array')->for('')->active('mailcoach::subscriber-engagement')) {
                            return $query->where('emails_opened', '>', 0);
                        }

                        return $query->whereHas('opens');
                    }

                    if ($data['value'] === 'none') {
                        if (Feature::store('array')->for('')->active('mailcoach::subscriber-engagement')) {
                            return $query->where('emails_opened', '=', 0);
                        }

                        return $query->whereDoesntHave('opens');
                    }

                    return $query;
                })),
            SelectFilter::make('clicks')
                ->label(__mc('Clicks'))
                ->options([
                    'any' => __mc('Has clicked at least one email'),
                    'none' => __mc('Has clicked no emails'),
                ])
                ->query(fn (Builder $query, array $data) => $query->where(function (Builder $query) use ($data) {
                    if ($data['value'] === 'any') {
                        if (Feature::store('array')->for('')->active('mailcoach::subscriber-engagement')) {
                            return $query->where('emails_clicked', '>', 0);
                        }

                        return $query->whereHas('clicks');
                    }

                    if ($data['value'] === 'none') {
                        if (Feature::store('array')->for('')->active('mailcoach::subscriber-engagement')) {
                            return $query->where('emails_clicked', '=', 0);
                        }

                        return $query->whereDoesntHave('clicks');
                    }

                    return $query;
                })),
        ];
    }

    protected function applyTagsQuery(Builder $query, array $values): Builder
    {
        if (! $values) {
            return $query;
        }

        $tagIds = self::getTagClass()::query()
            ->where('email_list_id', $this->emailList->id)
            ->where(fn (Builder $query) => $query->whereIn('uuid', $values))
            ->pluck('id');

        if (! $tagIds->count()) {
            return $query;
        }

        $prefix = DB::getTablePrefix();

        return $query->where(
            DB::connection(Mailcoach::getDatabaseConnection())
                ->table('mailcoach_email_list_subscriber_tags')
                ->selectRaw('count(*)')
                ->where(self::getSubscriberTableName().'.id', DB::raw($prefix.'mailcoach_email_list_subscriber_tags.subscriber_id'))
                ->whereIn('mailcoach_email_list_subscriber_tags.tag_id', $tagIds->toArray()),
            '>=', $tagIds->count()
        );
    }

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return function (Subscriber $record) {
            return route('mailcoach.emailLists.subscriber.details', [$this->emailList, $record]);
        };
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('Add tags')
                ->visible(fn (Subscriber $record) => Auth::user()->can('update', $this->emailList))
                ->label(__mc('Add tags'))
                ->icon('heroicon-s-plus-circle')
                ->action(function (Collection $subscribers, array $data) {
                    $tags = self::getTagClass()::whereIn('uuid', $data['tags'])->pluck('name')->toArray();

                    dispatch(new AddTagsToSubscribersJob($subscribers->pluck('id')->toArray(), $tags));

                    notify(__mc('Tags will be added to :count subscribers. This will be completed in the background.', ['count' => $subscribers->count()]));
                })
                ->form([
                    Select::make('tags')
                        ->label(__mc('Tags'))
                        ->multiple()
                        ->options(self::getTagClass()::where('type', TagType::Default)->orderBy('name')->pluck('name', 'uuid'))
                        ->required(),
                ]),
            BulkAction::make('Remove tags')
                ->visible(fn (Subscriber $record) => Auth::user()->can('update', $this->emailList))
                ->label(__mc('Remove tags'))
                ->icon('heroicon-s-minus-circle')
                ->action(function (Collection $subscribers, array $data) {
                    $tags = self::getTagClass()::whereIn('uuid', $data['tags'])->pluck('name')->toArray();

                    dispatch(new RemoveTagsFromSubscribersJob($subscribers->pluck('id')->toArray(), $tags));

                    notify(__mc('Tags will be removed from :count subscribers. This will be completed in the background.', ['count' => $subscribers->count()]));
                })
                ->form([
                    Select::make('tags')
                        ->label(__mc('Tags'))
                        ->multiple()
                        ->options(self::getTagClass()::where('type', TagType::Default)->orderBy('name')->pluck('name', 'uuid'))
                        ->required(),
                ]),
            BulkAction::make('export')
                ->label(__mc('Export selected'))
                ->icon('heroicon-s-cloud-arrow-down')
                ->action(function (Collection $subscribers) {
                    $header = [
                        'email' => null,
                        'first_name' => null,
                        'last_name' => null,
                        'tags' => null,
                        'subscribed_at' => null,
                        'unsubscribed_at' => null,
                    ];

                    $subscribers->each(function (Subscriber $subscriber) use (&$header) {
                        $attributes = array_keys($subscriber->extra_attributes->toArray());
                        $attributes = collect($attributes)->mapWithKeys(fn ($key) => [$key => null])->toArray();
                        ksort($attributes);

                        $header = array_merge($header, $attributes);
                    });

                    return $this->export(
                        header: array_unique(array_keys($header)),
                        rows: $subscribers,
                        formatRow: function (Subscriber $subscriber) use ($header) {
                            return array_merge($header, $subscriber->toExportRow());
                        },
                        title: "{$this->emailList->name} subscribers",
                    );
                }),
            BulkAction::make('Unsubscribe')
                ->visible(fn (Subscriber $record) => Auth::user()->can('update', $this->emailList))
                ->label(__mc('Unsubscribe'))
                ->icon('heroicon-s-x-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()
                ->action(function (Collection $subscribers) {
                    $count = $subscribers->count();

                    dispatch(new UnsubscribeSubscribersJob($subscribers->pluck('id')->toArray()));

                    notify(__mc(':count subscribers will be unsubscribed. This will be completed in the background.', [
                        'count' => $count,
                    ]));
                }),
            BulkAction::make('Resend confirmation mail')
                ->visible(fn (Subscriber $record) => Auth::user()->can('update', $this->emailList))
                ->icon('heroicon-s-envelope')
                ->requiresConfirmation()
                ->color('warning')
                ->deselectRecordsAfterCompletion()
                ->action(fn (Subscriber $record) => $this->resendConfirmation($record))
                ->action(function (Collection $subscribers) {
                    dispatch(new ResendConfirmSubscriberMailJob($subscribers->pluck('id')->toArray()));

                    notify(__mc(':count subscribers will be sent a confirmation mail. This will be completed in the background.', [
                        'count' => $subscribers->count(),
                    ]));
                }),
            BulkAction::make('Delete')
                ->visible(fn (Subscriber $record) => Auth::user()->can('delete', $this->emailList))
                ->label(__mc('Delete'))
                ->requiresConfirmation()
                ->modalHeading(fn (Collection $records) => __mc('Delete :resource', ['resource' => __mc('subscribers')]))
                ->modalDescription(fn (Collection $records) => new HtmlString(__mc('Are you sure you want to delete :count :resource?', [
                    'resource' => __mc('subscribers'),
                    'count' => $records->count(),
                ])))
                ->icon('heroicon-s-trash')
                ->color('danger')
                ->deselectRecordsAfterCompletion()
                ->action(function (Collection $records) {
                    $count = $records->count();

                    dispatch(new DeleteSubscribersJob($records->pluck('id')->toArray()));

                    notify(__mc('Deleting :count subscribers. This will be completed in the background.', ['count' => $count]));
                }),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        if ($this->emailList->subscribers()->count() <= $this->getTableRecordsPerPageSelectOptions()[0]) {
            return [];
        }

        return [
            Action::make('export_subscribers')
                ->label(function () {
                    return __mc('Export :count subscribers', ['count' => Str::shortNumber($this->getAllTableRecordsCount())]);
                })
                ->requiresConfirmation()
                ->link()
                ->action(function () {
                    $export = self::getSubscriberExportClass()::create([
                        'email_list_id' => $this->emailList->id,
                        'filters' => array_merge($this->tableFilters, ['search' => $this->tableSearch]),
                    ]);

                    dispatch(new ExportSubscribersJob(
                        subscriberExport: $export,
                        user: Auth::guard(config('mailcoach.guard'))->user(),
                    ));

                    notify(__mc('Subscriber export successfully queued.'));

                    return redirect()->route('mailcoach.emailLists.subscriber-exports', [$this->emailList]);
                }),
        ];
    }

    public function subscribersCount(): int
    {
        return once(function () {
            return $this->emailList->allSubscribers()->count();
        });
    }

    public function getTable(): Table
    {
        $table = parent::getTable();

        if ($this->subscribersCount() >= 10_000) {
            $table->selectCurrentPageOnly();
        }

        $table->filtersFormColumns(3);
        $table->defaultSort(function (Builder $query) {
            $query->orderByDesc(DB::raw('(subscribed_at IS NOT NULL), subscribed_at'));
        });

        return $table;
    }

    public function deleteSubscriber(Subscriber $subscriber)
    {
        $this->authorize('delete', $subscriber);

        /** @var DeleteSubscriberAction $deleteSubscriberAction */
        $deleteSubscriberAction = Mailcoach::getAudienceActionClass('delete_subscriber', DeleteSubscriberAction::class);

        $deleteSubscriberAction->execute($subscriber);

        notify(__mc('Subscriber :subscriber was deleted.', ['subscriber' => $subscriber->email]));
    }

    public function resubscribe(Subscriber $subscriber)
    {
        if (! $subscriber->isUnsubscribed()) {
            notify(__mc('Can only resubscribe unsubscribed subscribers'), 'error');

            return;
        }

        $subscriber->resubscribe();

        notify(__mc(':subscriber has been resubscribed.', ['subscriber' => $subscriber->email]));
    }

    public function unsubscribe(Subscriber $subscriber)
    {
        if (! $subscriber->isSubscribed()) {
            notify(__mc('Can only unsubscribe a subscribed subscriber'), 'error');

            return;
        }

        $subscriber->unsubscribe();

        notify(__mc(':subscriber has been unsubscribed.', ['subscriber' => $subscriber->email]));
    }

    public function confirm(Subscriber $subscriber)
    {
        if ($subscriber->status !== SubscriptionStatus::Unconfirmed) {
            notify(__mc('Can only subscribe unconfirmed emails'), 'error');

            return;
        }

        $subscriber->update([
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);

        event(new SubscribedEvent($subscriber));

        notify(__mc(':subscriber has been confirmed.', ['subscriber' => $subscriber->email]));
    }

    public function resendConfirmation(Subscriber $subscriber): void
    {
        resolve(SendConfirmSubscriberMailAction::class)->execute($subscriber, isReminder: true);

        notify(__mc('A confirmation mail has been sent to :subscriber', ['subscriber' => $subscriber->email]));
    }

    public function deleteUnsubscribes()
    {
        $this->authorize('update', $this->emailList);

        $this->emailList->allSubscribers()->unsubscribed()->delete();

        notify(__mc('All unsubscribers of the list have been deleted.'));
    }

    public function getTitle(): string
    {
        return __mc('Subscribers');
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-s-user-group';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return __mc('Where is everyone? No subscribers found');
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Action::make('learn')
                ->url('https://mailcoach.app/resources/learn-mailcoach/features/email-lists')
                ->link()
                ->openUrlInNewTab()
                ->label(__mc('Learn more about subscribers')),
        ];
    }

    public function getLayout(): string
    {
        return 'mailcoach::app.emailLists.layouts.emailList';
    }

    public function getLayoutData(): array
    {
        return [
            'emailList' => $this->emailList,
            'createData' => [
                'emailList' => $this->emailList,
            ],
            'create' => Auth::guard(config('mailcoach.guard'))->user()->can('create', self::getSubscriberClass())
                ? 'subscriber'
                : null,
            'createText' => __mc('Create subscriber'),
        ];
    }
}
