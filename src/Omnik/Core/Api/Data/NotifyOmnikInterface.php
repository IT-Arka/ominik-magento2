<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;

interface NotifyOmnikInterface
{
    public const TABLE_NAME = 'omnik_notify_omnik';
    public const ENTITY_ID = 'entity_id';
    public const SELLER = 'seller';
    public const EVENT = 'event';
    public const RESOURCE_TYPE = 'resource_type';
    public const RESOURCE_ID = 'resource_id';
    public const RESOURCE_URI = 'resource_uri';
    public const RESOURCE_MARKET_PLACE_ID = 'resource_market_place_id';
    public const RESOURCE_MARKET_PLACE_URI = 'resource_market_place_uri';
    public const STATUS = 'status';
    public const DATE = 'date';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const STORE_ID = 'store_id';
    public const FROM_PRICE = 'from_price';
    public const PRICE = 'price';
    public const STOCK = 'stock';

    public const ERROR_BODY = 'error_body';

    public const ATTEMPTS = 'attempts';

    /**
     * @param int $entity_id
     * @return mixed
     */
    public function setId($entity_id);

    /**
     * @return int
     */
    public function getId();

    /**
     * @param string $seller
     * @return NotifyOmnikInterface
     */
    public function setSeller(string $seller): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getSeller(): string;

    /**
     * @param string $event
     * @return NotifyOmnikInterface
     */
    public function setEvent(string $event): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getEvent(): string;

    /**
     * @param string $resource_type
     * @return NotifyOmnikInterface
     */
    public function setResourceType(string $resource_type): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getResourceType(): string;

    /**
     * @param string $resource_id
     * @return NotifyOmnikInterface
     */
    public function setResourceId(string $resource_id): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getResourceId(): string;

    /**
     * @param string $resource_uri
     * @return NotifyOmnikInterface
     */
    public function setResourceUri(string $resource_uri): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getResourceUri(): string;

    /**
     * @param string $resource_market_place_id
     * @return NotifyOmnikInterface
     */
    public function setResourceMarketPlaceId(string $resource_market_place_id): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getResourceMarketPlaceId(): string;

    /**
     * @param string $resource_market_place_uri
     * @return NotifyOmnikInterface
     */
    public function setResourceMarketPlaceUri(string $resource_market_place_uri): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getResourceMarketPlaceUri(): string;

    /**
     * @param int $status
     * @return NotifyOmnikInterface
     */
    public function setStatus(int $status): NotifyOmnikInterface;

    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @param string $date
     * @return NotifyOmnikInterface
     */
    public function setDate(string $date): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getDate(): string;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $created_at
     * @return NotifyOmnikInterface
     */
    public function setCreatedAt(string $created_at): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * @param string $updated_at
     * @return NotifyOmnikInterface
     */
    public function setUpdatedAt(string $updated_at): NotifyOmnikInterface;

    /**
     * @param int $storeId
     * @return NotifyOmnikInterface
     */
    public function setStoreId(int $storeId): NotifyOmnikInterface;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param float $fromPrice
     * @return NotifyOmnikInterface
     */
    public function setFromPrice(float $fromPrice): NotifyOmnikInterface;

    /**
     * @return float
     */
    public function getFromPrice(): float;

    /**
     * @param float $price
     * @return NotifyOmnikInterface
     */
    public function setPrice(float $price): NotifyOmnikInterface;

    /**
     * @return float
     */
    public function getPrice(): float;

    /**
     * @param int $stock
     * @return NotifyOmnikInterface
     */
    public function setStock(int $stock): NotifyOmnikInterface;

    /**
     * @return int
     */
    public function getStock(): int;

    /**
     * @param string $errorBody
     * @return NotifyOmnikInterface
     */
    public function setErrorBody(string $errorBody): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getErrorBody(): NotifyOmnikInterface;

    /**
     * @param string $attempts
     * @return NotifyOmnikInterface
     */
    public function setAttempts($attempts): NotifyOmnikInterface;

    /**
     * @return string
     */
    public function getAttempts();
}
