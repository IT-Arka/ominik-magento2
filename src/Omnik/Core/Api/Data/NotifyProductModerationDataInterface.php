<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;

interface NotifyProductModerationDataInterface
{
    public const STATUS_NOT_INTEGRATED = 0;
    public const STATUS_INTEGRATED = 1;
    public const STATUS_ERROR = 2;
    public const STATUS_NOT_FOUND = 3;
    public const STATUS_DATA_INCONSISTENT = 4;
    public const STATUS_RUNNING = 5;
    public const EVENT_MODERATION_APPROVED = 'MODERATION_APPROVED';
    public const RESOURCE_PRODUCT = 'PRODUCT';
    public const RESULT_NOT_PUBLISHED = 'not-published';
    public const QTY_LIMIT_REGISTERS = 100;

    /**
     * Maximum retry attempts before a register transitioning through transient
     * failures is parked in STATUS_ERROR instead of being retried again.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * @return array
     */
    public function getProductModerationApproved(): array;

    /**
     * Atomically claim a batch of approved-moderation registers for this worker.
     *
     * @param int|null $maxAttempts
     * @return array claimed registers (already marked as RUNNING)
     */
    public function claimProductModerationApproved(?int $maxAttempts = null): array;

    /**
     * @param int $id
     * @param int $status
     * @param string|null $errorBody
     * @return void
     */
    public function changeStatusNotify(int $id, int $status, ?string $errorBody = null): void;

    /**
     * Increment the attempts counter of a register and return the new value.
     *
     * @param int $id
     * @return int the attempts counter after incrementing
     */
    public function changeAttempts(int $id): int;

    /**
     * Release a claimed register back to the pending queue for retry.
     *
     * @param int $id
     * @return void
     */
    public function releaseClaim(int $id): void;
}
