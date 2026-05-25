<?php

namespace Spatie\Mailcoach\Domain\ConditionBuilder\Data;

class SubscriberAttributeQueryConditionData extends ConditionData
{
    protected function __construct(
        public ?string $attribute,
        public string|int|float|array|null $value,
    ) {}

    public static function make(?string $attribute, string|int|float|array|null $value): self
    {
        return new self($attribute, $value);
    }

    public static function fromArray(array $data): static
    {
        return new static($data['attribute'], $data['value']);
    }

    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'value' => $this->value,
        ];
    }
}
