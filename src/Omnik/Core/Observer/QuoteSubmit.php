<?php

namespace Omnik\Core\Observer;

use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class QuoteSubmit implements ObserverInterface
{
    /**
     * @param Logger $logger
     */
    public function __construct(
        private readonly Logger $logger
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
        } catch (\Exception $e) {
            $this->logger->error('QuoteSubmit observer failed: ' . $e->getMessage());
        }
    }
}
