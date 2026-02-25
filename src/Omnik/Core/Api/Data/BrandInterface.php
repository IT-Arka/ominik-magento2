<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;

interface BrandInterface
{
    public const TABLE_NAME = 'omnik_brand';
    public const ENTITY_ID = 'entity_id';
    public const TENANT = 'tenant';
    public const OPERATOR = 'operator';
    public const BRAND_ID = 'brand_id';
    public const BRAND_CODE = 'brand_code';
    public const BRAND_NAME = 'brand_name';
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
     * @return BrandInterface
     */
    public function setTenant(string $tenant): BrandInterface;

    /**
     * @return string
     */
    public function getOperator(): string;

    /**
     * @param string $operator
     * @return BrandInterface
     */
    public function setOperator(string $operator): BrandInterface;

    /**
     * @return string
     */
    public function getBrandId(): string;

    /**
     * @param string $brandId
     * @return BrandInterface
     */
    public function setBrandId(string $brandId): BrandInterface;

    /**
     * @return string
     */
    public function getBrandCode(): string;

    /**
     * @param string $brandCode
     * @return BrandInterface
     */
    public function setBrandCode(string $brandCode): BrandInterface;

    /**
     * @return string
     */
    public function getBrandName(): string;

    /**
     * @param string $brandName
     * @return BrandInterface
     */
    public function setBrandName(string $brandName): BrandInterface;

    /**
     * @return string
     */
    public function getCreateDate(): string;

    /**
     * @param string $createDate
     * @return BrandInterface
     */
    public function setCreateDate(string $createDate): BrandInterface;

    /**
     * @return string
     */
    public function getLastUpdate(): string;

    /**
     * @param string $lastUpdate
     * @return BrandInterface
     */
    public function setLastUpdate(string $lastUpdate): BrandInterface;
}
