<?php

namespace Spatie\Mailcoach\Livewire\Audience;

use Closure;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Spatie\Mailcoach\Domain\Audience\Events\TagRemovedEvent;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Audience\Models\Tag;
use Spatie\Mailcoach\Livewire\TableComponent;
use Spatie\Mailcoach\MainNavigation;

class TagsComponent extends TableComponent
{
    public EmailList $emailList;

    public function mount(EmailList $emailList)
    {
        $this->authorize('view', $emailList);

        $this->emailList = $emailList;

        app(MainNavigation::class)->activeSection()
            ?->add($this->emailList->name, route('mailcoach.emailLists.summary', $this->emailList), function ($section) {
                $section->add(__mc('Tags'), route('mailcoach.emailLists.tags', $this->emailList));
            });
    }

    public function deleteTag(Tag $tag)
    {
        $this->authorize('delete', $tag);

        $tag->subscribers->each(function ($subscriber) use ($tag) {
            event(new TagRemovedEvent($subscriber, $tag));
        });

        $tag->delete();

        notify(__mc('Tag :tag was deleted', ['tag' => $tag->name]));

        $this->redirect(route('mailcoach.emailLists.tags', $this->emailList));
    }

    public function getTitle(): string
    {
        return __mc('Tags');
    }

    public function getLayout(): string
    {
        return 'mailcoach::app.emailLists.layouts.emailList';
    }

    public function getLayoutData(): array
    {
        return [
            'emailList' => $this->emailList,
            'create' => Auth::user()->can('create', self::getTagClass()) ? 'tag' : null,
            'createText' => __mc('Create tag'),
            'createData' => [
                'emailList' => $this->emailList,
            ],
        ];
    }

    protected function getTableQuery(): Builder
    {
        return $this->emailList->tags()->withCount(['subscribers'])->getQuery()->reorder();
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'name';
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__mc('Name'))
                ->extraAttributes(fn (Tag $record) => Auth::user()->can('update', $record) ? ['class' => 'link'] : [])
                ->sortable()
                ->searchable(),
            IconColumn::make('visible_in_preferences')
                ->label(__mc('Visible'))
                ->tooltip(__mc('Whether the subscriber can choose to add or remove this tag on "Manage your preferences" page.'))
                ->alignCenter()
                ->sortable()
                ->boolean(),
            TextColumn::make('subscribers_count')
                ->label(__mc('Subscribers'))
                ->sortable()
                ->numeric()
                ->extraAttributes([
                    'class' => 'tabular-nums',
                ])
                ->alignRight(),
            TextColumn::make('updated_at')
                ->label(__mc('Updated at'))
                ->dateTime(config('mailcoach.date_format'))
                ->sortable()
                ->alignRight(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('delete')
                ->visible(fn (Tag $record) => Auth::user()->can('delete', $record))
                ->label('')
                ->tooltip(__mc('Delete'))
                ->modalHeading('Delete')
                ->icon('heroicon-s-trash')
                ->color('danger')
                ->action(fn (Tag $record) => $this->deleteTag($record))
                ->requiresConfirmation()
                ->modalHeading(fn (Tag $record) => __mc('Delete :resource', ['resource' => __mc('tag')]))
                ->modalDescription(fn (Tag $record) => new HtmlString(__mc('Are you sure you want to delete :resource<br/><strong>:name</strong>?', [
                    'resource' => __mc('tag'),
                    'name' => $record->name,
                ]))),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('Delete')
                ->visible(fn (Tag $record) => Auth::user()->can('delete', new (self::getTagClass())))
                ->label(__mc('Delete'))
                ->icon('heroicon-s-trash')
                ->requiresConfirmation()
                ->color('danger')
                ->action(function ($records) {
                    $this->authorize('delete', new (self::getTagClass()));

                    foreach ($records as $tag) {
                        $tag->subscribers->each(function ($subscriber) use ($tag) {
                            event(new TagRemovedEvent($subscriber, $tag));
                        });

                        $tag->delete();
                    }

                    notify(__mc('Successfully deleted :count tags', ['count' => $records->count()]));
                }),
        ];
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return __mc('There are no tags for this list. Everyone is equal!');
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-s-tag';
    }

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return function (Tag $record) {
            if (Auth::user()->can('update', $record)) {
                return route('mailcoach.emailLists.tags.edit', [$this->emailList, $record]);
            }

            return null;
        };
    }
}
