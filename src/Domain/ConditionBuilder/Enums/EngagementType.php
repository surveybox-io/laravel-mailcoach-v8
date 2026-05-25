<?php

namespace Spatie\Mailcoach\Domain\ConditionBuilder\Enums;

enum EngagementType: string
{
    case OpenRate = 'open_rate';
    case ClickRate = 'click_rate';
    case LastOpenAt = 'last_open_at';
    case LastClickAt = 'last_click_at';
}
