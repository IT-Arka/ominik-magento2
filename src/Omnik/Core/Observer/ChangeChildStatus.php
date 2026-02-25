<?php

namespace Omnik\Core\Observer;

use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Model\HandleChildOrders;
use Omnik\Core\Model\Cron\SaveHandler\Orders\Invoice;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ChangeChildStatus implements ObserverInterface
{
    /**
     * @param HandleChildOrders $handleChildOrders
     */
    public function __construct(
        private readonly HandleChildOrders $handleChildOrders
    ) {

    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');

        if ($order->getOrigData('status') == $order->getData('status')) {
            return $this;
        }

        // if ($order->getStatus() === Invoice::STATUS_ORDER_PENDING ||
        //     $order->getStatus() === Invoice::STATUS_ORDER_INVOICED) {
        //     return $this;
        // }

        try {
            $orderType = (string)$order->getData(SplitOrderInterface::SPLIT_ORDER_TYPE);
            if ($orderType !== SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT) {
                return $this;
            }

            $parentOrderId = $order->getId();
            $this->handleChildOrders->execute($parentOrderId, $order->getStatus(), true);
        } catch (\Exception $e) {
        }

        return $this;

    }
}
