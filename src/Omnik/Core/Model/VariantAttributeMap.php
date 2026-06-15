<?php
declare(strict_types=1);

namespace Omnik\Core\Model;

use Magento\Framework\Model\AbstractModel;
use Omnik\Core\Api\Data\VariantAttributeMapInterface;

/**
 * De-para variante Omnik -> atributo Magento.
 */
class VariantAttributeMap extends AbstractModel implements VariantAttributeMapInterface
{
    const CACHE_TAG = 'omnik_variant_attribute_map';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'omnik_variant_attribute_map';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Omnik\Core\Model\ResourceModel\VariantAttributeMap::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getOmnikVariant()
    {
        return $this->getData(self::OMNIK_VARIANT);
    }

    /**
     * @inheritDoc
     */
    public function setOmnikVariant($omnikVariant)
    {
        return $this->setData(self::OMNIK_VARIANT, $omnikVariant);
    }

    /**
     * @inheritDoc
     */
    public function getAttributeCode()
    {
        return $this->getData(self::ATTRIBUTE_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setAttributeCode($attributeCode)
    {
        return $this->setData(self::ATTRIBUTE_CODE, $attributeCode);
    }

    /**
     * @inheritDoc
     */
    public function getIsActive()
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritDoc
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
