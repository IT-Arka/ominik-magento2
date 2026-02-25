<?php

namespace Omnik\Core\Model;

use Omnik\Core\Model\Carrier\Method;
use Omnik\Core\Api\ShippingAmountInterface;
use Omnik\Core\Helper\SplitOrder\Data as SplitHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;

class ShippingAmount implements ShippingAmountInterface
{
    /**
     * @param SplitHelper $splitHelper
     * @param Method $omnikMethod
     */
    public function __construct(
        private readonly SplitHelper $splitHelper,
        private readonly Method      $omnikMethod
    ) {

    }

    /**
     * @param Order $parentOrder
     * @param Order $currentOrder
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOminikFreightRateBody(Order $parentOrder, Order $currentOrder): array
    {
        $item = current($currentOrder->getItems());
        $tenant = $this->splitHelper->getTenantByProductSku($item['sku']);

        if (!$this->splitHelper->isOmnikShipping($parentOrder->getShippingMethod()) ||
            $this->splitHelper->isContigency($parentOrder->getShippingMethod(), $tenant)) {

            return [
                'deliveryMethodId' => self::CONTINGENCY_METHOD_ID,
                'description' => Method::CONTINGENCY_METHOD,
                'deliveryEstimateBusinessDays' => (int)$this->omnikMethod->getConfigData('delivery_days'),
                'quotationId' => Method::CONTINGENCY_METHOD
            ];
        }

        return $this->splitHelper->getOmnikRateSelected($parentOrder->getShippingMethod(), $tenant, $parentOrder->getQuoteId());
    }

    /**
     * @param array $quotes
     * @param Quote $quote
     * @param Item $item
     * @param float $total
     * @return float
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function calculateShippingAmount(array $quotes, Quote $quote, Item $item, float $total = 0.0): float
    {
        $tenant = $this->splitHelper->getTenantByProductSku($item['sku']);

        if (!$this->splitHelper->isOmnikShipping($quote->getShippingAddress()->getShippingMethod()) ||
            $this->splitHelper->isContigency($quote->getShippingAddress()->getShippingMethod(), $tenant)) {
            return $this->getAmountWhenOmnikError($quote, $quotes);
        }

        $parentOrder = $this->splitHelper->getOrderParent($quote);
        $rateSelected = $this->splitHelper->getOmnikRateSelected($quote->getShippingAddress()->getShippingMethod(), $tenant, $parentOrder->getQuoteId());

        if (isset($rateSelected['finalShippingCost'])) {
            return (float)$rateSelected['finalShippingCost'];
        }

        return (float)$total;
    }

    /**
     * @param Quote $quote
     * @param array $quotes
     * @return float
     */
    public function getAmountWhenOmnikError(Quote $quote, array $quotes): float
    {
        $shippingTotals = $quote->getShippingAddress()->getShippingAmount();
        return (float)($shippingTotals / count($quotes));
    }
}
