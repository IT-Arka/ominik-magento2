<?php

namespace Omnik\Core\Block\Order;

use Omnik\Core\Helper\Sales\Data as Helper;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Recent as BlockRecent;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\Collection;

class Recent extends BlockRecent
{
    /**
     * @param Context $context
     * @param CollectionFactoryInterface $orderCollectionFactory
     * @param Session $customerSession
     * @param Config $orderConfig
     * @param StoreManagerInterface $storeManager
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        public Context $context,
        public readonly CollectionFactoryInterface $orderCollectionFactory,
        public readonly Session $customerSession,
        public readonly Config $orderConfig,
        public readonly StoreManagerInterface $storeManager,
        private readonly Helper $helper,
        array $data = []

    ) {
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data, $storeManager);
    }

    /**
     * @return false|Collection
     * @throws NoSuchEntityException
     */
    public function getParentOrders()
    {
        $orderCollection = $this->helper->getOrdersCollection();
        $orders = $orderCollection->setPageSize(self::ORDER_LIMIT)->load();

        $this->setOrders($orders);

        return $orders;
    }
}
