<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface PricePerSellerInterface
{

    /**
     * @api
     *
     * @return mixed
     */
    public function execute();
}
