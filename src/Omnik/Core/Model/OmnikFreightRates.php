<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\Data\OmnikFreightRatesInterface;
use Omnik\Core\Model\ResourceModel\OmnikFreightRates as OmnikFreightRatesResourceModel;
use Magento\Framework\Model\AbstractModel;

class OmnikFreightRates extends AbstractModel implements OmnikFreightRatesInterface
{
    public function _construct()
    {
        $this->_init(OmnikFreightRatesResourceModel::class);
    }

    /**
     * @param $entity_id
     * @return OmnikFreightRates|mixed
     */
    public function setId($entity_id)
    {
        return $this->setData(self::ENTITY_ID, $entity_id);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @param int $quoteId
     * @return OmnikFreightRatesInterface
     */
    public function setQuoteId(int $quoteId): OmnikFreightRatesInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @return int
     */
    public function getQuoteId(): int
    {
        return (int)$this->getData(self::QUOTE_ID);
    }

    /**
     * @param string $body
     * @return OmnikFreightRatesInterface
     */
    public function setBody(string $body): OmnikFreightRatesInterface
    {
        return $this->setData(self::BODY, $body);
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->getData(self::BODY);
    }

    /**
     * @param string $deliveryMethodId
     * @return OmnikFreightRatesInterface
     */
    public function setDeliveryMethodId(string $deliveryMethodId): OmnikFreightRatesInterface
    {
        return $this->setData(self::DELIVERY_METHOD_ID, $deliveryMethodId);
    }

    /**
     * @return string
     */
    public function getDeliveryMethodId(): string
    {
        return $this->getData(self::DELIVERY_METHOD_ID);
    }

    /**
     * @param string $sellerTenant
     * @return OmnikFreightRatesInterface
     */
    public function setSellerTenant(string $sellerTenant): OmnikFreightRatesInterface
    {
        return $this->setData(self::SELLER_TENANT, $sellerTenant);
    }

    /**
     * @return string
     */
    public function getSellerTenant(): string
    {
        return $this->getData(self::SELLER_TENANT);
    }

    /**
     * @param string $createdAt
     * @return OmnikFreightRatesInterface
     */
    public function setCreatedAt(string $createdAt): OmnikFreightRatesInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }
}
