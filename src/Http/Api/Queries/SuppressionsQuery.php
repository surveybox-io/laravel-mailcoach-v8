<?php

namespace Spatie\Mailcoach\Http\Api\Queries;

use Illuminate\Http\Request;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Http\Api\Queries\Filters\FuzzyFilter;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SuppressionsQuery extends QueryBuilder
{
    use UsesMailcoachModels;

    public function __construct(?Request $request = null)
    {
        parent::__construct(self::getSuppressionClass()::query(), $request);

        $this
            ->allowedSorts(
                'created_at',
            )
            ->allowedFilters(
                AllowedFilter::custom('search', new FuzzyFilter('email')),
                AllowedFilter::exact('reason'),
            );
    }
}
