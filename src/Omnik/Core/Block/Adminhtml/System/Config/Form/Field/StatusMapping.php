<?php
declare(strict_types=1);

namespace Omnik\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Omnik\Core\Block\Adminhtml\System\Config\Form\Field\Column\OmnikStatus;
use Omnik\Core\Block\Adminhtml\System\Config\Form\Field\Column\AdobeOrderStatus;
use Omnik\Core\Model\ResourceModel\StatusMapping\CollectionFactory as StatusMappingCollectionFactory;

/**
 * Class StatusMapping
 * Backend system config array field renderer for status mapping
 */
class StatusMapping extends AbstractFieldArray
{
    /**
     * @var OmnikStatus
     */
    private $omnikStatusRenderer;

    /**
     * @var AdobeOrderStatus
     */
    private $adobeOrderStatusRenderer;

    /**
     * @var StatusMappingCollectionFactory
     */
    private $statusMappingCollectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param StatusMappingCollectionFactory $statusMappingCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        StatusMappingCollectionFactory $statusMappingCollectionFactory,
        array $data = []
    ) {
        $this->statusMappingCollectionFactory = $statusMappingCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('omnik_status', [
            'label' => __('Omnik'),
            'class' => 'required-entry',
            'renderer' => $this->getOmnikStatusRenderer()
        ]);

        $this->addColumn('adobe_status', [
            'label' => __('Adobe'),
            'class' => 'required-entry',
            'renderer' => $this->getAdobeOrderStatusRenderer()
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Status Mapping');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $omnikStatus = $row->getOmnikStatus();
        if ($omnikStatus !== null) {
            $options['option_' . $this->getOmnikStatusRenderer()->calcOptionHash($omnikStatus)] = 'selected="selected"';
        }

        $adobeStatus = $row->getAdobeStatus();
        if ($adobeStatus !== null) {
            $options['option_' . $this->getAdobeOrderStatusRenderer()->calcOptionHash($adobeStatus)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Get Omnik status renderer
     *
     * @return OmnikStatus
     * @throws LocalizedException
     */
    private function getOmnikStatusRenderer()
    {
        if (!$this->omnikStatusRenderer) {
            $this->omnikStatusRenderer = $this->getLayout()->createBlock(
                OmnikStatus::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->omnikStatusRenderer;
    }

    /**
     * Get Adobe order status renderer
     *
     * @return AdobeOrderStatus
     * @throws LocalizedException
     */
    private function getAdobeOrderStatusRenderer()
    {
        if (!$this->adobeOrderStatusRenderer) {
            $this->adobeOrderStatusRenderer = $this->getLayout()->createBlock(
                AdobeOrderStatus::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->adobeOrderStatusRenderer;
    }

    /**
     * Get array rows
     *
     * @return array
     */
    public function getArrayRows()
    {
        $result = parent::getArrayRows();
        
        // If no data from parent (first load), load from database
        if (empty($result)) {
            $result = $this->loadMappingsFromDatabase();
        }
        
        return $result;
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

        $result = [];
        $index = 0;
        
        foreach ($collection as $mapping) {
            $result[$index] = new DataObject([
                'omnik_status' => $mapping->getOmnikStatus(),
                'adobe_status' => $mapping->getAdobeStatus(),
                '_id' => $index
            ]);
            $index++;
        }

        return $result;
    }
}
