<?php

namespace Spatie\Mailcoach\Domain\Shared\Commands;

use Illuminate\Support\Collection;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;

class PruneCommand extends \Illuminate\Database\Console\PruneCommand
{
    use UsesMailcoachModels;

    protected $signature = 'mailcoach:prune
                                {--chunk=1000 : The number of models to retrieve per chunk of models to be deleted}
                                {--pretend : Display the number of prunable records found instead of deleting them}';

    protected $description = 'Prune Mailcoach models according to config.';

    protected function models(): Collection
    {
        return collect([
            self::getOpenClass() => config('mailcoach.prune_after_days.opens'),
            self::getClickClass() => config('mailcoach.prune_after_days.clicks'),
            self::getUnsubscribeClass() => config('mailcoach.prune_after_days.unsubscribes'),
            self::getSendFeedbackItemClass() => config('mailcoach.prune_after_days.send_feedback_items'),
            self::getSendClass() => config('mailcoach.prune_after_days.sends'),
            self::getTransactionalMailLogItemClass() => config('mailcoach.prune_after_days.transactional_mail_log_items'),
            self::getSubscriberImportClass() => config('mailcoach.prune_after_days.subscriber_imports'),
        ])->filter()->keys();
    }
}
