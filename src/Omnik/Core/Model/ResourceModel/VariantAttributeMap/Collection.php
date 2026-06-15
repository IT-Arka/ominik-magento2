<?php
declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\VariantAttributeMap;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Omnik\Core\Model\VariantAttributeMap;
use Omnik\Core\Model\ResourceModel\VariantAttributeMap as VariantAttributeMapResource;

/**
 * Collection do de-para variante Omnik -> atributo Magento.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'omnik_variant_attribute_map_collection';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(VariantAttributeMap::class, VariantAttributeMapResource::class);
    }

    /**
     * @return $this
     */
    public function getActiveMappings()
    {
        return $this->addFieldToFilter('is_active', 1);
    }
}
