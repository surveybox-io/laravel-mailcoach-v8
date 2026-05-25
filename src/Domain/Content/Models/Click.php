<?php

namespace Spatie\Mailcoach\Domain\Content\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Mailcoach\Database\Factories\ClickFactory;
use Spatie\Mailcoach\Domain\Shared\Models\Concerns\UsesDatabaseConnection;
use Spatie\Mailcoach\Domain\Shared\Models\HasUuid;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;

class Click extends Model
{
    use HasFactory;
    use HasUuid;
    use MassPrunable;
    use UsesDatabaseConnection;
    use UsesMailcoachModels;

    public $table = 'mailcoach_clicks';

    protected $guarded = [];

    protected $casts = [
        'first_clicked_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Shared\Models\Send, $this>
     */
    public function send(): BelongsTo
    {
        return $this->belongsTo(self::getSendClass(), 'send_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Content\Models\Link, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(self::getLinkClass(), 'link_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Audience\Models\Subscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(self::getSubscriberClass(), 'subscriber_id');
    }

    protected static function newFactory(): ClickFactory
    {
        return new ClickFactory;
    }

    public function prunable(): Builder
    {
        if (! config('mailcoach.prune_after_days.clicks')) {
            throw new Exception('No prune setting defined for '.self::class);
        }

        return static::where('created_at', '<=', now()->subDays(config('mailcoach.prune_after_days.clicks')));
    }
}
