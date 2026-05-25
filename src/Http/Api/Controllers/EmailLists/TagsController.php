<?php

namespace Spatie\Mailcoach\Http\Api\Controllers\EmailLists;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Spatie\Mailcoach\Domain\Audience\Enums\TagType;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Audience\Models\Tag;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Http\Api\Controllers\Concerns\RespondsToApiRequests;
use Spatie\Mailcoach\Http\Api\Queries\EmailListTagsQuery;
use Spatie\Mailcoach\Http\Api\Requests\TagRequest;
use Spatie\Mailcoach\Http\Api\Resources\TagResource;

class TagsController
{
    use AuthorizesRequests;
    use RespondsToApiRequests;
    use UsesMailcoachModels;

    public function index(EmailList $emailList)
    {
        $query = new EmailListTagsQuery($emailList);
        $query->addSelect(DB::raw("*, '{$emailList->uuid}' as email_list_uuid"));

        $this->authorize('viewAny', static::getTagClass());

        $segments = $query->paginate();

        return TagResource::collection($segments);
    }

    public function show(EmailList $emailList, Tag $tag)
    {
        $this->authorize('view', $tag);

        $tag->email_list_uuid = $emailList->uuid;

        return new TagResource($tag);
    }

    public function store(EmailList $emailList, TagRequest $tagRequest)
    {
        $tagClass = self::getTagClass();

        $this->authorize('create', $tagClass);

        $tag = $emailList->tags()->firstOrCreate([
            'name' => $tagRequest->validated('name'),
            'type' => TagType::Default,
        ], [
            'visible_in_preferences' => $tagRequest->validated('visible_in_preferences', false),
        ]);

        $tag->email_list_uuid = $emailList->uuid;

        return TagResource::make($tag);
    }

    public function update(EmailList $emailList, Tag $tag, TagRequest $tagRequest)
    {
        $this->authorize('update', $tag);

        $tag->update([
            'name' => $tagRequest->validated('name'),
            'visible_in_preferences' => $tagRequest->validated('visible_in_preferences', false),
        ]);

        $tag->email_list_uuid = $emailList->uuid;

        return TagResource::make($tag);
    }

    public function destroy(EmailList $emailList, Tag $tag)
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return $this->respondOk();
    }
}
