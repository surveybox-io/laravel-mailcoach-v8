<?php

namespace Spatie\Mailcoach\Http\Api\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Spatie\Mailcoach\Domain\TransactionalMail\Models\TransactionalMail */
class TransactionalMailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => $this->contentItem->subject ?? '',
            'html' => $this->contentItem->html ?? '',
            'store_mail' => $this->store_mail,
            'created_at' => $this->created_at,
        ];
    }
}
