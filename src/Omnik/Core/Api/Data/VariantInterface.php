<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;

interface VariantInterface
{
    public const TABLE_NAME = 'omnik_product_variant';
    public const ENTITY_ID = 'entity_id';
    public const TENANT = 'tenant';
    public const OPERATOR = 'operator';
    public const VARIANT = 'variant';
    public const VARIANT_ID = 'variant_id';
    public const VARIANT_CODE = 'variant_code';
    public const VARIANT_NAME = 'variant_name';
    public const VARIANT_OPTION = 'variant_option';
    public const CREATE_DATE = 'create_date';
    public const LAST_UPDATE = 'last_update';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return string
     */
    public function getTenant(): string;

    /**
     * @param string $tenant
     * @return VariantInterface
     */
    public function setTenant(string $tenant): VariantInterface;

    /**
     * @return string
     */
    public function getOperator(): string;

    /**
     * @param string $operator
     * @return VariantInterface
     */
    public function setOperator(string $operator): VariantInterface;

    /**
     * @return string
     */
    public function getVariant(): string;

    /**
     * @param string $variant
     * @return VariantInterface
     */
    public function setVariant(string $variant): VariantInterface;

    /**
     * @return string
     */
    public function getVariantId(): string;

    /**
     * @param string $variantId
     * @return VariantInterface
     */
    public function setVariantId(string $variantId): VariantInterface;

    /**
     * @return string
     */
    public function getVariantCode(): string;

    /**
     * @param string $variantCode
     * @return VariantInterface
     */
    public function setVariantCode(string $variantCode): VariantInterface;

    /**
     * @return string
     */
    public function getVariantName(): string;

    /**
     * @param string $variantName
     * @return VariantInterface
     */
    public function setVariantName(string $variantName): VariantInterface;

    /**
     * @return string
     */
    public function getVariantOption(): string;

    /**
     * @param string $variantOption
     * @return VariantInterface
     */
    public function setVariantOption(string $variantOption): VariantInterface;

    /**
     * @return string
     */
    public function getCreateDate(): string;

    /**
     * @param string $createDate
     * @return VariantInterface
     */
    public function setCreateDate(string $createDate): VariantInterface;

    /**
     * @return string
     */
    public function getLastUpdate(): string;

    /**
     * @param string $lastUpdate
     * @return VariantInterface
     */
    public function setLastUpdate(string $lastUpdate): VariantInterface;
}
