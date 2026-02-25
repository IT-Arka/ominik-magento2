<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Magento\Framework\Model\AbstractModel;
use Omnik\Core\Api\Data\NotifyOmnikInterface;

class NotifyOmnik extends AbstractModel implements NotifyOmnikInterface
{
    public function _construct()
    {
        $this->_init(\Omnik\Core\Model\ResourceModel\NotifyOmnik::class);
    }

    /**
     * @param string $seller
     * @return NotifyOmnikInterface
     */
    public function setSeller(string $seller): NotifyOmnikInterface
    {
        return $this->setData(self::SELLER, $seller);
    }

    /**
     * @return string
     */
    public function getSeller(): string
    {
        return $this->getData(self::SELLER);
    }

    /**
     * @param string $event
     * @return NotifyOmnikInterface
     */
    public function setEvent(string $event): NotifyOmnikInterface
    {
        return $this->setData(self::EVENT, $event);
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->getData(self::EVENT);
    }

    /**
     * @param string $resource_type
     * @return NotifyOmnikInterface
     */
    public function setResourceType(string $resource_type): NotifyOmnikInterface
    {
        return $this->setData(self::RESOURCE_TYPE, $resource_type);
    }

    /**
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->getData(self::RESOURCE_TYPE);
    }

    /**
     * @param string $resource_id
     * @return NotifyOmnikInterface
     */
    public function setResourceId(string $resource_id): NotifyOmnikInterface
    {
        return $this->setData(self::RESOURCE_ID, $resource_id);
    }

    /**
     * @return string
     */
    public function getResourceId(): string
    {
        return $this->getData(self::RESOURCE_ID);
    }

    /**
     * @param string $resource_uri
     * @return NotifyOmnikInterface
     */
    public function setResourceUri(string $resource_uri): NotifyOmnikInterface
    {
        return $this->setData(self::RESOURCE_URI, $resource_uri);
    }

    /**
     * @return string
     */
    public function getResourceUri(): string
    {
        return $this->getData(self::RESOURCE_URI);
    }

    /**
     * @param string $resource_market_place_id
     * @return NotifyOmnikInterface
     */
    public function setResourceMarketPlaceId(string $resource_market_place_id): NotifyOmnikInterface
    {
        return $this->setData(self::RESOURCE_MARKET_PLACE_ID, $resource_market_place_id);
    }

    /**
     * @return string
     */
    public function getResourceMarketPlaceId(): string
    {
        return $this->getData(self::RESOURCE_MARKET_PLACE_ID);
    }

    /**
     * @param string $resource_market_place_uri
     * @return NotifyOmnikInterface
     */
    public function setResourceMarketPlaceUri(string $resource_market_place_uri): NotifyOmnikInterface
    {
        return $this->setData(self::RESOURCE_MARKET_PLACE_URI, $resource_market_place_uri);
    }

    /**
     * @return string
     */
    public function getResourceMarketPlaceUri(): string
    {
        return $this->getData(self::RESOURCE_MARKET_PLACE_URI);
    }

    /**
     * @param int $status
     * @return NotifyOmnikInterface
     */
    public function setStatus(int $status): NotifyOmnikInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return (int) $this->getData(self::STATUS);
    }

    /**
     * @param string $date
     * @return NotifyOmnikInterface
     */
    public function setDate(string $date): NotifyOmnikInterface
    {
        return $this->setData(self::DATE, $date);
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->getData(self::DATE);
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param string $created_at
     * @return NotifyOmnikInterface
     */
    public function setCreatedAt(string $created_at): NotifyOmnikInterface
    {
        return $this->setData(self::CREATED_AT, $created_at);
    }

    /**
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @param string $updated_at
     * @return NotifyOmnikInterface
     */
    public function setUpdatedAt(string $updated_at): NotifyOmnikInterface
    {
        return $this->setData(self::UPDATED_AT, $updated_at);
    }

    /**
     * @param int $storeId
     * @return NotifyOmnikInterface
     */
    public function setStoreId(int $storeId): NotifyOmnikInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->getData(self::STORE_ID);
    }

    public function setFromPrice(float $fromPrice): NotifyOmnikInterface
    {
        return $this->setData(self::FROM_PRICE, $fromPrice);
    }

    /**
     * @return float
     */
    public function getFromPrice(): float
    {
        return $this->getData(self::FROM_PRICE);
    }

    /**
     * @param float $price
     * @return NotifyOmnikInterface
     */
    public function setPrice(float $price): NotifyOmnikInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->getData(self::PRICE);
    }

    /**
     * @param int $stock
     * @return NotifyOmnikInterface
     */
    public function setStock(int $stock): NotifyOmnikInterface
    {
        return $this->setData(self::STOCK, $stock);
    }

    /**
     * @return int
     */
    public function getStock(): int
    {
        return $this->getData(self::STOCK);
    }

    /**
     * @param string $errorBody
     * @return NotifyOmnikInterface
     */
    public function setErrorBody(string $errorBody): NotifyOmnikInterface
    {
        return $this->setData(self::ERROR_BODY, $errorBody);
    }

    /**
     * @return string
     */
    public function getErrorBody(): NotifyOmnikInterface
    {
        return $this->getData(self::ERROR_BODY);
    }

    /**
     * @param string $attempts
     * @return NotifyOmnikInterface
     */
    public function setAttempts($attempts): NotifyOmnikInterface
    {
        return $this->setData(self::ATTEMPTS, $attempts);
    }

    /**
     * @return string
     */
    public function getAttempts(): string
    {
        return $this->getData(self::ATTEMPTS);
    }
}
