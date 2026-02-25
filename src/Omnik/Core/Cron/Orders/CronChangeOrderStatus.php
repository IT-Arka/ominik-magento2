<?php

declare(strict_types=1);

namespace Omnik\Core\Cron\Orders;

use Omnik\Core\Model\Cron\SaveHandler\Handler\Orders\HandlerPool;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;

class CronChangeOrderStatus
{
    /**
     * @var HandlerPool
     */
    private HandlerPool $handlerPool;

    /**
     * @var NotifyOmnikDataInterface
     */
    private NotifyOmnikDataInterface $notifyOmnikDataInterface;

    /**
     * @param HandlerPool $handlerPool
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     */
    public function __construct(
        HandlerPool $handlerPool,
        NotifyOmnikDataInterface $notifyOmnikDataInterface
    ) {
        $this->handlerPool = $handlerPool;
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $registers = $this->notifyOmnikDataInterface->getOrderInfoByStatus();

        if (!empty($registers)) {
            $this->handlerPool->execute($registers);
        }
    }
}
