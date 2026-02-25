<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\Data\BrandInterface;
use Omnik\Core\Model\ResourceModel\Brand as BrandResourceModel;
use Magento\Framework\Model\AbstractModel;

class Brand extends AbstractModel implements BrandInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BrandResourceModel::class);
    }

    /**
     * @return string
     */
    public function getTenant(): string
    {
        return $this->getData(self::TENANT);
    }

    /**
     * @param string $tenant
     * @return BrandInterface
     */
    public function setTenant(string $tenant): BrandInterface
    {
        return $this->setData(self::TENANT, $tenant);
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->getData(self::OPERATOR);
    }

    /**
     * @param string $operator
     * @return BrandInterface
     */
    public function setOperator(string $operator): BrandInterface
    {
        return $this->setData(self::OPERATOR, $operator);
    }

    /**
     * @return string
     */
    public function getBrandId(): string
    {
        return $this->getData(self::BRAND_ID);
    }

    /**
     * @param string $brandId
     * @return BrandInterface
     */
    public function setBrandId(string $brandId): BrandInterface
    {
        return $this->setData(self::BRAND_ID, $brandId);
    }

    /**
     * @return string
     */
    public function getBrandCode(): string
    {
        return $this->getData(self::BRAND_CODE);
    }

    /**
     * @param string $brandCode
     * @return BrandInterface
     */
    public function setBrandCode(string $brandCode): BrandInterface
    {
        return $this->setData(self::BRAND_CODE, $brandCode);
    }

    /**
     * @return string
     */
    public function getBrandName(): string
    {
        return $this->getData(self::BRAND_NAME);
    }

    /**
     * @param string $brandName
     * @return BrandInterface
     */
    public function setBrandName(string $brandName): BrandInterface
    {
        return $this->setData(self::BRAND_NAME, $brandName);
    }

    /**
     * @return string
     */
    public function getCreateDate(): string
    {
        return $this->getData(self::CREATE_DATE);
    }

    /**
     * @param string $createDate
     * @return BrandInterface
     */
    public function setCreateDate(string $createDate): BrandInterface
    {
        return $this->setData(self::CREATE_DATE, $createDate);
    }

    /**
     * @return string
     */
    public function getLastUpdate(): string
    {
        return $this->getData(self::LAST_UPDATE);
    }

    /**
     * @param string $lastUpdate
     * @return BrandInterface
     */
    public function setLastUpdate(string $lastUpdate): BrandInterface
    {
        return $this->setData(self::LAST_UPDATE, $lastUpdate);
    }
}
