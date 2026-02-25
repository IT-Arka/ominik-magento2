<?php

namespace Omnik\Core\Plugin\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Model\ProductFactory;

class ConfigurablePlugin
{
    /**
     * @param SerializerInterface $serializer
     * @param ProductFactory $productFactory
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ProductFactory $productFactory
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

        foreach ($subject->getAllowProducts() as $simpleProduct) {
            $config['skus'][$simpleProduct->getId()] = $this->splitSkuToOriginal($simpleProduct, $simpleProduct->getStoreId());
            $config['description'][$simpleProduct->getId()] = $simpleProduct->getShortDescription() ?? $subject->getProduct()->getShortDescription();
            $config['seller'][$simpleProduct->getId()] = $this->getAttributeValue($simpleProduct, 'variant_seller');
            $config['color'][$simpleProduct->getId()] = $this->getAttributeValue($simpleProduct, 'variant_color');
            $config['embalagem'][$simpleProduct->getId()] = $this->getAttributeValue($simpleProduct, 'variant_embalagem');
            $config['tamanho'][$simpleProduct->getId()] = $this->getAttributeValue($simpleProduct, 'variant_tamanho');
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
        $tenant = "";
        $product = $this->productFactory->create()->load($product->getId());
        if($product->getCustomAttribute('tenant') != null)
            $tenant = $product->getCustomAttribute('tenant')->getValue();
        $tenant = str_replace("OMPX", "", $tenant);
        $sku = $product->getSku();

        return str_replace("-" . $tenant, "", str_replace($storeId . "_", "", $sku));
    }
}
