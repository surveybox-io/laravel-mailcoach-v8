<?php

namespace Spatie\Mailcoach\Domain\Automation\Actions;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Spatie\Mailcoach\Domain\Automation\Jobs\SendAutomationMailJob;
use Spatie\Mailcoach\Domain\Shared\Models\Send;
use Spatie\Mailcoach\Domain\Shared\Support\HorizonStatus;
use Spatie\Mailcoach\Domain\Shared\Traits\HaltsWhenApproachingTimeLimit;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class SendAutomationMailsAction
{
    use HaltsWhenApproachingTimeLimit;
    use UsesMailcoachModels;

    public function execute(?CarbonInterface $stopExecutingAt = null): void
    {
        $this->retryDispatchForStuckSends();

        self::getSendClass()::query()
            ->undispatched()
            ->whereHas('contentItem', function (Builder $query) {
                /** @var \Spatie\Mailcoach\Domain\Automation\Models\AutomationMail $automationMail */
                $automationMail = new (self::getAutomationMailClass());
                $query->where('model_type', $automationMail->getMorphClass());
            })
            ->with([
                'contentItem:id,model_type,model_id',
                'contentItem.model:id',
                'subscriber:id,email_list_id',
                'subscriber.emailList:id,automation_mailer',
            ])
            ->lazyById()
            ->each(function (Send $send) use ($stopExecutingAt) {
                $mailer = $send->getMailerKey();

                $limiter = Mailcoach::getDispatchLimiter($mailer);

                // should horizon be used, and it is paused, stop dispatching jobs
                if (! app(HorizonStatus::class)->is(HorizonStatus::STATUS_PAUSED)) {
                    while ($limiter->exceeded()) {
                        $this->haltWhenApproachingTimeLimit($stopExecutingAt);

                        // Sleep at least 100ms if backoff is 0 for some reason.
                        usleep(max(100_000, $limiter->backoff() * 1_000_000));

                        $limiter = Mailcoach::getDispatchLimiter($mailer);
                    }

                    $limiter->hit();

                    dispatch(new SendAutomationMailJob($send));

                    $send->markAsSendingJobDispatched();
                }

                $this->haltWhenApproachingTimeLimit($stopExecutingAt);
            });
    }

    /**
     * Dispatch pending sends again that have
     * not been processed in the 30 minutes
     */
    protected function retryDispatchForStuckSends(): void
    {
        $retryQuery = self::getSendClass()::query()
            ->whereHas('contentItem', function (Builder $query) {
                /** @var \Spatie\Mailcoach\Domain\Automation\Models\AutomationMail $automationMail */
                $automationMail = new (self::getAutomationMailClass());
                $query->where('model_type', $automationMail->getMorphClass());
            })
            ->pending()
            ->where('sending_job_dispatched_at', '<', now()->subMinutes(30));

        if ($retryQuery->count() === 0) {
            return;
        }

        $retryQuery->each(function (Send $send) {
            dispatch(new SendAutomationMailJob($send));

            $send->markAsSendingJobDispatched();
        });
    }
}
