<?php
declare(strict_types=1);

namespace Omnik\Core\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Omnik\Core\Model\StatusMappingFactory;
use Omnik\Core\Model\ResourceModel\StatusMapping as StatusMappingResource;
use Omnik\Core\Model\ResourceModel\StatusMapping\CollectionFactory as StatusMappingCollectionFactory;

/**
 * Class StatusMapping
 * Backend model for status mapping configuration
 */
class StatusMapping extends Value
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var StatusMappingFactory
     */
    private $statusMappingFactory;

    /**
     * @var StatusMappingResource
     */
    private $statusMappingResource;

    /**
     * @var StatusMappingCollectionFactory
     */
    private $statusMappingCollectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SerializerInterface $serializer
     * @param StatusMappingFactory $statusMappingFactory
     * @param StatusMappingResource $statusMappingResource
     * @param StatusMappingCollectionFactory $statusMappingCollectionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serializer,
        StatusMappingFactory $statusMappingFactory,
        StatusMappingResource $statusMappingResource,
        StatusMappingCollectionFactory $statusMappingCollectionFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->statusMappingFactory = $statusMappingFactory;
        $this->statusMappingResource = $statusMappingResource;
        $this->statusMappingCollectionFactory = $statusMappingCollectionFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad()
    {
        // Load data from database instead of config value
        $mappings = $this->loadMappingsFromDatabase();
        $this->setValue($mappings);
    }

    /**
     * Load mappings from database
     *
     * @return array
     */
    private function loadMappingsFromDatabase()
    {
        $collection = $this->statusMappingCollectionFactory->create();
        $collection->getActiveMappings()->orderByCreatedAt('ASC');

        $mappings = [];
        foreach ($collection as $mapping) {
            $mappings[] = [
                'omnik_status' => $mapping->getOmnikStatus(),
                'adobe_status' => $mapping->getAdobeStatus()
            ];
        }

        return $mappings;
    }

    /**
     * Prepare data before save
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            // Store the array data temporarily for database save
            $this->setData('_temp_mapping_data', $value);
            // Store serialized data in config
            $this->setValue($this->serializer->serialize($value));
        }
        return parent::beforeSave();
    }

    /**
     * Process data after save
     *
     * @return $this
     */
    public function afterSave()
    {
        $this->saveStatusMappingToDatabase();
        
        // Reload data from database to show in form
        $mappings = $this->loadMappingsFromDatabase();
        $this->setValue($mappings);
        
        return parent::afterSave();
    }

    /**
     * Save status mapping to database
     *
     * @return void
     */
    private function saveStatusMappingToDatabase()
    {
        // Get data from temporary storage or current value
        $tempData = $this->getData('_temp_mapping_data');
        $value = $tempData ?: $this->getValue();
        
        if (is_string($value)) {
            try {
                $mappings = $this->serializer->unserialize($value);
            } catch (\Exception $e) {
                $this->_logger->error('Error unserializing status mapping data: ' . $e->getMessage());
                return;
            }
        } else {
            $mappings = $value;
        }

        if (!is_array($mappings)) {
            return;
        }

        try {
            // Clear existing mappings
            $collection = $this->statusMappingCollectionFactory->create();
            foreach ($collection as $mapping) {
                $this->statusMappingResource->delete($mapping);
            }

            // Save new mappings
            foreach ($mappings as $mapping) {
                if (empty($mapping['omnik_status']) || empty($mapping['adobe_status'])) {
                    continue;
                }

                $statusMapping = $this->statusMappingFactory->create();
                $statusMapping->setData([
                    'omnik_status' => $mapping['omnik_status'],
                    'adobe_status' => $mapping['adobe_status'],
                    'is_active' => 1,
                    'created_at' => new \DateTime(),
                    'updated_at' => new \DateTime()
                ]);

                $this->statusMappingResource->save($statusMapping);
            }
        } catch (\Exception $e) {
            $this->_logger->error(
                'Error saving status mapping to database: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
