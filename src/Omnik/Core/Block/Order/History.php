<?php

namespace Omnik\Core\Block\Order;

use Omnik\Core\Helper\Sales\Data as Helper;
use Magento\Sales\Block\Order\History as BlockHistory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Framework\Exception\NoSuchEntityException;

class History extends BlockHistory
{
    /**
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param Session $customerSession
     * @param Config $orderConfig
     * @param Helper $helper
     */
    public function __construct(
        public readonly Context  $context,
        public CollectionFactory $orderCollectionFactory,
        public readonly Session  $customerSession,
        public readonly Config   $orderConfig,
        private readonly Helper  $helper,
        array                    $data = []
    ) {
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /**
     * @return bool|Collection
     * @throws NoSuchEntityException
     */
    public function getOrders()
    {
        if (!$this->orders) {
            $this->orders = $this->helper->getOrdersCollection();
        }

        return $this->orders;
    }
}
