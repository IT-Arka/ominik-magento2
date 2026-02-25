<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Stock;

use Omnik\Core\Api\Data\Stock\StockQueueInterface;
use Magento\Framework\Model\AbstractModel;

class StockQueue extends AbstractModel implements StockQueueInterface
{
    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->getData(self::EVENT);
    }

    /**
     * @param string $event
     * @return StockQueueInterface
     */
    public function setEvent(string $event): StockQueueInterface
    {
        return $this->setData(self::EVENT, $event);
    }

    /**
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->getData(self::RESOURCE_TYPE);
    }

    /**
     * @param string $resourceType
     * @return StockQueueInterface
     */
    public function setResourceType(string $resourceType): StockQueueInterface
    {
        return $this->setData(self::RESOURCE_TYPE, $resourceType);
    }

    /**
     * @return string
     */
    public function getResourceId(): string
    {
        return $this->getData(self::RESOURCE_ID);
    }

    /**
     * @param string $resourceId
     * @return StockQueueInterface
     */
    public function setResourceId(string $resourceId): StockQueueInterface
    {
        return $this->setData(self::RESOURCE_ID, $resourceId);
    }

    /**
     * @return int
     */
    public function getStock(): int
    {
        return $this->getData(self::STOCK);
    }

    /**
     * @param int $stock
     * @return StockQueueInterface
     */
    public function setStock(int $stock): StockQueueInterface
    {
        return $this->setData(self::STOCK, $stock);
    }
}
