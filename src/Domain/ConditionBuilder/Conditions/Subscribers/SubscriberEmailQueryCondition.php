<?php

namespace Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\Subscribers;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Mailcoach\Domain\Audience\Models\TagSegment;
use Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\QueryCondition;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ComparisonOperator;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ConditionCategory;

class SubscriberEmailQueryCondition extends QueryCondition
{
    public const KEY = 'subscriber_email';

    public function key(): string
    {
        return self::KEY;
    }

    public function comparisonOperators(): array
    {
        return [
            ComparisonOperator::StartsWith,
            ComparisonOperator::DoesNotStartWith,
            ComparisonOperator::EndsWith,
            ComparisonOperator::DoesNotEndWith,
        ];
    }

    public function category(): ConditionCategory
    {
        return ConditionCategory::Attributes;
    }

    public function getComponent(): string
    {
        return 'mailcoach::subscriber-email-condition';
    }

    public function apply(Builder $baseQuery, ComparisonOperator $operator, mixed $value, ?TagSegment $tagSegment): Builder
    {
        $this->ensureOperatorIsSupported($operator);

        $value = empty($value) ? null : $value;

        return match ($operator) {
            ComparisonOperator::EndsWith => $baseQuery->where('email', 'like', "%{$value}"),
            ComparisonOperator::DoesNotEndWith => $baseQuery->whereNot('email', 'like', "%{$value}"),
            ComparisonOperator::StartsWith => $baseQuery->where('email', 'like', "{$value}%"),
            ComparisonOperator::DoesNotStartWith => $baseQuery->whereNot('email', 'like', "{$value}%"),
            default => $baseQuery,
        };
    }
}
