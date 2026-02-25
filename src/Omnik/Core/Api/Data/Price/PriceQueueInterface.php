<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data\Price;

interface PriceQueueInterface
{
    public const EVENT = 'event';
    public const RESOURCE_TYPE = 'resource_type';
    public const RESOURCE_ID = 'resource_id';
    public const FROM_PRICE = 'from_price';
    public const PRICE = 'price';

    /**
     * @return string
     */
    public function getEvent(): string;

    /**
     * @param string $event
     * @return PriceQueueInterface
     */
    public function setEvent(string $event): PriceQueueInterface;

    /**
     * @return string
     */
    public function getResourceType(): string;

    /**
     * @param string $resourceType
     * @return PriceQueueInterface
     */
    public function setResourceType(string $resourceType): PriceQueueInterface;

    /**
     * @return string
     */
    public function getResourceId(): string;

    /**
     * @param string $resourceId
     * @return PriceQueueInterface
     */
    public function setResourceId(string $resourceId): PriceQueueInterface;

    /**
     * @return float
     */
    public function getFromPrice(): float;

    /**
     * @param float $fromPrice
     * @return PriceQueueInterface
     */
    public function setFromPrice(float $fromPrice): PriceQueueInterface;

    /**
     * @return float
     */
    public function getPrice(): float;

    /**
     * @param float $price
     * @return PriceQueueInterface
     */
    public function setPrice(float $price): PriceQueueInterface;
}
