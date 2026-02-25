<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Price;

use Omnik\Core\Api\Data\Price\PriceQueueInterface;
use Magento\Framework\Model\AbstractModel;

class PriceQueue extends AbstractModel implements PriceQueueInterface
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
     * @return PriceQueueInterface
     */
    public function setEvent(string $event): PriceQueueInterface
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
     * @return PriceQueueInterface
     */
    public function setResourceType(string $resourceType): PriceQueueInterface
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
     * @return PriceQueueInterface
     */
    public function setResourceId(string $resourceId): PriceQueueInterface
    {
        return $this->setData(self::RESOURCE_ID, $resourceId);
    }

    /**
     * @return float
     */
    public function getFromPrice(): float
    {
        return $this->getData(self::FROM_PRICE);
    }

    /**
     * @param float $fromPrice
     * @return PriceQueueInterface
     */
    public function setFromPrice(float $fromPrice): PriceQueueInterface
    {
        return $this->setData(self::FROM_PRICE, $fromPrice);
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->getData(self::PRICE);
    }

    /**
     * @param float $price
     * @return PriceQueueInterface
     */
    public function setPrice(float $price): PriceQueueInterface
    {
        return $this->setData(self::PRICE, $price);
    }
}
