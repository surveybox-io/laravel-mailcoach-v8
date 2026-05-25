<?php

namespace Spatie\Mailcoach\Domain\Shared\Support;

use InvalidArgumentException;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use RedisException;
use Spatie\Mailcoach\Mailcoach;
use Throwable;

class HorizonStatus
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PAUSED = 'paused';

    public function __construct(
        private ?MasterSupervisorRepository $masterSupervisorRepository = null
    ) {}

    public function is(string $status): bool
    {
        try {
            return $status === $this->get();
        } catch (Throwable) {
            return false;
        }
    }

    public function get(): string
    {
        /**
         * If we're not using Redis, we're probably not using Horizon
         */
        if (Mailcoach::getQueueDriver() !== 'redis') {
            return static::STATUS_ACTIVE;
        }

        try {
            $masters = $this->masterSupervisorRepository->all();
        } catch (RedisException|InvalidArgumentException) {
            $masters = false;
        }

        if (! $masters) {
            return static::STATUS_INACTIVE;
        }

        $isPaused = collect($masters)->contains(fn ($master) => $master->status === 'paused');

        return $isPaused
            ? static::STATUS_PAUSED
            : static::STATUS_ACTIVE;
    }
}
