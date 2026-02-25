<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron;

use Omnik\Core\Api\ProductHandlerInterface;

class HandlerPool
{
    /**
     * @var array
     */
    private array $handlers;

    /**
     * @param array $handlers
     */
    public function __construct(
        array $handlers = []
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
            if (!$handler instanceof ProductHandlerInterface) {
                throw new \InvalidArgumentException(__(
                    'Type %1 is not an instance of %2',
                    get_class($handler),
                    ProductHandlerInterface::class
                ));
            }

            $handler->execute($registers);
        }
    }
}
