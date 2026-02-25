<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface NotifyTesteInterface
{

    /**
     * @api
     * @return mixed
     */
    public function execute();

    /**
     * @param mixed $type
     * @return void
     */
    public function runCron($type);
}
