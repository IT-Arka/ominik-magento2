<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface ValidSelectComboInterface
{

    /**
     * @api
     *
     * @return bool
     */
    public function execute(): bool;
}
