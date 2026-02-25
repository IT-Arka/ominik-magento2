<?php

namespace Omnik\Core\Model;

use Omnik\Core\Model\Integration\Sales\Approvation;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Model\Order\ChildOrderPayment;
use Omnik\Core\Model\Integration\Order\GetOrder;
use Omnik\Core\Model\Integration\Params;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;

class HandleChildOrders
{
    /** @var SearchCriteriaBuilder */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /** @var FilterBuilder */
    private FilterBuilder $filterBuilder;

    /** @var OrderRepositoryInterface */
    private OrderRepositoryInterface $orderRepository;

    /** @var FilterGroup */
    private FilterGroup $filterGroup;

    /** @var ChildOrderPayment */
    private ChildOrderPayment $childOrderPayment;
    private Approvation $integrationApprovation;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroup $filterGroup
     * @param OrderRepositoryInterface $orderRepository
     * @param ChildOrderPayment $childOrderPayment
     * @param Approvation $integrationApprovation
     * @param ProductRepositoryInterface $productRepository
     * @param GetOrder $getOrder
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        SearchCriteriaBuilder                       $searchCriteriaBuilder,
        FilterBuilder                               $filterBuilder,
        FilterGroup                                 $filterGroup,
        OrderRepositoryInterface                    $orderRepository,
        ChildOrderPayment                           $childOrderPayment,
        Approvation                                 $integrationApprovation,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly GetOrder                   $getOrder,
        private readonly OrderFactory               $orderFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->orderRepository = $orderRepository;
        $this->filterGroup = $filterGroup;
        $this->childOrderPayment = $childOrderPayment;
        $this->integrationApprovation = $integrationApprovation;
    }

    /**
     * @param $parentOrderId
     * @param $status
     * @param bool $isUpdate
     * @return void
     */
    public function execute($parentOrderId, $status, bool $isUpdate = false)
    {
        $filter1 = $this->filterBuilder->setField(SplitOrderInterface::SPLIT_ORDER_TYPE)
            ->setConditionType('eq')
            ->setValue(SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD)
            ->create();
        $filter2 = $this->filterBuilder->setField(SplitOrderInterface::SPLIT_ORDER_PARENT_ID)
            ->setConditionType('eq')
            ->setValue($parentOrderId)
            ->create();

        $filterGroup1 = $this->filterGroup->setFilters([$filter1]);
        $filterGroup2 = $this->filterGroup->setFilters([$filter2]);

        $searchCriteria = $this->searchCriteriaBuilder
            ->setFilterGroups([$filterGroup1, $filterGroup2])
            ->create();

        $result = $this->orderRepository->getList($searchCriteria);
        if (!$result) {
            return;
        }

        $orders = $result->getItems();

        foreach ($orders as $order) {
            try {
                $parentOrder = $this->orderFactory->create()->load($parentOrderId, 'entity_id');

                if ($this->integrationApprovation->isApproved($parentOrder)) {
                    $this->childOrderPayment->invoice($order);
                }

                if ($this->integrationApprovation->isNotApproved($parentOrder)) {
                    $this->childOrderPayment->cancel($order);
                }

                $isIntegratedOmnik = $this->isIntegratedOrderOmnik($order);

                if (!$isUpdate && $isIntegratedOmnik &&
                    ($parentOrder->getPayment()->getMethod() == Params::GETNET_CARD)
                ) {
                    $this->integrationApprovation->integrate($order);
                }

                if ($isUpdate && $this->isIntegratedOrderOmnik($order)) {
                    $this->integrationApprovation->integrate($order);
                }

            } catch (\Exception $e) {
            }
        }
    }

    /**
     * @param $order
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isIntegratedOrderOmnik($order)
    {
        $tenant = $this->getTenant($order);
        $orderOmnik = $this->getOrder->execute($tenant, $order->getStoreId(), $order->getIncrementId());
        if (isset($orderOmnik['orderData']['id'])) {
            return true;
        }
        return false;
    }

    /**
     * @param $order
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTenant($order)
    {
        $item = current($order->getItems())->getData();
        $product = $this->productRepository->get($item['sku']);
        return $product->getCustomAttribute('tenant')->getValue();
    }
}
