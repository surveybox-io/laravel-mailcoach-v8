<?php

namespace Spatie\Mailcoach\Domain\Shared\Actions;

use Spatie\Mailcoach\Domain\Automation\Models\AutomationMail;
use Spatie\Mailcoach\Domain\Campaign\Models\Campaign;
use Spatie\Mailcoach\Domain\Content\Models\ContentItem;
use Spatie\Mailcoach\Domain\Shared\Models\Sendable;

class GetReplaceContextForSendableAction
{
    public function __construct(
        private GetReplaceContextForEmailListAction $getReplaceContextForEmailListAction,
    ) {}

    public function execute(?Sendable $sendable, ?ContentItem $contentItem = null): array
    {
        if (! $sendable) {
            return [];
        }

        $contentItem ??= $sendable->contentItem;

        $context = $contentItem?->getTemplateFieldValues() ?? [];
        $context['subject'] = $contentItem?->subject;
        $context['fromEmail'] = $contentItem?->from_email;
        $context['fromName'] = $contentItem?->from_name;
        $context['replyToEmail'] = $contentItem?->reply_to_email;
        $context['replyToName'] = $contentItem?->reply_to_name;

        if ($sendable instanceof AutomationMail) {
            return array_merge($context, [
                'automation_mail' => $sendable->attributesToArray(),
                'webviewUrl' => $sendable->webviewUrl(),
            ]);
        }

        if ($sendable instanceof Campaign) {
            $context = array_merge($context, $this->getReplaceContextForEmailListAction->execute($sendable->emailList));
            $context = array_merge($context, [
                'campaign' => $sendable->attributesToArray(),
                'websiteCampaignUrl' => $sendable->emailList?->websiteEnabled()
                    ? $sendable->websiteUrl()
                    : '',
                'webviewUrl' => $sendable->webviewUrl(),
            ]);

            return $context;
        }

        return [];
    }
}
