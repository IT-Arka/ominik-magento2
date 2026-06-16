<?php

namespace Omnik\Core\Observer;

use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Model\HandleChildOrders;
use Omnik\Core\Model\Integration\Sales\Approvation;
use Omnik\Core\Model\Cron\SaveHandler\Orders\Invoice;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ChangeChildStatus implements ObserverInterface
{
    /**
     * @param HandleChildOrders $handleChildOrders
     * @param Approvation $integrationApprovation
     */
    public function __construct(
        private readonly HandleChildOrders $handleChildOrders,
        private readonly Approvation $integrationApprovation
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

            // Pedido split (pai): propaga o status para os pedidos filhos.
            if ($orderType === SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT) {
                $parentOrderId = $order->getId();
                $this->handleChildOrders->execute($parentOrderId, $order->getStatus(), true);
                return $this;
            }

            // Pedido filho é tratado pelo pai (acima); ignora aqui para não duplicar.
            if ($orderType === SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD) {
                return $this;
            }

            // Pedido único (não-split): envia approved/notapproved direto para a Omnik.
            // O próprio integrate decide via StatusMapping (isApproved/isNotApproved).
            $this->integrationApprovation->integrate($order);
        } catch (\Exception $e) {
        }

        return $this;

    }
}
