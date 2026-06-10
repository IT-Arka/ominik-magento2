<?php

namespace Omnik\Core\Plugin\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Model\ProductFactory;
use Omnik\Core\Helper\Config as ConfigHelper;

class ConfigurablePlugin
{
    /**
     * @param SerializerInterface $serializer
     * @param ProductFactory $productFactory
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ProductFactory $productFactory,
        private readonly ConfigHelper $configHelper
    ) {
    }

    /**
     * After Get JsonConfig
     *
     * @param Configurable $subject
     * @param string $result
     * @return bool|string
     */
    public function afterGetJsonConfig(Configurable $subject, string $result)
    {
        if (!$config = $this->serializer->unserialize($result)) {
            return $result;
        }

        $config['description_config'] = $subject->getProduct()->getShortDescription();
        $config['sku_config'] = $subject->getProduct()->getSku();

        $sellerAttr   = $this->configHelper->getAttrVariantSeller();
        $colorAttr    = $this->configHelper->getAttrVariantColor();
        $embalagemAttr = $this->configHelper->getAttrVariantEmbalagem();
        $tamanhoAttr  = $this->configHelper->getAttrVariantTamanho();

        foreach ($subject->getAllowProducts() as $simpleProduct) {
            $pid = $simpleProduct->getId();
            $config['skus'][$pid]        = $this->splitSkuToOriginal($simpleProduct, $simpleProduct->getStoreId());
            $config['description'][$pid] = $simpleProduct->getShortDescription() ?? $subject->getProduct()->getShortDescription();
            $config['seller'][$pid]      = $this->getAttributeValue($simpleProduct, $sellerAttr);
            $config['color'][$pid]       = $this->getAttributeValue($simpleProduct, $colorAttr);
            $config['embalagem'][$pid]   = $this->getAttributeValue($simpleProduct, $embalagemAttr);
            $config['tamanho'][$pid]     = $this->getAttributeValue($simpleProduct, $tamanhoAttr);
        }

        return $this->serializer->serialize($config);
    }

    /**
     * @param $simpleProduct
     * @param $attribute
     * @return mixed|string
     */
    public function getAttributeValue($simpleProduct, $attribute)
    {
        $attr = $simpleProduct->getResource()->getAttribute($attribute);
        $optionText = '';
        if ($attr && $attr->usesSource()) {
            $optionText = $attr->getSource()->getOptionText($simpleProduct->getData($attribute));
        }

        return $optionText;
    }

    /**
     * @param $product
     * @param $storeId
     * @return array|string|string[]
     */
    public function splitSkuToOriginal($product, $storeId)
    {
        $tenantAttr = $this->configHelper->getAttrTenant((int)$storeId);
        $product    = $this->productFactory->create()->load($product->getId());
        $tenant     = str_replace("OMPX", "", $product->getCustomAttribute($tenantAttr)?->getValue() ?? '');
        $sku        = $product->getSku();

        return str_replace("-" . $tenant, "", str_replace($storeId . "_", "", $sku));
    }
}
