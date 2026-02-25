<?php
declare(strict_types=1);

namespace Omnik\Core\Model;

use Magento\Framework\Model\AbstractModel;
use Omnik\Core\Api\Data\StatusMappingInterface;

/**
 * Class StatusMapping
 * Status mapping model
 */
class StatusMapping extends AbstractModel implements StatusMappingInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'omnik_core_status_mapping';

    /**
     * Cache tag
     *
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'omnik_core_status_mapping';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'status_mapping';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Omnik\Core\Model\ResourceModel\StatusMapping::class);
    }

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get Omnik status
     *
     * @return string|null
     */
    public function getOmnikStatus()
    {
        return $this->getData(self::OMNIK_STATUS);
    }

    /**
     * Set Omnik status
     *
     * @param string $omnikStatus
     * @return $this
     */
    public function setOmnikStatus($omnikStatus)
    {
        return $this->setData(self::OMNIK_STATUS, $omnikStatus);
    }

    /**
     * Get Adobe status
     *
     * @return string|null
     */
    public function getAdobeStatus()
    {
        return $this->getData(self::ADOBE_STATUS);
    }

    /**
     * Set Adobe status
     *
     * @param string $adobeStatus
     * @return $this
     */
    public function setAdobeStatus($adobeStatus)
    {
        return $this->setData(self::ADOBE_STATUS, $adobeStatus);
    }

    /**
     * Get is active
     *
     * @return bool
     */
    public function getIsActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Set is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
