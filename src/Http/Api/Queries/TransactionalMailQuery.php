<?php

namespace Spatie\Mailcoach\Http\Api\Queries;

use Illuminate\Http\Request;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Http\Api\Queries\Filters\FuzzyFilter;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TransactionalMailQuery extends QueryBuilder
{
    use UsesMailcoachModels;

    public function __construct(?Request $request = null)
    {
        parent::__construct(self::getTransactionalMailClass()::query(), $request);

        $this
            ->defaultSort('-created_at', '-id')
            ->allowedSorts(
                'subject',
                'created_at',
                'id',
            )
            ->allowedFilters(
                AllowedFilter::exact('uuid'),
                AllowedFilter::exact('name'),
                AllowedFilter::custom('search', new FuzzyFilter('contentItem.subject', 'name')),
            );
    }
}
