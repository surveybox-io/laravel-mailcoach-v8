<?php

namespace Spatie\Mailcoach\Domain\Template\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;

trait HasTemplate
{
    use UsesMailcoachModels;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Mailcoach\Domain\Template\Models\Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(self::getTemplateClass());
    }
}
