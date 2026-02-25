<?php
declare(strict_types=1);

namespace Omnik\Core\Block\Adminhtml\System\Config\Form\Field\Column;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Magento\Sales\Model\Order\Config;

/**
 * Class AdobeOrderStatus
 * HTML select element for Adobe Commerce order status options
 */
class AdobeOrderStatus extends Select
{
    /**
     * @var Config
     */
    protected $orderConfig;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Config $orderConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $orderConfig,
        array $data = []
    ) {
        $this->orderConfig = $orderConfig;
        parent::__construct($context, $data);
    }

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
            $this->setOptions($this->getAdobeOrderStatusOptions());
        }
        return parent::_toHtml();
    }

    /**
     * Get Adobe Commerce order status options
     *
     * @return array
     */
    private function getAdobeOrderStatusOptions(): array
    {
        $options = [];
        
        $statusOptions = $this->orderConfig->getStatuses();
        
        foreach ($statusOptions as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }
        
        return $options;
    }
}
