<?php
declare(strict_types=1);

namespace Omnik\Core\Api\Data;

/**
 * Interface VariantAttributeMapInterface
 * De-para entre variante da Omnik e atributo de produto do Magento.
 */
interface VariantAttributeMapInterface
{
    const ENTITY_ID = 'entity_id';
    const OMNIK_VARIANT = 'omnik_variant';
    const ATTRIBUTE_CODE = 'attribute_code';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getEntityId();

    /**
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * @return string|null
     */
    public function getOmnikVariant();

    /**
     * @param string $omnikVariant
     * @return $this
     */
    public function setOmnikVariant($omnikVariant);

    /**
     * @return string|null
     */
    public function getAttributeCode();

    /**
     * @param string $attributeCode
     * @return $this
     */
    public function setAttributeCode($attributeCode);

    /**
     * @return bool
     */
    public function getIsActive();

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive);
}
