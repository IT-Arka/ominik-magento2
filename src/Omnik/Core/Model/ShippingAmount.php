<?php

namespace Omnik\Core\Model;

use Omnik\Core\Model\Carrier\Method;
use Omnik\Core\Model\Shipping\DeliveryEstimate;
use Omnik\Core\Api\ShippingAmountInterface;
use Omnik\Core\Api\SplitOrderInterface;
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

    /**
     * Resolve o prazo de entrega estimado da rate Omnik selecionada para o
     * quote informado, retornando os dias e o tipo de prazo.
     *
     * O quote pai carrega o shipping_method completo; cada quote/pedido
     * (único ou filho) é associado a um tenant. Para um pedido filho, a
     * rate é resolvida a partir do shipping_method e do quote do pai.
     *
     * Retorna ['business_days' => int|null, 'time_type' => string|null].
     * Os dois campos vêm nulos quando não há rate Omnik aplicável (ex.: o
     * seller da cotação não é dono do SKU do pedido).
     *
     * @param Order $order
     * @param Quote $quote
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function resolveEstimatedDelivery(Order $order, Quote $quote): array
    {
        $empty = ['business_days' => null, 'time_type' => null];

        $items = $order->getItems();
        if (empty($items)) {
            return $empty;
        }

        $item = current($items);
        $tenant = $this->splitHelper->getTenantByProductSku($item->getSku());

        // Para pedido filho (split), o shipping_method completo e o quote
        // ficam no pedido pai; para pedido único, vêm do próprio quote.
        $parentOrderId = $quote->getData(SplitOrderInterface::SPLIT_ORDER_PARENT_ID);
        if (!empty($parentOrderId)) {
            $parentOrder = $this->splitHelper->getOrderParent($quote);
            $shippingMethod = $parentOrder->getShippingMethod();
            $quoteId = (int)$parentOrder->getQuoteId();
        } else {
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
            $quoteId = (int)$quote->getId();
        }

        if (empty($shippingMethod)
            || !$this->splitHelper->isOmnikShipping($shippingMethod)
            || $this->splitHelper->isContigency($shippingMethod, $tenant)) {
            return $empty;
        }

        $rateSelected = $this->splitHelper->getOmnikRateSelected($shippingMethod, $tenant, $quoteId);

        return $this->extractEstimatedDelivery($rateSelected);
    }

    /**
     * Extrai o prazo de entrega da rate selecionada.
     *
     * O prazo vem de `deliveryEstimateBusinessDays` e o tipo
     * (`bd` = dias úteis, `d` = dias) de `deliveryTimeType`.
     * O valor de dias permanece nulo quando o seller da cotação não é dono do SKU.
     *
     * @param array $rateSelected
     * @return array
     */
    private function extractEstimatedDelivery(array $rateSelected): array
    {
        $businessDays = DeliveryEstimate::resolveDays($rateSelected);
        $timeType = $rateSelected['deliveryTimeType'] ?? null;

        if ($businessDays === null) {
            return ['business_days' => null, 'time_type' => null];
        }

        return [
            'business_days' => $businessDays,
            'time_type' => $timeType !== null ? (string)$timeType : null,
        ];
    }
}
