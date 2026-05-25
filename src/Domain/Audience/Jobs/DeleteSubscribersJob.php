<?php

namespace Spatie\Mailcoach\Domain\Audience\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Mailcoach\Domain\Audience\Actions\Subscribers\DeleteSubscriberAction;
use Spatie\Mailcoach\Domain\Audience\Models\Subscriber;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class DeleteSubscribersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesMailcoachModels;

    private DeleteSubscriberAction $deleteAction;

    public function __construct(
        private array $subscriberIds,
    ) {
        $this->deleteAction = Mailcoach::getAudienceActionClass('delete_subscriber', DeleteSubscriberAction::class);
    }

    public function handle(): void
    {
        self::getSubscriberClass()::query()
            ->whereIn('id', $this->subscriberIds)
            ->each(function (Subscriber $subscriber) {
                $this->deleteAction->execute($subscriber);
            });
    }
}
