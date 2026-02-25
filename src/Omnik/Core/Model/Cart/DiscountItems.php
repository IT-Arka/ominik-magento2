<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cart;

use Magento\Quote\Model\Quote\Item;
use Magento\Framework\Pricing\Helper\Data as PricingData;

class DiscountItems
{
    /**
     * @var PricingData
     */
    private PricingData $pricingData;

    /**
     * @param PricingData $pricingData
     */
    public function __construct(
        PricingData $pricingData
    ) {
        $this->pricingData = $pricingData;
    }

    /**
     * @param Item $item
     * @return string
     */
    public function getSubTotalDiscount(Item $item): string
    {
        $subtotal = ($item->getPrice() * $item->getQty());
        if (!empty($item->getDiscountAmount())) {
            $subtotal = $this->getDiscountPrice($item) * $item->getQty();
            $subtotal = round($subtotal, 2);
        }
        return $this->pricingData->currency($subtotal, true, false);
    }

    /**
     * @param Item $item
     * @return float
     */
    public function getDiscountPrice(Item $item): float
    {
        if (!empty($item->getDiscountAmount())) {
            $discount = $item->getDiscountAmount() / $item->getQty();
            $price = $item->getPrice() - $discount;
            return round($price, 2);
        }

        return $item->getPrice();
    }
}
