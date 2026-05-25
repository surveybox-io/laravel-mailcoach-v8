<?php

namespace Spatie\Mailcoach\Livewire;

use Composer\InstalledVersions;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Spatie\Mailcoach\Domain\Shared\Support\HorizonStatus;
use Spatie\Mailcoach\Domain\Shared\Support\Version;
use Spatie\Mailcoach\Mailcoach;

class DebugComponent extends Component
{
    public function render()
    {
        $horizonStatus = app(HorizonStatus::class);
        $versionInfo = resolve(Version::class);

        $databaseDriver = Mailcoach::getDatabaseDriver();
        $databaseVersion = DB::getServerVersion();

        $horizonVersion = InstalledVersions::getVersion('laravel/horizon');
        $filamentVersion = InstalledVersions::getVersion('filament/support');
        $webhookTableCount = \Spatie\WebhookClient\Models\WebhookCall::query()
            ->where('name', 'like', '%-feedback')
            ->whereNull('processed_at')
            ->count();
        $lastScheduleRun = Cache::get('mailcoach-last-schedule-run');
        $usesVapor = InstalledVersions::isInstalled('laravel/vapor-core');
        $scheduledJobs = $this->getScheduledJobs();
        $filesystems = $this->getFilesystems();

        return view('mailcoach::app.debug', compact(
            'versionInfo',
            'horizonStatus',
            'databaseDriver',
            'databaseVersion',
            'horizonVersion',
            'filamentVersion',
            'webhookTableCount',
            'lastScheduleRun',
            'usesVapor',
            'scheduledJobs',
            'filesystems',
        ))->layout('mailcoach::app.layouts.app');
    }

    /** @return Collection<Event> */
    private function getScheduledJobs(): Collection
    {
        $app = app()->make(Kernel::class);
        $app->bootstrap();
        $schedule = app(Schedule::class);

        return collect($schedule->events())
            ->filter(fn ($event) => Str::contains($event->command, 'mailcoach'));
    }

    private function getFilesystems(): array
    {
        $disks = [
            'import_subscribers_disk' => config('mailcoach.audience.import_subscribers_disk'),
            'import_disk' => config('mailcoach.import_disk'),
            'export_disk' => config('mailcoach.export_disk'),
            'tmp_disk' => config('mailcoach.tmp_disk'),
        ];

        return array_map(
            fn ($disk) => [
                'disk' => $disk,
                'visibility' => config("filesystems.disks.{$disk}.visibility", 'private'),
            ],
            $disks
        );
    }
}
