<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Handler;

use Omnik\Core\Api\NotifyHandlerInterface;

class Handler
{
    /**
     * @var array|mixed
     */
    private array $handlers;

    /**
     * @param $handlers
     */
    public function __construct(
        $handlers = []
    ) {
        $this->handlers = $handlers;
    }

    /**
     * @param array $registers
     * @return void
     */
    public function execute(array $registers)
    {
        foreach ($this->handlers as $handler) {
            if (!$handler instanceof NotifyHandlerInterface) {
                throw new \InvalidArgumentException(__(
                    'Type %1 is not an instance of %2',
                    get_class($handler),
                    NotifyHandlerInterface::class
                ));
            }

            $handler->execute($registers);
        }
    }
}
