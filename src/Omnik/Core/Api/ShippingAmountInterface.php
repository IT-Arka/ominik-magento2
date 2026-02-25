<?php

namespace Omnik\Core\Api;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;

interface ShippingAmountInterface
{
    public const CONTINGENCY_METHOD_ID = 'id_omnik_123';

    /**
     * @param Order $parentOrder
     * @param Order $currentOrder
     * @return array
     */
    public function getOminikFreightRateBody(Order $parentOrder, Order $currentOrder): array;

    /**
     * @param array $quotes
     * @param Quote $quote
     * @param Item $item
     * @param float $total
     * @return float
     */
    public function calculateShippingAmount(array $quotes, Quote $quote, Item $item, float $total = 0.0): float;

    /**
     * @param Quote $quote
     * @param array $quotes
     * @return float
     */
    public function getAmountWhenOmnikError(Quote $quote, array $quotes): float;

}
