<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface PriceProductsSectionInterface
{

    /**
     * @api
     *
     * @return string
     */
    public function execute(): string;
}
