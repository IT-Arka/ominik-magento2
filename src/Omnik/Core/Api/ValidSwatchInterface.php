<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface ValidSwatchInterface
{

    /**
     * @api
     *
     * @return bool
     */
    public function execute(): bool;
}
