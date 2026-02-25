<?php

namespace Omnik\Core\Helper;

use Magento\Checkout\Model\SessionFactory;
use Magento\Customer\Model\SessionFactory as CustomerSession;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;


class ProductPostcode extends Template implements BlockInterface
{
    /**
     * @param Template\Context $context
     * @param SessionFactory $checkoutSessionFactory
     * @param CustomerSession $customerSessionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context                 $context,
        private readonly SessionFactory  $checkoutSessionFactory,
        private readonly CustomerSession $customerSessionFactory,
        array                            $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @param $postcode
     * @return void
     */
    public function setProductPostcode($postcode): void
    {
        $session = $this->checkoutSessionFactory->create();
        $session->setPostcodePdp($postcode);
    }

    /**
     * @return mixed
     */
    public function getProductPostcode(): mixed
    {
        $customerSession = $this->customerSessionFactory->create();
        if ($customerSession->isLoggedIn()) {
            $defaultShippingAddress = $customerSession->getCustomer()->getDefaultShippingAddress();
            if ($defaultShippingAddress) {
                $postcode = $defaultShippingAddress->getPostcode();
                if ($postcode) {
                    return $postcode;
                }
            }
        }

        $session = $this->checkoutSessionFactory->create();
        return $session->getPostcodePdp();
    }
}
