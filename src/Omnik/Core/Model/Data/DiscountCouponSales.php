<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Data;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class DiscountCouponSales
{

    /**
     * @var OrderItemRepositoryInterface
     */
    private OrderItemRepositoryInterface $orderItemRepositoryInterface;

    /**
     * @var OrderItemInterface
     */
    private OrderItemInterface $orderItemInterface;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepositoryInterface;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @param OrderItemRepositoryInterface $orderItemRepositoryInterface
     * @param OrderItemInterface $orderItemInterface
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepositoryInterface,
        OrderItemInterface           $orderItemInterface,
        OrderRepositoryInterface     $orderRepositoryInterface,
        CheckoutSession              $checkoutSession
    )
    {
        $this->orderItemRepositoryInterface = $orderItemRepositoryInterface;
        $this->orderItemInterface = $orderItemInterface;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param int $orderId
     * @return array
     */
    public function getItemByOrderId(int $orderId): array
    {
        $order = $this->orderRepositoryInterface->get($orderId);
        return $order->getItems();
    }

    /**
     * @param int $orderId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function applyDiscountItems(int $orderId): void
    {
        $items = $this->getItemByOrderId($orderId);
        $total = 0;

        $this->applyDiscountSales($orderId, $total, $items);

        foreach ($items as $orderItem) {

            if (!empty($orderItem->getDiscountAmount())) {
                $result = $this->getDiscount($orderItem);
                $itemId = $orderItem->getItemId();

                $this->orderItemInterface->setItemId($itemId);
                $this->orderItemInterface->setPrice($result['priceUnit']);
                $this->orderItemInterface->setBasePrice($result['priceUnit']);
                $this->orderItemInterface->setPriceInclTax($result['priceUnit']);
                $this->orderItemInterface->setBasePriceInclTax($result['priceUnit']);
                $this->orderItemInterface->setRowTotalInclTax($result['rowTotal']);
                $this->orderItemInterface->setBaseRowTotalInclTax($result['rowTotal']);
                $this->orderItemInterface->setWeight(1);
                $this->orderItemInterface->setProductOptions($orderItem->getProductOptions());

                $this->orderItemRepositoryInterface->save($this->orderItemInterface);
            }
        }
    }

    /**
     * @param OrderItemInterface $orderItem
     * @return array
     */
    private function getDiscount(OrderItemInterface $orderItem): array
    {
        $discountUnit = $orderItem->getDiscountAmount() / $orderItem->getQtyOrdered();
        $priceUnit = $orderItem->getPrice() - $discountUnit;
        $priceUnit = round($priceUnit, 2);
        $rowTotal = $priceUnit * $orderItem->getQtyOrdered();
        $total = $priceUnit * $orderItem->getQtyOrdered();

        return [
            'discountUnit' => $discountUnit,
            'priceUnit' => $priceUnit,
            'rowTotal' => $rowTotal,
            'total' => $total
        ];
    }

    /**
     * @param int $orderId
     * @param float $total
     * @param array $items
     * @return void
     */
    public function applyDiscountSales(int $orderId, float $total, array $items): void
    {

        $discountSales = false;

        foreach ($items as $orderItem) {
            if (!empty($orderItem->getDiscountAmount())) {
                $discountSales = true;
                $result = $this->getDiscount($orderItem);
                $total += $result['total'];
            } else {
                $total += $orderItem->getPrice() * $orderItem->getQtyOrdered();
            }
        }

        if ($discountSales) {
            $order = $this->orderRepositoryInterface->get($orderId);

            $order->setGrandTotal($total);
            $order->setBaseGrandTotal($total);
            $order->getPayment()->setAmountOrdered($total);
            $order->getPayment()->setBaseAmountOrdered($total);

            $this->orderRepositoryInterface->save($order);
        }
    }
}
