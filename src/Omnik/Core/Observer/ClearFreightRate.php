<?php

namespace Omnik\Core\Observer;

use Omnik\Core\Model\Repositories\OmnikFreightRatesRepository;
use Omnik\Core\Helper\SplitOrder\Data as SplitHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;

class ClearFreightRate implements ObserverInterface
{
    /**
     * @param SplitHelper $helper
     * @param OmnikFreightRatesRepository $omnikFreightRatesRepository
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        private readonly SplitHelper                 $helper,
        private readonly OmnikFreightRatesRepository $omnikFreightRatesRepository,
        private readonly OrderFactory                $orderFactory
    ) {

    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        try {
            $orders = $observer->getData('orders');
            foreach ($orders as $order) {
                $item = current($order->getItems());
                $tenant = $this->helper->getTenantByProductSku($item['sku']);
                $selectedMethod = $this->helper->getSplitMethodByTenant($order->getShippingMethod(), $tenant);
                $parentOrder = $this->orderFactory->create()->load($order->getSplitOrderParentId());

                $omnikRates = $this->omnikFreightRatesRepository->getFreightRate($parentOrder->getQuoteId(), $selectedMethod, $tenant, 'neq');
                foreach ($omnikRates->getItems() as $omnikRate) {
                    $omnikRate->delete();
                }
            }
        } catch (\Exception $e) {
        }
    }
}
