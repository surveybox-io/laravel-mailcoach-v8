<?php

namespace Spatie\Mailcoach\Domain\Campaign\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Mailcoach\Domain\Campaign\Actions\SendCampaignMailsAction;
use Spatie\Mailcoach\Domain\Content\Models\ContentItem;
use Spatie\Mailcoach\Domain\Shared\Exceptions\TimeLimitApproaching;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class SendCampaignMailsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesMailcoachModels;

    public function __construct()
    {
        $this->onQueue(config('mailcoach.campaigns.perform_on_queue.send_campaign_job'));
        $this->connection ??= Mailcoach::getQueueConnection();
    }

    public function uniqueFor(): int
    {
        return max(60, config('mailcoach.campaigns.send_campaign_maximum_job_runtime_in_seconds'));
    }

    public function handle(): void
    {
        /** @var \Spatie\Mailcoach\Domain\Campaign\Actions\SendCampaignMailsAction $sendCampaignMailsAction */
        $sendCampaignMailsAction = Mailcoach::getCampaignActionClass('send_campaign_mails', SendCampaignMailsAction::class);

        $maxRuntimeInSeconds = max(60, config('mailcoach.campaigns.send_campaign_maximum_job_runtime_in_seconds'));

        $stopExecutingAt = now()->addSeconds($maxRuntimeInSeconds);

        try {
            self::getContentItemClass()::query()
                /** @var Builder<\Spatie\Mailcoach\Domain\Shared\Models\Send> $query */
                ->whereHas('sends', fn (Builder $query) => $query->pending())
                ->where('model_type', (new (self::getCampaignClass()))->getMorphClass())
                ->with(['model'])
                ->lazyById()
                ->each(function (ContentItem $contentItem) use ($stopExecutingAt, $sendCampaignMailsAction) {
                    if (! $campaign = $contentItem->getModel()) {
                        return;
                    }

                    /** @var ?\Spatie\Mailcoach\Domain\Campaign\Models\Campaign $campaign */
                    $sendCampaignMailsAction->execute($campaign, $stopExecutingAt);
                });
        } catch (TimeLimitApproaching) {
            return;
        }
    }
}
