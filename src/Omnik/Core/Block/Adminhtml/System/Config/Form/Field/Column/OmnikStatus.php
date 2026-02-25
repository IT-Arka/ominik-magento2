<?php
declare(strict_types=1);

namespace Omnik\Core\Block\Adminhtml\System\Config\Form\Field\Column;

use Magento\Framework\View\Element\Html\Select;

/**
 * Class OmnikStatus
 * HTML select element for Omnik status options
 */
class OmnikStatus extends Select
{
    /**
     * Omnik status options
     *
     * @var array
     */
    private $omnikStatusOptions = [
        'NEW' => 'NEW',
        'APPROVED' => 'APPROVED',
        'PARTIALLYRETURNED' => 'PARTIALLY RETURNED',
        'PARTIALLYCANCELED' => 'PARTIALLY CANCELED',
        'INVOICED' => 'INVOICED',
        'SENT' => 'SENT',
        'DELIVERED' => 'DELIVERED',
        'CANCELED' => 'CANCELED',
        'SHIPPING_LABEL' => 'SHIPPING_LABEL',
        'ERROR_ORDER' => 'ERROR_ORDER',
        'RECALCULATE_LATE_ORDER_SHIPPING_LABEL' => 'RECALCULATE_LATE_ORDER_SHIPPING_LABEL',
        'RECEIVING_PERIOD' => 'RECEIVING_PERIOD',
        'REVERSE_REQUEST' => 'REVERSE_REQUEST',
        'REVERSE_IN_PROGRESS' => 'REVERSE_IN_PROGRESS',
        'REVERSE_RECEIVE' => 'REVERSE_RECEIVE',
        'REVERSE_CANCELED' => 'REVERSE_CANCELED',
        'REVERSE_CONCLUDED' => 'REVERSE_CONCLUDED'
    ];

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getOmnikStatusOptions());
        }
        return parent::_toHtml();
    }

    /**
     * Get Omnik status options
     *
     * @return array
     */
    private function getOmnikStatusOptions(): array
    {
        $options = [];
        
        foreach ($this->omnikStatusOptions as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }
        
        return $options;
    }
}
