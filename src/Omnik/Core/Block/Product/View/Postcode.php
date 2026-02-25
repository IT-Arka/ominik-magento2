<?php

namespace Omnik\Core\Block\Product\View;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Omnik\Core\Helper\ProductPostcode as PostcodeHelper;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Magento\Customer\Model\Customer;

class Postcode extends \Magento\Catalog\Block\Product\View
{
    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param PostcodeHelper $postcodeHelper
     * @param CustomerSessionFactory $sessionFactory
     * @param Customer $customer
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        private readonly PostcodeHelper         $postcodeHelper,
        private readonly CustomerSessionFactory $sessionFactory,
        private readonly Customer               $customer,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
    }

    /**
     * @return mixed
     */
    public function getPostcode()
    {
        $customerSession = $this->sessionFactory->create();
        if ($customerSession->getCustomer()->getId()) {
            $customer = $this->customer->load($customerSession->getCustomer()->getId());
            $defaultShipping = $customer->getDefaultShippingAddress();
            if (!empty($defaultShipping) && $defaultShipping->getId()) {
                return $defaultShipping->getPostcode();
            }
        }

        return $this->postcodeHelper->getProductPostcode();
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->context->getRequest()->getParam('id');
    }
}
