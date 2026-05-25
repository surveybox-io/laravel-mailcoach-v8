<?php

namespace Spatie\Mailcoach\Domain\Campaign\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Mailcoach\Domain\Campaign\Models\Campaign;
use Spatie\Mailcoach\Domain\Content\Models\ContentItem;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class CreateCampaignSendsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesMailcoachModels;

    public bool $deleteWhenMissingModels = true;

    public $tries = 1;

    /** @var string */
    public $queue;

    public function uniqueId(): string
    {
        $key = md5(implode($this->subscriberIds));

        return "{$this->campaign->id}-{$key}";
    }

    public function uniqueFor(): int
    {
        return max(1, $this->contentItem->sendTimeInMinutes()) * 60;
    }

    public function __construct(
        protected Campaign $campaign,
        protected ContentItem $contentItem,
        protected array $subscriberIds,
    ) {
        $this->queue = config('mailcoach.campaigns.perform_on_queue.send_campaign_job');

        $this->connection ??= Mailcoach::getQueueConnection();
    }

    public function handle(): void
    {
        if ($this->campaign->isCancelled()) {
            return;
        }

        $existing = DB::connection(Mailcoach::getDatabaseConnection())
            ->table(self::getSendTableName())
            ->where('content_item_id', $this->contentItem->id)
            ->whereIn('subscriber_id', $this->subscriberIds)
            ->pluck('subscriber_id')
            ->toArray();

        $new = array_diff($this->subscriberIds, $existing);

        self::getSendClass()::insert(array_map(function (int $subscriberId) {
            return [
                'content_item_id' => $this->contentItem->id,
                'subscriber_id' => $subscriberId,
                'uuid' => Str::uuid()->toString(),
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }, $new));
    }
}
