<?php

namespace Omnik\Core\Observer;

use Omnik\Core\Model\ShippingAmount;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class QuoteSubmit implements ObserverInterface
{
    /**
     * Colunas que persistem o prazo de entrega estimado da Omnik no pedido.
     */
    private const ESTIMATED_DELIVERY_BUSINESS_DAYS = 'estimated_delivery_business_days';
    private const ESTIMATED_DELIVERY_TIME_TYPE = 'estimated_delivery_time_type';

    /**
     * @param Logger $logger
     * @param ShippingAmount $shippingAmount
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly ShippingAmount $shippingAmount
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getData('order');
            $quote = $observer->getData('quote');

            if ($quote->getData(SplitOrderInterface::SPLIT_ORDER_TYPE) === SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD) {
                $order->setCanSendNewEmailFlag(false);
            }

            $this->setEstimatedDelivery($order, $quote);
        } catch (\Exception $e) {
            $this->logger->error('QuoteSubmit observer failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve e grava o prazo de entrega estimado da rate Omnik no pedido.
     * Falhas aqui não devem interromper a finalização do pedido.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    private function setEstimatedDelivery($order, $quote): void
    {
        try {
            $estimatedDelivery = $this->shippingAmount->resolveEstimatedDelivery($order, $quote);
            if ($estimatedDelivery['business_days'] !== null) {
                $order->setData(self::ESTIMATED_DELIVERY_BUSINESS_DAYS, $estimatedDelivery['business_days']);
                $order->setData(self::ESTIMATED_DELIVERY_TIME_TYPE, $estimatedDelivery['time_type']);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'QuoteSubmit failed to resolve estimated delivery: ' . $e->getMessage()
            );
        }
    }
}
