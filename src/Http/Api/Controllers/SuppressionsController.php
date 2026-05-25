<?php

namespace Spatie\Mailcoach\Http\Api\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Mailcoach\Domain\Audience\Models\Suppression;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Http\Api\Controllers\Concerns\RespondsToApiRequests;
use Spatie\Mailcoach\Http\Api\Queries\SuppressionsQuery;
use Spatie\Mailcoach\Http\Api\Requests\SuppressionRequest;
use Spatie\Mailcoach\Http\Api\Resources\SuppressionResource;

class SuppressionsController
{
    use AuthorizesRequests;
    use RespondsToApiRequests;
    use UsesMailcoachModels;

    public function index(SuppressionsQuery $suppressionsQuery)
    {
        $this->authorize('viewAny', self::getSuppressionClass());

        $suppressions = $suppressionsQuery->paginate();

        return SuppressionResource::collection($suppressions);
    }

    public function show(Suppression $suppression)
    {
        $this->authorize('view', $suppression);

        return new SuppressionResource($suppression);
    }

    public function store(SuppressionRequest $request)
    {
        $this->authorize('create', self::getSuppressionClass());

        $suppression = self::getSuppressionClass()::fromAdmin($request->get('email'));

        return new SuppressionResource($suppression);
    }

    public function destroy(Suppression $suppression)
    {
        $this->authorize('delete', $suppression);

        $suppression->delete();

        return $this->respondOk();
    }
}
