<?php

declare(strict_types=1);

namespace Omnik\Core\Helper\Catalog;

use Omnik\Core\Helper\Data as QuoteHelper;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class Data extends AbstractHelper
{
    public const ATTRIBUTE_CODE_VARIANT_SELLER = 'variant_seller';

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param QuoteHelper $quoteHelper
     * @param Context $context
     */
    public function __construct(
        private readonly ProductRepositoryInterface          $productRepository,
        private readonly ProductAttributeRepositoryInterface $productAttributeRepository,
        private readonly QuoteHelper                         $quoteHelper,
        public readonly Context                              $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param int $optionId
     * @return string|void|null
     * @throws NoSuchEntityException
     */
    public function getLabelAttributeByOptionId(int $optionId)
    {
        $attributeData = $this->productAttributeRepository->get(self::ATTRIBUTE_CODE_VARIANT_SELLER);
        $optionLabel = '';

        if ($attributeData->usesSource()) {
            $optionLabel = $attributeData->getSource()->getOptionText($optionId);
        }

        return $optionLabel;
    }

    /**
     * @param $configProduct
     * @param $responseFreight
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBuybox($configProduct, $responseFreight = null)
    {
        $simpleIds = $this->getAllSimpleByConfigurable($configProduct);
        foreach ($simpleIds as $simpleId) {
            $simpleProducts[] = $this->productRepository->getById($simpleId);
        }
        return $this->getHtmlForBuybox($simpleProducts, $responseFreight);
    }

    /**
     * @param $simpleProducts
     * @param $responseFreight
     * @return string
     */
    public function getHtmlForBuybox($simpleProducts, $responseFreight = null)
    {
        $html = '';
        foreach ($simpleProducts as $simpleProduct) {
            $attrId = $this->getSellerAttributeId($simpleProduct);

            $brandText = __('Brand');
            $freightText = __('As low as');
            $brand = $this->getBrandName($simpleProduct);

            $html .= "<li class='line-freight seller-{$simpleProduct->getVariantSeller()}' data-option-id='{$simpleProduct->getVariantSeller()}' style='cursor: pointer'>";
            $html .= "<input type='hidden' name='optionId' value='option-label-variant_seller-{$attrId}-item-{$simpleProduct->getVariantSeller()}'>";
            $html .= "<p class='seller-price'> {$this->quoteHelper->getSellerName($simpleProduct)}";
            if (!empty($responseFreight)) {
                $html .= " | {$simpleProduct->getFormattedPrice()}";
            }
            $html .= "</p>";
            $html .= "<p class='shipping-price'>{$brandText}: {$brand}</p>";

            if (empty($responseFreight)) {
                $html .= "<p class='shipping-price'> {$freightText}:  {$simpleProduct->getFormattedPrice()}</p>";
            } else {
                $finalCost = $this->formatCost($responseFreight['content'][0]['deliveryOptions'][0]['finalShippingCost']);
                $html .= "<p class='shipping-price'> {$responseFreight['content'][0]['deliveryOptions'][0]['deliveryTime']} Dias Úteis | R$ {$finalCost} </p>";
            }
            $html .= "</li>";
        }

        return $html;
    }

    /**
     * @param $simpleProduct
     * @return mixed
     */
    public function getSellerAttributeId($simpleProduct)
    {
        return $simpleProduct->getResource()->getAttribute('variant_seller')->getAttributeId();
    }

    /**
     * @param $cost
     * @return array|string|string[]
     */
    public function formatCost($cost)
    {
        $cost = str_replace('.', ',', (string)$cost);
        if (strpos($cost, ',') === false) {
            return $cost . ',00';
        }
        return $cost;
    }

    /**
     * @param $configProduct
     * @return array
     */
    public function getAllSimpleByConfigurable($configProduct)
    {
        $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
        $seller = [];
        $ids = [];
        foreach ($_children as $child) {
            $sellerAttribute = $child->getCustomAttribute('variant_seller');
            if (isset($seller[$sellerAttribute->getValue()])) {
                if ((float)$child->getFinalPrice() <= $seller[$sellerAttribute->getValue()]) {
                    $seller[$sellerAttribute->getValue()] = $child->getFinalPrice();
                    $ids[$sellerAttribute->getValue()] = $child->getId();
                }
                continue;
            }
            $seller[$sellerAttribute->getValue()] = (float)$child->getFinalPrice();
            $ids[$sellerAttribute->getValue()] = $child->getId();
        }
        return array_unique($ids);
    }

    /**
     * @param $simpleProduct
     * @return string
     */
    public function getBrandName($simpleProduct)
    {
        $optionText = '';
        $attr = $simpleProduct->getResource()->getAttribute('brand');
        if ($attr->usesSource()) {
            $optionText = $attr->getSource()->getOptionText($simpleProduct->getBrand());
        }

        return $optionText;
    }
}
