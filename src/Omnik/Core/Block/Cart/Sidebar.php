<?php
namespace Omnik\Core\Block\Cart;

use Omnik\Core\Helper\FreeShippingProgressBar\Data;
use Magento\Framework\View\Element\Template;

class Sidebar extends Template
{
    /**
     * @var Data
     */
    private Data $helper;

    /**
     * @param Template\Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data             $helper,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    public function isFreeShippingEligible(): bool
    {
        return $this->helper->isFreeShippingEligible();
    }

    /**
     * @return float
     */
    public function getConfigForShippingBar(): float
    {
        return $this->helper->getFreeShippingMinValue();
    }

    /**
     * @return bool
     */
    public function freeShippingIsAvailable()
    {
        return $this->helper->isFreeShippingActive();
    }
}
