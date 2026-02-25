<?php
declare(strict_types=1);

namespace Omnik\Core\Api\Data;

/**
 * Interface StatusMappingInterface
 * Status mapping interface
 */
interface StatusMappingInterface
{
    /**
     * Constants for keys of data array
     */
    const ENTITY_ID = 'entity_id';
    const OMNIK_STATUS = 'omnik_status';
    const ADOBE_STATUS = 'adobe_status';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId();

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Get Omnik status
     *
     * @return string|null
     */
    public function getOmnikStatus();

    /**
     * Set Omnik status
     *
     * @param string $omnikStatus
     * @return $this
     */
    public function setOmnikStatus($omnikStatus);

    /**
     * Get Adobe status
     *
     * @return string|null
     */
    public function getAdobeStatus();

    /**
     * Set Adobe status
     *
     * @param string $adobeStatus
     * @return $this
     */
    public function setAdobeStatus($adobeStatus);

    /**
     * Get is active
     *
     * @return bool
     */
    public function getIsActive();

    /**
     * Set is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}
