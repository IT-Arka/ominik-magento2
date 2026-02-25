<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface NotifyInterface
{
    public const URL_STAGING = 'staging';
    public const STORE_ID_PRODUCTION = 1;
    public const STORE_ID_STAGING = 2;
    public const RESOURCE_PRICE = 'PRICE';
    public const RESOURCE_INVENTORY = 'INVENTORY';
    public const RESOURCE_AVAILABLE_CREDIT = 'AVAILABLE_CREDIT';

    /**
     * @api
     *
     * @return mixed
     */
    public function execute();
}
