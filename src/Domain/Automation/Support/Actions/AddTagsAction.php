<?php

namespace Spatie\Mailcoach\Domain\Automation\Support\Actions;

use Spatie\Mailcoach\Domain\Automation\Models\ActionSubscriber;
use Spatie\Mailcoach\Domain\Automation\Support\Actions\Enums\ActionCategoryEnum;

class AddTagsAction extends AutomationAction
{
    public static function getCategory(): ActionCategoryEnum
    {
        return ActionCategoryEnum::Tags;
    }

    public static function make(array $data): self
    {
        $tags = explode(',', $data['tags'] ?? '');

        $tags = array_map('trim', $tags);

        return new self($tags);
    }

    public static function getIcon(): string
    {
        return 'heroicon-s-tag';
    }

    public function __construct(public array $tags)
    {
        parent::__construct();
    }

    public static function getName(): string
    {
        return (string) __mc('Add tags');
    }

    public static function getComponent(): ?string
    {
        return 'mailcoach::add-tags-action';
    }

    public function toArray(): array
    {
        $tags = array_map('trim', $this->tags);

        return [
            'tags' => implode(',', $tags),
        ];
    }

    public function run(ActionSubscriber $actionSubscriber): void
    {
        $actionSubscriber->subscriber->addTags($this->tags);
    }
}
