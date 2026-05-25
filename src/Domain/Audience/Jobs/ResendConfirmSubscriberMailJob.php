<?php

namespace Spatie\Mailcoach\Domain\Audience\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Mailcoach\Domain\Audience\Actions\Subscribers\SendConfirmSubscriberMailAction;
use Spatie\Mailcoach\Domain\Audience\Models\Subscriber;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;

class ResendConfirmSubscriberMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesMailcoachModels;

    public function __construct(
        private array $subscriberIds,
    ) {}

    public function handle(SendConfirmSubscriberMailAction $action): void
    {
        self::getSubscriberClass()::query()
            ->whereIn('id', $this->subscriberIds)
            ->each(function (Subscriber $subscriber) use ($action) {
                $action->execute($subscriber, isReminder: true);
            });
    }
}
