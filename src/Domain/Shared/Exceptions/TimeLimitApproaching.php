<?php

namespace Spatie\Mailcoach\Domain\Shared\Exceptions;

use Exception;

class TimeLimitApproaching extends Exception
{
    public static function make()
    {
        return new static;
    }
}
