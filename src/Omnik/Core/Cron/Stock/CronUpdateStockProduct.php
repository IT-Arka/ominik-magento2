<?php

declare(strict_types=1);

namespace Omnik\Core\Cron\Stock;

use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Model\Cron\SaveHandler\Handler\Stock\HandlerPool;

class CronUpdateStockProduct
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
        $registers = $this->notifyOmnikDataInterface->getInfoByStatus();

        if (!empty($registers)) {
            $this->handlerPool->execute($registers);
        }
    }
}
