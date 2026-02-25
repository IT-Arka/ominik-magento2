<?php

namespace Omnik\Core\Model\Order;

use Magento\Sales\Api\OrderRepositoryInterface;

class ChildOrderPayment
{
    /** @var OrderRepositoryInterface */
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $order
     * @return bool
     */
    public function invoice($order): bool
    {
        if (!$order->canInvoice()) {
            return false;
        }

        $payment = $order->getPayment();
        $baseTotalDue = $order->getBaseTotalDue();
        $payment->registerCaptureNotification($baseTotalDue);
        $this->orderRepository->save($order);
        return true;
    }

    /**
     * @param $order
     * @return bool
     */
    public function cancel($order): bool
    {
        if ($order->canCancel()) {
            $order->cancel();
            $this->orderRepository->save($order);
            return true;
        }

        return false;
    }
}
