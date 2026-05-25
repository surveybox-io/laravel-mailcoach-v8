<?php

namespace Spatie\Mailcoach\Http\Api\Controllers\TransactionalMails;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Mailcoach\Domain\TransactionalMail\Models\TransactionalMailLogItem;
use Spatie\Mailcoach\Http\Api\Resources\TransactionalMailLogItemResource;

class ShowTransactionalMailController
{
    use AuthorizesRequests;

    public function __invoke(TransactionalMailLogItem $transactionalMail)
    {
        $this->authorize('view', $transactionalMail);

        return new TransactionalMailLogItemResource($transactionalMail);
    }
}
