<?php

namespace Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\Subscribers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Mailcoach\Domain\Audience\Models\TagSegment;
use Spatie\Mailcoach\Domain\Campaign\Models\Campaign;
use Spatie\Mailcoach\Domain\ConditionBuilder\Conditions\QueryCondition;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ComparisonOperator;
use Spatie\Mailcoach\Domain\ConditionBuilder\Enums\ConditionCategory;
use Spatie\Mailcoach\Domain\Content\Models\ContentItem;

class SubscriberReceivedCampaignQueryCondition extends QueryCondition
{
    public const KEY = 'subscriber_received_campaign';

    public function key(): string
    {
        return self::KEY;
    }

    public function comparisonOperators(): array
    {
        return [
            ComparisonOperator::Any,
            ComparisonOperator::None,
            ComparisonOperator::Equals,
            ComparisonOperator::NotEquals,
        ];
    }

    public function category(): ConditionCategory
    {
        return ConditionCategory::Actions;
    }

    public function getComponent(): string
    {
        return 'mailcoach::subscriber-received-campaign-condition';
    }

    public function apply(Builder $baseQuery, ComparisonOperator $operator, mixed $value, ?TagSegment $tagSegment): Builder
    {
        $this->ensureOperatorIsSupported($operator);

        if ($operator === ComparisonOperator::Any) {
            $emailList = $tagSegment?->emailList->id;

            $campaigns = Campaign::query()->where('email_list_id', $emailList)->pluck('id');
            $value = ContentItem::query()->whereIn('model_id', $campaigns)->pluck('id');

            return $baseQuery->whereHas('sends', function (Builder $query) use ($value) {
                $query->sent()->whereIn('content_item_id', $value);
            });
        }

        if ($operator === ComparisonOperator::None) {
            return $baseQuery
                ->where(function (Builder $query) {
                    $query
                        ->whereDoesntHave('sends')
                        ->orWhereHas('sends.contentItem', function (Builder $query) {
                            $query->whereNot('model_type', (new (self::getCampaignClass()))->getMorphClass());
                        });
                });
        }

        /** @var ?Campaign $campaign */
        $campaign = self::getCampaignClass()::find($value);

        if ($campaign instanceof Collection) {
            $campaign = $campaign->first();
        }

        if ($campaign === null) {
            return $baseQuery;
        }

        if ($operator === ComparisonOperator::Equals) {
            $value = $campaign->contentItem->id;

            return $baseQuery->whereHas('sends', function (Builder $query) use ($value) {
                $query->sent()->where('content_item_id', $value);
            });
        }

        $value = $campaign->contentItems->pluck('id')->all();

        return $baseQuery
            ->where(function (Builder $query) use ($value) {
                $query->whereDoesntHave('sends', function (Builder $query) use ($value) {
                    $query->sent()->whereIn('content_item_id', $value);
                })->orWhereHas('sends', function (Builder $query) use ($value) {
                    $query->pending()->whereIn('content_item_id', $value);
                });
            });
    }
}
