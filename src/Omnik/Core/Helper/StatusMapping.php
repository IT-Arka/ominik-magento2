<?php
declare(strict_types=1);

namespace Omnik\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Omnik\Core\Model\ResourceModel\StatusMapping\CollectionFactory as StatusMappingCollectionFactory;
use Omnik\Core\Model\ResourceModel\StatusMapping as StatusMappingResource;

/**
 * Class StatusMapping
 * Helper for status mapping operations
 */
class StatusMapping extends AbstractHelper
{
    /**
     * @var StatusMappingCollectionFactory
     */
    private $statusMappingCollectionFactory;

    /**
     * @var StatusMappingResource
     */
    private $statusMappingResource;

    /**
     * @var array
     */
    private $mappingCache = [];

    /**
     * Constructor
     *
     * @param Context $context
     * @param StatusMappingCollectionFactory $statusMappingCollectionFactory
     * @param StatusMappingResource $statusMappingResource
     */
    public function __construct(
        Context $context,
        StatusMappingCollectionFactory $statusMappingCollectionFactory,
        StatusMappingResource $statusMappingResource
    ) {
        $this->statusMappingCollectionFactory = $statusMappingCollectionFactory;
        $this->statusMappingResource = $statusMappingResource;
        parent::__construct($context);
    }

    /**
     * Get Adobe status by Omnik status
     *
     * @param string $omnikStatus
     * @return string|null
     */
    public function getAdobeStatusByOmnikStatus($omnikStatus)
    {
        if (isset($this->mappingCache['omnik_to_adobe'][$omnikStatus])) {
            return $this->mappingCache['omnik_to_adobe'][$omnikStatus];
        }

        $mapping = $this->statusMappingResource->getByOmnikStatus($omnikStatus);
        
        if ($mapping && isset($mapping['adobe_status'])) {
            $this->mappingCache['omnik_to_adobe'][$omnikStatus] = $mapping['adobe_status'];
            return $mapping['adobe_status'];
        }

        return null;
    }

    /**
     * Get Omnik status by Adobe status
     *
     * @param string $adobeStatus
     * @return string|null
     */
    public function getOmnikStatusByAdobeStatus($adobeStatus)
    {
        if (isset($this->mappingCache['adobe_to_omnik'][$adobeStatus])) {
            return $this->mappingCache['adobe_to_omnik'][$adobeStatus];
        }

        $mapping = $this->statusMappingResource->getByAdobeStatus($adobeStatus);
        
        if ($mapping && isset($mapping['omnik_status'])) {
            $this->mappingCache['adobe_to_omnik'][$adobeStatus] = $mapping['omnik_status'];
            return $mapping['omnik_status'];
        }

        return null;
    }

    /**
     * Get all active mappings
     *
     * @return array
     */
    public function getAllActiveMappings()
    {
        if (isset($this->mappingCache['all_mappings'])) {
            return $this->mappingCache['all_mappings'];
        }

        $collection = $this->statusMappingCollectionFactory->create();
        $collection->getActiveMappings()->orderByCreatedAt();

        $mappings = [];
        foreach ($collection as $mapping) {
            $mappings[] = [
                'entity_id' => $mapping->getEntityId(),
                'omnik_status' => $mapping->getOmnikStatus(),
                'adobe_status' => $mapping->getAdobeStatus(),
                'is_active' => $mapping->getIsActive(),
                'created_at' => $mapping->getCreatedAt(),
                'updated_at' => $mapping->getUpdatedAt()
            ];
        }

        $this->mappingCache['all_mappings'] = $mappings;
        return $mappings;
    }

    /**
     * Check if status mapping exists
     *
     * @param string $omnikStatus
     * @param string $adobeStatus
     * @return bool
     */
    public function mappingExists($omnikStatus, $adobeStatus)
    {
        return $this->statusMappingResource->mappingExists($omnikStatus, $adobeStatus);
    }

    /**
     * Get Omnik status options
     *
     * @return array
     */
    public function getOmnikStatusOptions()
    {
        return [
            'NEW' => __('NEW'),
            'APPROVED' => __('APPROVED'),
            'PARTIALLYRETURNED' => __('PARTIALLY RETURNED'),
            'PARTIALLYCANCELED' => __('PARTIALLY CANCELED'),
            'INVOICED' => __('INVOICED'),
            'SENT' => __('SENT'),
            'DELIVERED' => __('DELIVERED'),
            'CANCELED' => __('CANCELED'),
            'SHIPPING_LABEL' => __('SHIPPING_LABEL'),
            'ERROR_ORDER' => __('ERROR_ORDER'),
            'RECALCULATE_LATE_ORDER_SHIPPING_LABEL' => __('RECALCULATE_LATE_ORDER_SHIPPING_LABEL'),
            'RECEIVING_PERIOD' => __('RECEIVING_PERIOD'),
            'REVERSE_REQUEST' => __('REVERSE_REQUEST'),
            'REVERSE_IN_PROGRESS' => __('REVERSE_IN_PROGRESS'),
            'REVERSE_RECEIVE' => __('REVERSE_RECEIVE'),
            'REVERSE_CANCELED' => __('REVERSE_CANCELED'),
            'REVERSE_CONCLUDED' => __('REVERSE_CONCLUDED')
        ];
    }

    /**
     * Clear mapping cache
     *
     * @return void
     */
    public function clearCache()
    {
        $this->mappingCache = [];
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            'omnik_core/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugMode()
    {
        return $this->scopeConfig->isSetFlag(
            'omnik_core/advanced/debug_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get sync frequency
     *
     * @return int
     */
    public function getSyncFrequency()
    {
        return (int) $this->scopeConfig->getValue(
            'omnik_core/advanced/sync_frequency',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?: 60;
    }

    /**
     * Check if map is enabled
     *
     * @return bool
     */
    public function isMapEnabled()
    {
        return $this->scopeConfig->getValue(
            'omnik_adobe_status_mapping/status/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}
