<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface ProductSellerInterface
{

    /**
     * @param string $sku
     * @return string
     */
    public function getSellerId(string $sku): string;
}
