<?php
declare(strict_types=1);

namespace Omnik\Core\Block\Adminhtml\System\Config\Form\Field\Column;

use Magento\Framework\View\Element\Html\Select;
use Omnik\Core\Model\Config\Source\ProductAttributes;

/**
 * <select> de atributos de produto do Magento para o de-para de variantes.
 * Reusa o source model ProductAttributes (mesma lista do mapeamento fixo).
 */
class ProductAttributeSelect extends Select
{
    /**
     * @param \Magento\Framework\View\Element\Context $context
     * @param ProductAttributes $productAttributes
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        private readonly ProductAttributes $productAttributes,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->productAttributes->toOptionArray());
        }
        return parent::_toHtml();
    }
}
