<?php

declare(strict_types=1);

namespace Omnik\Core\Helper\Catalog;

use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Helper\Data as QuoteHelper;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class Data extends AbstractHelper
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param QuoteHelper $quoteHelper
     * @param ConfigHelper $configHelper
     * @param Context $context
     */
    public function __construct(
        private readonly ProductRepositoryInterface          $productRepository,
        private readonly ProductAttributeRepositoryInterface $productAttributeRepository,
        private readonly QuoteHelper                         $quoteHelper,
        private readonly ConfigHelper                        $configHelper,
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
        $attrCode      = $this->configHelper->getAttrVariantSeller();
        $attributeData = $this->productAttributeRepository->get($attrCode);
        $optionLabel   = '';

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
        $attrCode = $this->configHelper->getAttrVariantSeller();
        return $simpleProduct->getResource()->getAttribute($attrCode)->getAttributeId();
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
        $variantSellerAttr = $this->configHelper->getAttrVariantSeller();
        foreach ($_children as $child) {
            $sellerAttribute = $child->getCustomAttribute($variantSellerAttr);
            if (!$sellerAttribute) {
                continue;
            }
            $sellerVal = $sellerAttribute->getValue();
            if (isset($seller[$sellerVal])) {
                if ((float)$child->getFinalPrice() <= $seller[$sellerVal]) {
                    $seller[$sellerVal] = $child->getFinalPrice();
                    $ids[$sellerVal]    = $child->getId();
                }
                continue;
            }
            $seller[$sellerVal] = (float)$child->getFinalPrice();
            $ids[$sellerVal]    = $child->getId();
        }
        return array_unique($ids);
    }

    /**
     * @param $simpleProduct
     * @return string
     */
    public function getBrandName($simpleProduct)
    {
        $attrCode   = $this->configHelper->getAttrBrand();
        $optionText = '';
        $attr       = $simpleProduct->getResource()->getAttribute($attrCode);
        if ($attr && $attr->usesSource()) {
            $optionText = $attr->getSource()
                ->getOptionText($simpleProduct->getData($attrCode));
        }

        return $optionText;
    }
}
