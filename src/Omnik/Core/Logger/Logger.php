<?php
declare(strict_types=1);

namespace Omnik\Core\Logger;

use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    /**
     * @param mixed $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        $name,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
    }

}
