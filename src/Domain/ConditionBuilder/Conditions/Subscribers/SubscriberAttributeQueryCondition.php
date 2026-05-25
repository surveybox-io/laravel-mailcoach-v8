<?php

namespace Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\Subscribers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Mailcoach\Domain\Audience\Models\TagSegment;
use Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\QueryCondition;
use Spatie\Mailcoach\Domain\ConditionBuilder\Data\SubscriberAttributeQueryConditionData;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ComparisonOperator;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ConditionCategory;
use Tpetry\QueryExpressions\Language\Cast;

class SubscriberAttributeQueryCondition extends QueryCondition
{
    public const KEY = 'subscriber_attribute';

    public function key(): string
    {
        return self::KEY;
    }

    public function comparisonOperators(): array
    {
        return [
            ComparisonOperator::Equals,
            ComparisonOperator::In,
            ComparisonOperator::NotEquals,
            ComparisonOperator::NotIn,
            ComparisonOperator::GreaterThanOrEquals,
            ComparisonOperator::SmallerThanOrEquals,
        ];
    }

    public function category(): ConditionCategory
    {
        return ConditionCategory::Attributes;
    }

    public function getComponent(): string
    {
        return 'mailcoach::subscriber-attribute-condition';
    }

    /** @param SubscriberAttributeQueryConditionData $value */
    public function apply(Builder $baseQuery, ComparisonOperator $operator, mixed $value, ?TagSegment $tagSegment): Builder
    {
        $asFloat = is_string($value->value) && str($value->value)->contains('.');

        return match ($operator) {
            ComparisonOperator::Equals => $baseQuery->whereJsonContains("extra_attributes->{$value->attribute}", $value->value),
            ComparisonOperator::In => $baseQuery->where(function (Builder $query) use ($value) {
                foreach (Arr::wrap($value->value) as $attributeValue) {
                    $query->orWhereJsonContains("extra_attributes->{$value->attribute}", $attributeValue);
                }
            }),
            ComparisonOperator::NotEquals => $baseQuery->where(function (Builder $query) use ($value) {
                $query
                    ->whereNull('extra_attributes')
                    ->orWhereJsonDoesntContainKey("extra_attributes->{$value->attribute}")
                    ->orWhereJsonDoesntContain("extra_attributes->{$value->attribute}", $value->value);
            }),
            ComparisonOperator::NotIn => $baseQuery->where(function (Builder $query) use ($value) {
                $query->orWhereNull('extra_attributes');
                $query->orWhereJsonDoesntContainKey("extra_attributes->{$value->attribute}");
                $query->orWhere(function (Builder $query) use ($value) {
                    foreach (Arr::wrap($value->value) as $attributeValue) {
                        $query->whereJsonDoesntContain("extra_attributes->{$value->attribute}", $attributeValue);
                    }
                });
            }),
            ComparisonOperator::GreaterThanOrEquals => $baseQuery->where(new Cast("extra_attributes->{$value->attribute}", $asFloat ? 'float' : 'int'), '>=', $asFloat ? DB::raw('(1.0 * '.(float) $value->value.')') : (int) $value->value),
            ComparisonOperator::SmallerThanOrEquals => $baseQuery->where(new Cast("extra_attributes->{$value->attribute}", $asFloat ? 'float' : 'int'), '<=', $asFloat ? DB::raw('(1.0 * '.(float) $value->value.')') : (int) $value->value),
            default => $baseQuery,
        };
    }

    public function dto(): ?string
    {
        return SubscriberAttributeQueryConditionData::class;
    }
}
