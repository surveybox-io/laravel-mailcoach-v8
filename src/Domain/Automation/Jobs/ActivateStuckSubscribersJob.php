<?php

namespace Spatie\Mailcoach\Domain\Automation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Spatie\Mailcoach\Domain\Automation\Models\Automation;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class ActivateStuckSubscribersJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesMailcoachModels;

    public function __construct(public Automation $automation)
    {
        $this->onQueue(config('mailcoach.perform_on_queue.schedule'));

        $this->connection ??= Mailcoach::getQueueConnection();
    }

    public function handle()
    {
        /*
         * When an automation action gets deleted while there are subscribers in it,
         * those subscribers will become stuck. All previous actions will
         * have set a completed_at value, so no action will get picked up anymore
         *
         * This query searches for subscribers in that situation. It will clear
         * the completed_at value from the latest action, so the automation will get
         * picked up from there.
         */
        $actionSubscriberTable = self::getActionSubscriberTableName();
        $automationActionTable = self::getAutomationActionTableName();

        $ids = DB::table($actionSubscriberTable)
            ->select([
                'subscriber_id',
                DB::raw('max(mailcoach_automation_action_subscriber.id) as latest_id'),
            ])
            ->whereIn('action_id', function ($query) use ($automationActionTable) {
                $query->select('id')
                    ->from($automationActionTable)
                    ->where('automation_id', $this->automation->id);
            })
            ->groupBy('subscriber_id')
            ->havingRaw('count(subscriber_id) = count(completed_at)')
            ->havingRaw('count(halted_at) = 0')
            ->pluck('latest_id');

        DB::table($actionSubscriberTable)
            ->whereIn('id', $ids)
            ->update([
                'completed_at' => null,
            ]);
    }

    public function uniqueId(): string
    {
        return (string) $this->automation->id;
    }
}
