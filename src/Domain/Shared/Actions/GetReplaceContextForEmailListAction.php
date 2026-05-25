<?php

namespace Spatie\Mailcoach\Domain\Shared\Actions;

use Spatie\Mailcoach\Domain\Audience\Models\EmailList;

class GetReplaceContextForEmailListAction
{
    public function __construct(
    ) {}

    public function execute(?EmailList $emailList): array
    {
        if (! $emailList) {
            return [];
        }

        $attributes = array_merge(
            $emailList->extra_attributes->toArray(), [
                'uuid' => $emailList->uuid,
                'name' => $emailList->name,
                'website_url' => $emailList->websiteUrl(),
                'websiteUrl' => $emailList->websiteUrl(),
                'extra_attributes' => $emailList->extra_attributes->toArray(),
            ]);

        return [
            'list' => $attributes,
            'emailList' => $attributes,
            'email_list' => $attributes,
            'websiteUrl' => $emailList->websiteUrl(),
            'website_url' => $emailList->websiteUrl(),
        ];
    }
}
