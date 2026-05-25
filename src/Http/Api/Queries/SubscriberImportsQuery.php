<?php

namespace Spatie\Mailcoach\Http\Api\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriberImportsQuery extends QueryBuilder
{
    use UsesMailcoachModels;

    public function __construct(?Request $request = null)
    {
        parent::__construct(
            self::getSubscriberImportClass()::query()
                ->select([
                    'uuid',
                    'status',
                    'subscribe_unsubscribed',
                    'unsubscribe_others',
                    'replace_tags',
                    'imported_subscribers_count',
                    'errors',
                    'email_list_id',
                ])
                ->with(['emailList']),
            $request,
        );

        $this
            ->allowedSorts(
                'created_at',
                'status',
            )
            ->allowedFilters(
                AllowedFilter::callback('email_list_uuid', function (Builder $query, $value) {
                    $query->whereRelation('emailList', 'uuid', $value);
                }),
                AllowedFilter::exact('status'),
            );
    }
}
