<?php

namespace Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\Subscribers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\Mailcoach\Domain\Audience\Models\TagSegment;
use Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\QueryCondition;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ComparisonOperator;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ConditionCategory;
use Spatie\Mailcoach\Mailcoach;

class SubscriberNotInListQueryCondition extends QueryCondition
{
    public const KEY = 'subscriber_not_in_list';

    public function key(): string
    {
        return self::KEY;
    }

    public function comparisonOperators(): array
    {
        return [
            ComparisonOperator::Equals,
        ];
    }

    public function category(): ConditionCategory
    {
        return ConditionCategory::Attributes;
    }

    public function getComponent(): string
    {
        return 'mailcoach::subscriber-not-in-list-condition';
    }

    public function apply(Builder $baseQuery, ComparisonOperator $operator, mixed $value, ?TagSegment $tagSegment): Builder
    {
        return $baseQuery
            ->whereNotExists(
                DB::connection(Mailcoach::getDatabaseConnection())->table(self::getSubscriberTableName(), 'others')
                    ->where('others.email', '=', DB::raw(self::getSubscriberTableName().'.email'))
                    ->where('others.email_list_id', '=', $value)
            );
    }
}
