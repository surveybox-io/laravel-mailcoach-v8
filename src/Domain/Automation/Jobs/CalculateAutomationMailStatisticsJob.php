<?php

namespace Spatie\Mailcoach\Domain\Automation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Spatie\Mailcoach\Domain\Automation\Enums\AutomationStatus;
use Spatie\Mailcoach\Domain\Automation\Models\AutomationMail;
use Spatie\Mailcoach\Domain\Content\Jobs\CalculateStatisticsJob;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class CalculateAutomationMailStatisticsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesMailcoachModels;

    public int $uniqueFor = 60;

    public function __construct(protected ?int $automationMailId = null)
    {
        $this->onQueue(config('mailcoach.perform_on_queue.schedule'));
        $this->connection ??= Mailcoach::getQueueConnection();
    }

    public function handle()
    {
        Cache::put('mailcoach-last-schedule-run', now());

        $this->automationMailId
            ? CalculateStatisticsJob::dispatchSync(self::getAutomationMailClass()::find($this->automationMailId)->contentItem)
            : $this->calculateStatisticsOfAutomationMails();
    }

    protected function calculateStatisticsOfAutomationMails(): void
    {
        $automationUuids = static::getAutomationClass()::query()
            ->where('status', AutomationStatus::Started)
            ->pluck('uuid');

        static::getAutomationMailClass()::query()
            ->inAutomations($automationUuids)
            ->each(function (AutomationMail $automationMail) {
                if (! $automationMail instanceof (self::getAutomationMailClass())) {
                    $automationMail = self::getAutomationMailClass()::find($automationMail->getKey());
                }

                $automationMail?->contentItem->dispatchCalculateStatistics();
            });
    }
}
