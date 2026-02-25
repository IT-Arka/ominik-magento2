<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\Data\VariantInterface;
use Omnik\Core\Model\ResourceModel\Variant as VariantResourceModel;
use Magento\Framework\Model\AbstractModel;

class Variant extends AbstractModel implements VariantInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(VariantResourceModel::class);
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
     * @return VariantInterface
     */
    public function setTenant(string $tenant): VariantInterface
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
     * @return VariantInterface
     */
    public function setOperator(string $operator): VariantInterface
    {
        return $this->setData(self::OPERATOR, $operator);
    }

    /**
     * @return string
     */
    public function getVariant(): string
    {
        return $this->getData(self::VARIANT);
    }

    /**
     * @param string $variant
     * @return VariantInterface
     */
    public function setVariant(string $variant): VariantInterface
    {
        return $this->setData(self::VARIANT, $variant);
    }

    /**
     * @return string
     */
    public function getVariantId(): string
    {
        return $this->getData(self::VARIANT_ID);
    }

    /**
     * @param string $variantId
     * @return VariantInterface
     */
    public function setVariantId(string $variantId): VariantInterface
    {
        return $this->setData(self::VARIANT_ID, $variantId);
    }

    /**
     * @return string
     */
    public function getVariantCode(): string
    {
        return $this->getData(self::VARIANT_CODE);
    }

    /**
     * @param string $variantCode
     * @return VariantInterface
     */
    public function setVariantCode(string $variantCode): VariantInterface
    {
        return $this->setData(self::VARIANT_CODE, $variantCode);
    }

    /**
     * @return string
     */
    public function getVariantName(): string
    {
        return $this->getData(self::VARIANT_NAME);
    }

    /**
     * @param string $variantName
     * @return VariantInterface
     */
    public function setVariantName(string $variantName): VariantInterface
    {
        return $this->setData(self::VARIANT_NAME, $variantName);
    }

    /**
     * @return string
     */
    public function getVariantOption(): string
    {
        return $this->getData(self::VARIANT_OPTION);
    }

    /**
     * @param string $variantOption
     * @return VariantInterface
     */
    public function setVariantOption(string $variantOption): VariantInterface
    {
        return $this->setData(self::VARIANT_OPTION, $variantOption);
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
     * @return VariantInterface
     */
    public function setCreateDate(string $createDate): VariantInterface
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
     * @return VariantInterface
     */
    public function setLastUpdate(string $lastUpdate): VariantInterface
    {
        return $this->setData(self::LAST_UPDATE, $lastUpdate);
    }
}
