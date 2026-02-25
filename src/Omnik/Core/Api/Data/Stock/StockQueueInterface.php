<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data\Stock;

interface StockQueueInterface
{
    public const EVENT = 'event';
    public const RESOURCE_TYPE = 'resource_type';
    public const RESOURCE_ID = 'resource_id';
    public const STOCK = 'stock';

    /**
     * @return string
     */
    public function getEvent(): string;

    /**
     * @param string $event
     * @return StockQueueInterface
     */
    public function setEvent(string $event): StockQueueInterface;

    /**
     * @return string
     */
    public function getResourceType(): string;

    /**
     * @param string $resourceType
     * @return StockQueueInterface
     */
    public function setResourceType(string $resourceType): StockQueueInterface;

    /**
     * @return string
     */
    public function getResourceId(): string;

    /**
     * @param string $resourceId
     * @return StockQueueInterface
     */
    public function setResourceId(string $resourceId): StockQueueInterface;

    /**
     * @return int
     */
    public function getStock(): int;

    /**
     * @param int $stock
     * @return StockQueueInterface
     */
    public function setStock(int $stock): StockQueueInterface;
}
