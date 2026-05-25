<?php

namespace Spatie\Mailcoach\Http\Api\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Spatie\Mailcoach\Domain\Audience\Models\Suppression */
class SuppressionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'email' => $this->email,
            'reason' => $this->reason,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
