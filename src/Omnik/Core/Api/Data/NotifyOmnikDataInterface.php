<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;

interface NotifyOmnikDataInterface
{
    public const STATUS_NOT_INTEGRATED = 0;
    public const STATUS_INTEGRATED = 1;
    public const STATUS_ERROR = 2;
    public const STATUS_NOT_FOUND = 3;
    public const STATUS_NOT_FOUND_MAGENTO = 4;
    public const RESOURCE_TYPE_ORDER = 'ORDER';

    /**
     * @param string|null $methods
     * @return array
     */
    public function getInfoByStatus($methods = null): array;

    /**
     * @param int $id
     * @param int $statusId
     * @param string|null $errorBody
     * @return void
     */
    public function changeStatusNotify(int $id, int $statusId = self::STATUS_INTEGRATED, ?string $errorBody = null): void;

    public function changeAttempts(int $id): void;
}
