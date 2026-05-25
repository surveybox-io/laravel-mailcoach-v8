<?php

namespace Spatie\Mailcoach\Domain\Automation\Support\Actions;

use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Date;
use Spatie\Mailcoach\Domain\Automation\Enums\WaitUnit;

/** https://github.com/briannesbitt/Carbon/issues/2855 */
class GetIntervalAction
{
    public function execute(int $length, string $unit): CarbonInterval
    {
        if ($unit !== WaitUnit::Weekdays->value) {
            return CarbonInterval::$unit($length);
        }

        $additionalDays = 0;
        $period = CarbonPeriod::create(Date::now(), '1 day', Date::now()->addDays($length));

        /** @var \Carbon\CarbonInterface $day */
        foreach ($period as $index => $day) {
            if ($day->isWeekend()) {
                $additionalDays++;
            }

            if ($length === $index && $day->isSaturday()) {
                $additionalDays++;
            }
        }

        return CarbonInterval::days($length + $additionalDays);
    }
}
