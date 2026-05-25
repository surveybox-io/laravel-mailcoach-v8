<?php

namespace Spatie\Mailcoach\Domain\Automation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Spatie\Mailcoach\Database\Factories\TriggerFactory;
use Spatie\Mailcoach\Domain\Automation\Support\Triggers\AutomationTrigger;
use Spatie\Mailcoach\Domain\Shared\Models\Concerns\UsesDatabaseConnection;
use Spatie\Mailcoach\Domain\Shared\Models\HasUuid;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Throwable;

class Trigger extends Model
{
    use HasFactory;
    use HasUuid;
    use UsesDatabaseConnection;
    use UsesMailcoachModels;

    public $table = 'mailcoach_automation_triggers';

    protected $guarded = [];

    protected static function booted()
    {
        static::saved(function () {
            cache()->forget('automation-triggers');
        });
    }

    public function setTriggerAttribute(AutomationTrigger $value)
    {
        $this->attributes['trigger'] = json_encode(array_merge($value->toArray(), [
            'class' => $value::class,
            'uuid' => $this->uuid,
        ]));
    }

    public function getAutomationTrigger(): AutomationTrigger
    {
        return $this->trigger;
    }

    public function getTriggerAttribute(string $value): AutomationTrigger
    {
        // If the action is base64 encoded, decode it first
        // @todo Remove next major version
        if ($value === base64_encode(base64_decode($value, true))) {
            $value = base64_decode($value);
        }

        try {
            // @todo Remove next major version, only keep json_decode
            /** @var AutomationTrigger $trigger */
            $trigger = unserialize($value);
        } catch (Throwable) {
            $triggerData = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            $triggerClass = Arr::pull($triggerData, 'class');

            $trigger = $triggerClass::make($triggerData);
        }

        $trigger->uuid = $this->uuid;
        $trigger->setAutomation($this->automation);

        return $trigger;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Automation\Models\Automation, $this>
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(self::getAutomationClass());
    }

    protected static function newFactory(): TriggerFactory
    {
        return new TriggerFactory;
    }
}
