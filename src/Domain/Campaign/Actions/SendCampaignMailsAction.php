<?php

namespace Spatie\Mailcoach\Domain\Campaign\Actions;

use Carbon\CarbonInterface;
use Spatie\Mailcoach\Domain\Campaign\Jobs\SendCampaignMailJob;
use Spatie\Mailcoach\Domain\Campaign\Models\Campaign;
use Spatie\Mailcoach\Domain\Content\Models\ContentItem;
use Spatie\Mailcoach\Domain\Shared\Models\Send;
use Spatie\Mailcoach\Domain\Shared\Support\HorizonStatus;
use Spatie\Mailcoach\Domain\Shared\Traits\HaltsWhenApproachingTimeLimit;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class SendCampaignMailsAction
{
    use HaltsWhenApproachingTimeLimit;
    use UsesMailcoachModels;

    public function execute(Campaign $campaign, ?CarbonInterface $stopExecutingAt = null): void
    {
        foreach ($campaign->contentItems as $contentItem) {
            $contentItem->setRelation('model', $campaign);

            $this->retryDispatchForStuckSends($contentItem);

            if (! $contentItem->sends()->undispatched()->count()) {
                if ($contentItem->allSendsCreated() && ! $contentItem->allMailSendingJobsDispatched()) {
                    $contentItem->markAsAllMailSendingJobsDispatched();
                }

                continue;
            }

            $this->dispatchMailSendingJobs($contentItem, $stopExecutingAt);
        }
    }

    /**
     * Determines how many sends we can realistically process
     * within the time that this job is allowed to run.
     * With a maximum of 1000 to keep performance.
     */
    protected function chunkSize(string $mailer): int
    {
        $mailsPerTimespan = config("mail.mailers.{$mailer}.mails_per_timespan", 10);
        $timespanInSeconds = config("mail.mailers.{$mailer}.timespan_in_seconds", 1);
        $runTimeInSeconds = config('mailcoach.campaigns.send_campaign_maximum_job_runtime_in_seconds');

        return max(min(1_000, $mailsPerTimespan / $timespanInSeconds * $runTimeInSeconds), 1);
    }

    /**
     * Dispatch pending sends again that have
     * not been processed in a realistic time
     */
    protected function retryDispatchForStuckSends(ContentItem $contentItem): void
    {
        $retryQuery = $contentItem
            ->sends()
            ->pending()
            ->where('sending_job_dispatched_at', '<', now()->subSeconds(config('mailcoach.campaigns.send_campaign_mail_job_retry_until_seconds', 60 * 60 * 3)));

        if ($retryQuery->count() === 0) {
            return;
        }

        $contentItem->update(['all_sends_dispatched_at' => null]);
        $retryQuery->update(['sending_job_dispatched_at' => null]);
    }

    protected function dispatchMailSendingJobs(ContentItem $contentItem, ?CarbonInterface $stopExecutingAt = null): void
    {
        $undispatchedCount = $contentItem->sends()->undispatched()->count();
        $mailsPerTimespan = config("mail.mailers.{$contentItem->getMailerKey()}.mails_per_timespan", 10);
        $timespanInSeconds = config("mail.mailers.{$contentItem->getMailerKey()}.timespan_in_seconds", 10);
        $mailsPer2Minutes = ($mailsPerTimespan / $timespanInSeconds) * 60 * 2;

        while ($undispatchedCount > 0) {
            $dispatchedCount = $contentItem->sends()->pending()->dispatched()->count();

            /**
             * If we have more sends dispatched than we can process in 2 minutes,
             * or 100 if the limit is less than 100 mails per 2 minutes, stop.
             */
            if ($dispatchedCount > max(100, $mailsPer2Minutes)) {
                break;
            }

            $contentItem
                ->sends()
                ->undispatched()
                ->select(self::getSendTableName().'.id')
                ->orderBy('sending_job_dispatched_at')
                ->lazy($this->chunkSize($contentItem->getMailerKey()))
                ->each(function (Send $send) use ($contentItem, $stopExecutingAt) {
                    // should horizon be used, and it is paused, stop dispatching jobs
                    $this->dispatchJobForSend($send, $contentItem->getMailerKey(), $stopExecutingAt);

                    $this->haltWhenApproachingTimeLimit($stopExecutingAt);
                });

            $undispatchedCount = $contentItem->sends()->undispatched()->count();

            $this->haltWhenApproachingTimeLimit($stopExecutingAt);
        }

        if (! $contentItem->allSendsCreated()) {
            return;
        }

        $contentItem->markAsAllMailSendingJobsDispatched();
    }

    protected function dispatchJobForSend(
        Send $send,
        string $mailer,
        ?CarbonInterface $stopExecutingAt,
    ): void {
        if (app(HorizonStatus::class)->is(HorizonStatus::STATUS_PAUSED)) {
            return;
        }

        $limiter = Mailcoach::getDispatchLimiter($mailer);

        while ($limiter->exceeded()) {
            $this->haltWhenApproachingTimeLimit($stopExecutingAt);

            // Sleep at least 100ms if backoff is 0 for some reason.
            usleep(max(100_000, $limiter->backoff() * 1_000_000));

            $limiter = Mailcoach::getDispatchLimiter($mailer);
        }

        $limiter->hit();

        dispatch(new SendCampaignMailJob($send));

        $send->markAsSendingJobDispatched();
    }
}
