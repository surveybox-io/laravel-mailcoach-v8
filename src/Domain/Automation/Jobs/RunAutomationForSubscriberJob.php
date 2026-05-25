<?php

namespace Spatie\Mailcoach\Domain\Automation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Mailcoach\Domain\Audience\Models\Subscriber;
use Spatie\Mailcoach\Domain\Automation\Actions\ShouldAutomationRunForSubscriberAction;
use Spatie\Mailcoach\Domain\Automation\Enums\AutomationStatus;
use Spatie\Mailcoach\Domain\Automation\Models\Automation;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class RunAutomationForSubscriberJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesMailcoachModels;

    public $deleteWhenMissingModels = true;

    /** @var string */
    public $queue;

    /** @var ShouldAutomationRunForSubscriberAction */
    public $action;

    public int $uniqueFor = 60 * 60 * 3; // 3 hours

    public function __construct(
        public Automation $automation,
        public Subscriber $subscriber,
    ) {
        $this->queue = config('mailcoach.automation.perform_on_queue.run_automation_for_subscriber_job');
        $this->action = resolve(config('mailcoach.automation.actions.should_run_for_subscriber', ShouldAutomationRunForSubscriberAction::class));

        $this->connection ??= Mailcoach::getQueueConnection();
    }

    public function uniqueId(): string
    {
        return "{$this->automation->id}-{$this->subscriber->id}";
    }

    public function handle(): void
    {
        if ($this->automation->status !== AutomationStatus::Started) {
            return;
        }

        if (! $this->automation->emailList) {
            return;
        }

        if (! $this->action->execute($this->automation, $this->subscriber)) {
            return;
        }

        $this->automation->run($this->subscriber);
    }
}
