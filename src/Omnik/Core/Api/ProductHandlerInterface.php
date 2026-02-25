<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface ProductHandlerInterface
{
    /**
     * @param array $registers
     * @return void
     */
    public function execute(array $registers): void;
}
