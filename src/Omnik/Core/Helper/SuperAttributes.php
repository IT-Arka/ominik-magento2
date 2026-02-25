<?php

namespace Omnik\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class SuperAttributes extends AbstractHelper
{

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {

    }

    /**
     * @param $productId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSuperAttributeData($productId)
    {
        $product = $this->productRepository->getById($productId);
        if ($product->getTypeId() != Configurable::TYPE_CODE) {
            return [];
        }

        $productTypeInstance = $product->getTypeInstance();
        $productTypeInstance->setStoreFilter($product->getStoreId(), $product);

        $attributes = $productTypeInstance->getConfigurableAttributes($product);
        $superAttributeList = [];
        foreach ($attributes as $_attribute) {
            $attributeCode = $_attribute->getProductAttribute()->getAttributeCode();
            $superAttributeList[$_attribute->getAttributeId()] = $attributeCode;
        }
        return $superAttributeList;
    }
}
