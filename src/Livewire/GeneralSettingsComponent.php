<?php

namespace Spatie\Mailcoach\Livewire;

use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Mailcoach\Domain\Settings\Support\AppConfiguration\AppConfiguration;
use Spatie\Mailcoach\Domain\Settings\Support\TimeZone;
use Spatie\Mailcoach\Mailcoach;

class GeneralSettingsComponent extends Component
{
    public string $name = '';

    public string $timezone = '';

    public string $url = '';

    public string $storage_url = '';

    public string $from_address = '';

    public function rules()
    {
        return [
            'name' => ['required'],
            'timezone' => ['required', Rule::in(TimeZone::all())],
            'url' => ['required', 'url'],
            'storage_url' => ['required', 'url'],
            'from_address' => ['required', config('mailcoach.audience.email_validation_rule', 'email:strict,dns')],
        ];
    }

    public function mount(AppConfiguration $appConfiguration)
    {
        $this->name = $appConfiguration->name ?? config('app.name');
        $this->timezone = $appConfiguration->timezone ?? config('mailcoach.timezone') ?? config('app.timezone');
        $this->url = $appConfiguration->url ?? config('app.url');
        $this->storage_url = $appConfiguration->storage_url ?? config('filesystems.disks.public.url');
        $this->from_address = $appConfiguration->from_address ?? config('mail.from.address') ?? '';
    }

    public function save()
    {
        resolve(AppConfiguration::class)->put($this->validate());

        Mailcoach::restartQueues();

        notify(__mc('The app configuration was saved.'));
    }

    public function render()
    {
        $timeZones = TimeZone::all();

        return view('mailcoach::app.configuration.app.edit', compact('timeZones'))
            ->layout('mailcoach::app.layouts.settings', ['title' => __mc('General')]);
    }
}
