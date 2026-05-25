<?php

namespace Spatie\Mailcoach\Domain\Campaign\Jobs;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Audience\Models\Subscriber;
use Spatie\Mailcoach\Domain\Campaign\Models\Campaign;
use Spatie\Mailcoach\Domain\Shared\Models\Send;
use Spatie\Mailcoach\Mailcoach;
use Spatie\RateLimitedMiddleware\RateLimited;

class SendCampaignMailJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public int $maxExceptions = 3;

    /** @var string */
    public $queue;

    public function uniqueId(): int
    {
        return $this->pendingSend->id;
    }

    public function uniqueFor(): int
    {
        return config('mailcoach.campaigns.send_campaign_mail_job_retry_until_seconds', 60 * 60 * 3);
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addSeconds(config('mailcoach.campaigns.send_campaign_mail_job_retry_until_seconds', 60 * 60 * 3));
    }

    public function __construct(public Send $pendingSend)
    {
        $this->connection ??= Mailcoach::getQueueConnection();
        $this->queue = config('mailcoach.campaigns.perform_on_queue.send_mail_job');
    }

    public function handle(): void
    {
        $this->pendingSend = $this->pendingSend->fresh([
            'contentItem.model.emailList',
            'subscriber',
        ]);

        $campaign = $this->pendingSend->contentItem->model;

        if (! $campaign instanceof Campaign || $campaign->isCancelled()) {
            if (! $this->pendingSend->wasAlreadySent()) {
                $this->pendingSend->delete();
            }

            return;
        }

        $subscriber = $this->pendingSend->subscriber;

        if (! $subscriber) {
            if (! $this->pendingSend->wasAlreadySent()) {
                $this->pendingSend->delete();
            }

            return;
        }

        if (! $campaign->getSegment()->shouldSend($subscriber)) {
            $this->pendingSend->invalidate();

            return;
        }

        if (! $this->isValidSubscriptionForEmailList($subscriber, $campaign->emailList)) {
            $this->pendingSend->invalidate();

            return;
        }

        /** @var \Spatie\Mailcoach\Domain\Shared\Actions\SendMailAction $sendMailAction */
        $sendMailAction = Mailcoach::getSharedActionClass('send_mail', \Spatie\Mailcoach\Domain\Shared\Actions\SendMailAction::class);

        $sendMailAction->execute($this->pendingSend);
    }

    public function middleware(): array
    {
        if (! $model = $this->pendingSend->contentItem?->model) {
            return [];
        }

        if (! $model instanceof Campaign) {
            return [];
        }

        if ($model->isCancelled()) {
            return [];
        }

        $mailer = $model->getMailerKey();
        $mailsPerTimespan = config("mail.mailers.{$mailer}.mails_per_timespan", 10);
        $timespanInSeconds = config("mail.mailers.{$mailer}.timespan_in_seconds", 1);

        $rateLimitedMiddleware = (new RateLimited(useRedis: false))
            ->key('mailer-throttle-'.$mailer)
            ->allow($mailsPerTimespan)
            ->everySeconds($timespanInSeconds)
            ->releaseAfterSeconds($timespanInSeconds + 1)
            ->dontRelease(function ($job) use ($timespanInSeconds) {
                $retryUntil = $job->retryUntil() instanceof DateTimeInterface
                    ? $job->retryUntil()
                    : now()->addSeconds($job->retryUntil());

                return $retryUntil <= now()->addSeconds($timespanInSeconds);
            });

        return [$rateLimitedMiddleware];
    }

    protected function isValidSubscriptionForEmailList(Subscriber $subscriber, EmailList $emailList): bool
    {
        if (! $subscriber->isSubscribed()) {
            return false;
        }

        if ((int) $subscriber->email_list_id !== (int) $emailList->id) {
            return false;
        }

        return true;
    }
}
