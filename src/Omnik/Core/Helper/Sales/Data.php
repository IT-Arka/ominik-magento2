<?php

declare(strict_types=1);

namespace Omnik\Core\Helper\Sales;

use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Model\Seller\ProductSeller;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Sales\Model\Order\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\Collection;

class Data extends AbstractHelper
{
    /**
     * @param Context $context
     * @param Config $orderConfig
     * @param Session $session
     * @param StoreManagerInterface $storeManager
     * @param ProductSeller $productSeller
     * @param CollectionFactoryInterface $orderCollectionFactory
     */
    public function __construct(
        public readonly Context                $context,
        private readonly Config                $orderConfig,
        private readonly Session               $session,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductSeller         $productSeller,
        private CollectionFactoryInterface $orderCollectionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return false|Collection
     * @throws NoSuchEntityException
     */
    public function getOrdersCollection()
    {
        if (!($customerId = $this->session->getCustomerId())) {
            return false;
        }

        return $this->getOrderCollectionFactory()->create($customerId)->addAttributeToSelect(
            '*'
        )->addAttributeToFilter(
            'customer_id',
            $customerId
        )->addAttributeToFilter(
            'store_id',
            $this->storeManager->getStore()->getId()
        )->addFieldToFilter(
            SplitOrderInterface::SPLIT_ORDER_TYPE,
            SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT
        )->addAttributeToFilter(
            'status',
            ['in' => $this->orderConfig->getVisibleOnFrontStatuses()]
        )->addAttributeToSort(
            'created_at',
            'desc'
        );
    }

    /**
     * @return CollectionFactoryInterface|mixed
     */
    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }
        return $this->orderCollectionFactory;
    }

    /**
     * @param $order
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSellerNameByOrderChild($order): string
    {
        try {
            if ($order->getSplitOrderType() == SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD) {
                $item = current($order->getItems());
                return $this->productSeller->getSellerNameBySku($item->getSku());
            }
        }catch (\Exception $e){

        }
        return '';
    }
}
