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
     * @return array
     */
    public function getProductModerationApproved(): array;

    /**
     * @param int $id
     * @param int $status
     * @return void
     */
    public function changeStatusNotify(int $id, int $status): void;
}
