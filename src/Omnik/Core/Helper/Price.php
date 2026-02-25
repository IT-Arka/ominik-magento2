<?php

declare(strict_types=1);

namespace Omnik\Core\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingData;
use Magento\Framework\Url\Helper\Data;
use Omnik\Core\Model\CalculatePrice;
use Magento\Quote\Model\Quote\Item;
use Omnik\Core\Model\Cart\DiscountItems;

class Price extends Data
{
    public const VENDIDO_E_ENTREGUE_POR = "Vendido e entregue por";

    /**
     * @var PricingData
     */
    private PricingData $pricingData;

    /**
     * @var CalculatePrice
     */
    private CalculatePrice $calculatePrice;

    /**
     * @var DiscountItems
     */
    private DiscountItems $discountItems;

    /**
     * @param PricingData $pricingData
     * @param CalculatePrice $calculatePrice
     * @param DiscountItems $discountItems
     */
    public function __construct(
        PricingData $pricingData,
        CalculatePrice $calculatePrice,
        DiscountItems $discountItems
    ) {
        $this->pricingData = $pricingData;
        $this->calculatePrice = $calculatePrice;
        $this->discountItems = $discountItems;
    }

    /**
     * @param Item $item
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSpecialPriceRules(Item $item): array
    {
        $result = [];

        $originalPrice = $this->calculatePrice->applyFactor($item->getOriginalPrice(), $item->getProduct());
        $originalPrice = (float) number_format($originalPrice, 2);

        $price = $this->discountItems->getDiscountPrice($item);

        if ($price < $originalPrice) {
            $result['hasSpecialPrice'] = true;
            $result['special_price'] = $this->pricingData->currency($originalPrice, true, false);
            $result['special_price_without_format'] = $originalPrice;
        } else {
            $result['hasSpecialPrice'] = false;
        }

        $result['price'] = $this->pricingData->currency($price, true, false);
        $result['price_without_format'] = $price;

        return $result;
    }

    /**
     * @param Item $item
     * @return string
     */
    public function getSubTotalDiscount(Item $item): string
    {
        return $this->discountItems->getSubTotalDiscount($item);
    }
}
