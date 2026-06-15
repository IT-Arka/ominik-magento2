<?php
declare(strict_types=1);

namespace Omnik\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Omnik\Core\Block\Adminhtml\System\Config\Form\Field\Column\ProductAttributeSelect;
use Omnik\Core\Model\ResourceModel\VariantAttributeMap\CollectionFactory as VariantMapCollectionFactory;

/**
 * Renderer da tabela de de-para Variante Omnik -> Atributo Magento.
 */
class VariantAttributeMap extends AbstractFieldArray
{
    /**
     * @var ProductAttributeSelect
     */
    private $attributeRenderer;

    /**
     * @var VariantMapCollectionFactory
     */
    private $collectionFactory;

    /**
     * @param Context $context
     * @param VariantMapCollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        VariantMapCollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('omnik_variant', [
            'label' => __('Variante Omnik'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('attribute_code', [
            'label' => __('Atributo Magento'),
            'class' => 'required-entry',
            'renderer' => $this->getAttributeRenderer()
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Adicionar mapeamento de variante');
    }

    /**
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $attributeCode = $row->getAttributeCode();
        if ($attributeCode !== null) {
            $key = 'option_' . $this->getAttributeRenderer()->calcOptionHash($attributeCode);
            $options[$key] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return ProductAttributeSelect
     * @throws LocalizedException
     */
    private function getAttributeRenderer()
    {
        if (!$this->attributeRenderer) {
            $this->attributeRenderer = $this->getLayout()->createBlock(
                ProductAttributeSelect::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributeRenderer;
    }

    /**
     * @return array
     */
    public function getArrayRows()
    {
        $result = parent::getArrayRows();

        if (empty($result)) {
            $result = $this->loadFromDatabase();
        }

        return $result;
    }

    /**
     * @return array
     */
    private function loadFromDatabase()
    {
        $collection = $this->collectionFactory->create();
        $collection->getActiveMappings()->setOrder('entity_id', 'ASC');

        $result = [];
        $index = 0;
        foreach ($collection as $mapping) {
            $result[$index] = new DataObject([
                'omnik_variant' => $mapping->getOmnikVariant(),
                'attribute_code' => $mapping->getAttributeCode(),
                '_id' => $index
            ]);
            $index++;
        }

        return $result;
    }
}
