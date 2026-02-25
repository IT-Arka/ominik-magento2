<?php
declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\StatusMapping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Omnik\Core\Model\StatusMapping;
use Omnik\Core\Model\ResourceModel\StatusMapping as StatusMappingResource;

/**
 * Class Collection
 * Status mapping collection
 */
class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'omnik_core_status_mapping_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'status_mapping_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(StatusMapping::class, StatusMappingResource::class);
    }

    /**
     * Get active mappings
     *
     * @return $this
     */
    public function getActiveMappings()
    {
        return $this->addFieldToFilter('is_active', 1);
    }

    /**
     * Filter by Omnik status
     *
     * @param string $omnikStatus
     * @return $this
     */
    public function filterByOmnikStatus($omnikStatus)
    {
        return $this->addFieldToFilter('omnik_status', $omnikStatus);
    }

    /**
     * Filter by Adobe status
     *
     * @param string $adobeStatus
     * @return $this
     */
    public function filterByAdobeStatus($adobeStatus)
    {
        return $this->addFieldToFilter('adobe_status', $adobeStatus);
    }

    /**
     * Order by created date
     *
     * @param string $direction
     * @return $this
     */
    public function orderByCreatedAt($direction = self::SORT_ORDER_DESC)
    {
        return $this->setOrder('created_at', $direction);
    }

    /**
     * Order by updated date
     *
     * @param string $direction
     * @return $this
     */
    public function orderByUpdatedAt($direction = self::SORT_ORDER_DESC)
    {
        return $this->setOrder('updated_at', $direction);
    }
}
