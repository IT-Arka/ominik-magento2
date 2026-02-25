<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ProductData;

use Omnik\Core\Model\CalculatePrice;

class PriceProducts
{
    public const TYPE_PRICE = 'price';

    public const TYPE_SPECIAL_PRICE = 'special_price';

    /**
     * @var CalculatePrice
     */
    protected CalculatePrice $calculatePrice;

    /**
     * @param CalculatePrice $calculatePrice
     */
    public function __construct(
        CalculatePrice $calculatePrice
    ) {
        $this->calculatePrice = $calculatePrice;
    }

    /**
     * @param string $typePrice
     * @param \Magento\Catalog\Model\Product $children
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getResultPrices(string $typePrice, \Magento\Catalog\Model\Product $children): float
    {
        $price = "";
        if ($typePrice == self::TYPE_PRICE) {
            $price = (float)$children->getPrice();
        } elseif ($typePrice == self::TYPE_SPECIAL_PRICE) {
            $price = (float)$children->getSpecialPrice();
        }

        $result = $this->calculatePrice->applyFactor($price, $children);
        return $result;
    }
}
