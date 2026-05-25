<?php

namespace Spatie\Mailcoach\Livewire\Audience;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Campaign\Models\Campaign;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class ListTrackingStatisticsComponent extends Component
{
    use UsesMailcoachModels;

    public EmailList $emailList;

    public bool $readyToLoad = false;

    public function mount(EmailList $emailList)
    {
        $this->emailList = $emailList;
    }

    public function loadData()
    {
        $this->readyToLoad = true;
    }

    public function render(): View
    {
        if (! $this->readyToLoad) {
            return view('mailcoach::app.emailLists.tracking-statistics');
        }

        $chart = $this->emailList->campaigns()
            ->sent()
            ->latest('sent_at')
            ->with('contentItems')
            ->get()
            ->sortBy('sent_at')
            ->filter(function (Campaign $campaign) {
                return $campaign->openRate() > 0
                    || $campaign->clickRate() > 0
                    || $campaign->unsubscribeRate() > 0
                    || $campaign->bounceRate() > 0;
            })
            ->take(15)
            ->map(function (Campaign $campaign) {
                return [
                    'label' => $campaign->name,
                    'openRate' => round($campaign->openRate() / 100, 2),
                    'clickRate' => round($campaign->clickRate() / 100, 2),
                    'unsubscribeRate' => round($campaign->unsubscribeRate() / 100, 2),
                    'bounceRate' => round($campaign->bounceRate() / 100, 2),
                ];
            });

        return view('mailcoach::app.emailLists.tracking-statistics', [
            'chart' => $chart,
            'averageOpenRate' => $this->averageOpenRate(),
            'averageClickRate' => $this->averageClickRate(),
            'averageUnsubscribeRate' => $this->averageUnsubscribeRate(),
            'averageBounceRate' => $this->averageBounceRate(),
        ]);
    }

    public function averageOpenRate(): float
    {
        return DB::connection(Mailcoach::getDatabaseConnection())
            ->table(self::getContentItemTableName())
            ->where('model_type', (new (self::getCampaignClass()))->getMorphClass())
            ->whereIn('model_id', $this->emailList->campaigns()->select('id'))
            ->average('open_rate') / 100;
    }

    public function averageClickRate(): float
    {
        return DB::connection(Mailcoach::getDatabaseConnection())
            ->table(self::getContentItemTableName())
            ->where('model_type', (new (self::getCampaignClass()))->getMorphClass())
            ->whereIn('model_id', $this->emailList->campaigns()->select('id'))
            ->average('click_rate') / 100;
    }

    public function averageUnsubscribeRate(): float
    {
        return DB::connection(Mailcoach::getDatabaseConnection())
            ->table(self::getContentItemTableName())
            ->where('model_type', (new (self::getCampaignClass()))->getMorphClass())
            ->whereIn('model_id', $this->emailList->campaigns()->select('id'))
            ->average('unsubscribe_rate') / 100;
    }

    public function averageBounceRate(): float
    {
        return DB::connection(Mailcoach::getDatabaseConnection())
            ->table(self::getContentItemTableName())
            ->where('model_type', (new (self::getCampaignClass()))->getMorphClass())
            ->whereIn('model_id', $this->emailList->campaigns()->select('id'))
            ->average('bounce_rate') / 100;
    }
}
