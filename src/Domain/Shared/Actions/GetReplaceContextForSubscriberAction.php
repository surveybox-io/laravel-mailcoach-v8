<?php

namespace Spatie\Mailcoach\Domain\Shared\Actions;

use Spatie\Mailcoach\Domain\Audience\Models\Subscriber;
use Spatie\Mailcoach\Domain\Audience\Models\Tag;
use Spatie\Mailcoach\Domain\Shared\Models\Send;

class GetReplaceContextForSubscriberAction
{
    public function __construct(
        private GetReplaceContextForEmailListAction $getReplaceContextForEmailListAction
    ) {}

    public function execute(?Subscriber $subscriber, ?Send $send = null): array
    {
        if (! $subscriber) {
            return [];
        }

        $context = [];

        $context = array_merge($context, $this->getReplaceContextForEmailListAction->execute($subscriber->emailList));
        $context = array_merge($context, $this->getContextForSubscriber($subscriber, $send));

        return $context;
    }

    protected function getContextForSubscriber(Subscriber $subscriber, ?Send $send = null): array
    {
        $context = [
            'unsubscribeUrl' => $subscriber->unsubscribeUrl($send),
            'preferencesUrl' => $subscriber->preferencesUrl($send),
            'subscriber' => array_merge(
                $subscriber->extra_attributes->toArray(),
                array_filter([
                    'uuid' => $subscriber->uuid,
                    'first_name' => $subscriber->first_name,
                    'last_name' => $subscriber->last_name,
                    'email' => $subscriber->email,
                    'subscribed_at' => $subscriber->subscribed_at,
                    'extra_attributes' => $subscriber->extra_attributes->toArray(),
                    'tags' => $subscriber->tags->pluck('name')->toArray(),
                ]),
            ),
            'tags' => $subscriber->tags->pluck('name')->toArray(),
        ];

        $tagUrls = $subscriber->tags->mapWithKeys(function (Tag $tag) use ($subscriber, $send) {
            return [$tag->name => $subscriber->unsubscribeTagUrl($tag->name, $send)];
        })->toArray();

        $context['unsubscribeTag'] = $tagUrls;

        return $context;
    }
}
