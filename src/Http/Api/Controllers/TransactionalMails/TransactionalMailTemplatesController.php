<?php

namespace Spatie\Mailcoach\Http\Api\Controllers\TransactionalMails;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Domain\TransactionalMail\Models\TransactionalMail;
use Spatie\Mailcoach\Http\Api\Queries\TransactionalMailQuery;
use Spatie\Mailcoach\Http\Api\Resources\TransactionalMailResource;

class TransactionalMailTemplatesController
{
    use AuthorizesRequests;
    use UsesMailcoachModels;

    public function index(TransactionalMailQuery $transactionalMailQuery)
    {
        $this->authorize('viewAny', static::getTransactionalMailClass());

        return TransactionalMailResource::collection($transactionalMailQuery->with(['contentItem'])->paginate());
    }

    public function show(TransactionalMail $transactionalMailTemplate)
    {
        $this->authorize('view', $transactionalMailTemplate);

        return TransactionalMailResource::make($transactionalMailTemplate);
    }
}
