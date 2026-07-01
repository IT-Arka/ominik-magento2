<?php

declare(strict_types=1);

namespace Omnik\Core\Cron;

use Omnik\Core\Api\Data\NotifyProductModerationDataInterface;
use Omnik\Core\Model\Cron\HandlerPool;

class ProductCron
{
    /**
     * @var HandlerPool
     */
    private HandlerPool $handlerPool;

    /**
     * @var NotifyProductModerationDataInterface
     */
    private NotifyProductModerationDataInterface $notifyProductModerationData;

    /**
     * @param HandlerPool $handlerPool
     * @param NotifyProductModerationDataInterface $notifyProductModerationData
     */
    public function __construct(
        HandlerPool $handlerPool,
        NotifyProductModerationDataInterface $notifyProductModerationData
    ) {
        $this->handlerPool = $handlerPool;
        $this->notifyProductModerationData = $notifyProductModerationData;
    }

    /**
     * Atomically claim a disjoint batch of pending registers and process it.
     *
     * The claim marks rows as RUNNING in a single UPDATE, so the five parallel
     * product cron jobs each take a distinct partition with no race condition.
     *
     * @return void
     */
    public function execute(): void
    {
        $registers = $this->notifyProductModerationData->claimProductModerationApproved(
            NotifyProductModerationDataInterface::MAX_ATTEMPTS
        );

        if (!empty($registers)) {
            $this->handlerPool->execute($registers);
        }
    }
}
