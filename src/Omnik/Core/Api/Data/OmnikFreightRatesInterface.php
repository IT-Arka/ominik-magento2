<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;
interface OmnikFreightRatesInterface
{
    public const TABLE_NAME = 'omnik_omnik_freight_rates';
    public const ENTITY_ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const DELIVERY_METHOD_ID = 'delivery_method_id';
    public const SELLER_TENANT = 'seller_tenant';
    public const BODY = 'body';
    public const CREATED_AT = 'created_at';
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
     * @param int $quoteId
     * @return OmnikFreightRatesInterface
     */
    public function setQuoteId(int $quoteId): OmnikFreightRatesInterface;

    /**
     * @return int
     */
    public function getQuoteId(): int;

    /**
     * @param string $deliveryMethodId
     * @return OmnikFreightRatesInterface
     */
    public function setDeliveryMethodId(string $deliveryMethodId): OmnikFreightRatesInterface;

    /**
     * @return string
     */
    public function getDeliveryMethodId(): string;

    /**
     * @param string $body
     * @return OmnikFreightRatesInterface
     */
    public function setBody(string $body): OmnikFreightRatesInterface;

    /**
     * @return string
     */
    public function getBody(): string;

    /**
     * @param string $createdAt
     * @return OmnikFreightRatesInterface
     */
    public function setCreatedAt(string $createdAt): OmnikFreightRatesInterface;

    /**
     * @return string
     */
    public function getCreatedAt(): string;
}
