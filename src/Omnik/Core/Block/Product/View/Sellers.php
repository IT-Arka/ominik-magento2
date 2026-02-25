<?php

namespace Omnik\Core\Block\Product\View;

use Omnik\Core\Helper\Data as ProductHelper;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Url\EncoderInterface as UrlEncoder;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Sellers extends View
{
    /**
     * @param Context $context
     * @param UrlEncoder $urlEncoder
     * @param EncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelperCore
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param Registry $registry
     * @param ProductHelper $productHelper
     * @param array $data
     */
    public function __construct(
        Context                        $context,
        UrlEncoder                     $urlEncoder,
        EncoderInterface               $jsonEncoder,
        StringUtils                    $string,
        Product                        $productHelperCore,
        ConfigInterface                $productTypeConfig,
        FormatInterface                $localeFormat,
        Session                        $customerSession,
        ProductRepositoryInterface     $productRepository,
        PriceCurrencyInterface         $priceCurrency,
        private readonly Registry      $registry,
        private readonly ProductHelper $productHelper,
        array                          $data = []
    ) {
        parent::__construct($context, $urlEncoder, $jsonEncoder, $string, $productHelperCore, $productTypeConfig,
            $localeFormat, $customerSession, $productRepository, $priceCurrency, $data);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBuybox()
    {
        $this->getRequest()->getParams();
        $currentProduct = $this->registry->registry('current_product');
        return $this->productHelper->getBuybox($currentProduct);
    }
}
