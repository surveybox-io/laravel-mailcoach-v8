<?php

namespace Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\Subscribers;

use Carbon\CarbonInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Spatie\Mailcoach\Domain\Audience\Models\TagSegment;
use Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\QueryCondition;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ComparisonOperator;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ConditionCategory;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\EngagementType;

class SubscriberEngagementQueryCondition extends QueryCondition
{
    public const KEY = 'subscriber_engagement';

    public function key(): string
    {
        return self::KEY;
    }

    public function comparisonOperators(): array
    {
        return [
            ComparisonOperator::GreaterThanOrEquals,
            ComparisonOperator::SmallerThanOrEquals,
        ];
    }

    public function category(): ConditionCategory
    {
        return ConditionCategory::Actions;
    }

    public function getComponent(): string
    {
        return 'mailcoach::subscriber-engagement-condition';
    }

    public function apply(Builder $baseQuery, ComparisonOperator $operator, mixed $value, ?TagSegment $tagSegment): Builder
    {
        $this->ensureOperatorIsSupported($operator);

        if (is_null($value)) {
            return $baseQuery;
        }

        if (is_null($value['value'] ?? null) && is_null($value['date'] ?? null)) {
            return $baseQuery;
        }

        $rate = match ($value['type']) {
            EngagementType::OpenRate->value => DB::raw('(1.0 * emails_opened / emails_received * 100)'),
            EngagementType::ClickRate->value => DB::raw('(1.0 * emails_clicked / emails_received * 100)'),
            EngagementType::LastOpenAt->value => 'last_open_at',
            EngagementType::LastClickAt->value => 'last_click_at',
            default => throw new Exception("Unsupported engagement type: {$value['type']}"),
        };

        if (in_array($rate, ['last_open_at', 'last_click_at'])) {
            $value = Date::parse($value['date']);
        } else {
            $value = (float) $value['value'];
            $value = DB::raw('1.0 * '.$value);
        }

        return $baseQuery
            ->when($value instanceof CarbonInterface, fn (Builder $query) => $query->whereNotNull($rate))
            ->when($tagSegment, fn (Builder $query) => $query->where('email_list_id', $tagSegment?->email_list_id))
            ->when($operator === ComparisonOperator::GreaterThanOrEquals, fn (Builder $query) => $query->where($rate, '>=', $value))
            ->when($operator === ComparisonOperator::SmallerThanOrEquals, fn (Builder $query) => $query->where($rate, '<=', $value));
    }
}
