<?php

namespace Spatie\Mailcoach\Domain\Shared\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Console\WorkCommand as BaseWorkCommand;
use Illuminate\Queue\Worker;

class WorkCommand extends BaseWorkCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'mailcoach:work
                            {connection? : The name of the queue connection to work}
                            {--name=default : The name of the worker}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs (Deprecated)}
                            {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
                            {--max-jobs=0 : The number of jobs to process before stopping}
                            {--max-time=0 : The maximum number of seconds the worker should run}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--rest=0 : Number of seconds to rest between jobs}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}
                            {--json : Output the queue worker information as JSON}';

    protected $description = 'Run Mailcoach queues in preset priority';

    public function __construct()
    {
        parent::__construct(app(Worker::class, [
            'isDownForMaintenance' => fn () => app()->isDownForMaintenance(),
        ]), app(Cache::class));
    }

    protected function getQueue($connection): string
    {
        return implode(',', [
            'default',
            'mailcoach-schedule',
            'mailcoach',
            'send-campaign',
            'send-mail',
            'send-automation-mail',
            'mailcoach-feedback',
        ]);
    }
}
