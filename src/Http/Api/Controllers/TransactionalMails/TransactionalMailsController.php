<?php

namespace Spatie\Mailcoach\Http\Api\Controllers\TransactionalMails;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Http\Api\Queries\TransactionalMailLogItemQuery;
use Spatie\Mailcoach\Http\Api\Resources\TransactionalMailLogItemResource;

class TransactionalMailsController
{
    use AuthorizesRequests;
    use UsesMailcoachModels;

    public function __invoke(TransactionalMailLogItemQuery $transactionalMailsQuery)
    {
        $this->authorize('viewAny', static::getTransactionalMailLogItemClass());

        return TransactionalMailLogItemResource::collection($transactionalMailsQuery->with(['contentItem'])->paginate());
    }
}
