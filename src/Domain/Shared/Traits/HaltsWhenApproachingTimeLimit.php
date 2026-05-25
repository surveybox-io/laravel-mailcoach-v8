<?php

namespace Spatie\Mailcoach\Domain\Shared\Traits;

use Carbon\CarbonInterface;
use Spatie\Mailcoach\Domain\Shared\Exceptions\TimeLimitApproaching;

trait HaltsWhenApproachingTimeLimit
{
    protected function haltWhenApproachingTimeLimit(?CarbonInterface $stopExecutingAt): void
    {
        if (is_null($stopExecutingAt)) {
            return;
        }

        if ($stopExecutingAt->diffInSeconds(absolute: true) > 10) {
            return;
        }

        throw TimeLimitApproaching::make();
    }
}
