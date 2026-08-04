<?php

namespace Omnik\Core\Model;

use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Sales\Approvation;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Model\Order\ChildOrderPayment;
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
    private Logger $_logger;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroup $filterGroup
     * @param OrderRepositoryInterface $orderRepository
     * @param ChildOrderPayment $childOrderPayment
     * @param Approvation $integrationApprovation
     * @param ProductRepositoryInterface $productRepository
     * @param OrderFactory $orderFactory
     * @param ConfigHelper $configHelper
     * @param Logger $logger
     */
    public function __construct(
        SearchCriteriaBuilder                       $searchCriteriaBuilder,
        FilterBuilder                               $filterBuilder,
        FilterGroup                                 $filterGroup,
        OrderRepositoryInterface                    $orderRepository,
        ChildOrderPayment                           $childOrderPayment,
        Approvation                                 $integrationApprovation,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly OrderFactory               $orderFactory,
        private readonly ConfigHelper               $configHelper,
        Logger                                      $logger
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->orderRepository = $orderRepository;
        $this->filterGroup = $filterGroup;
        $this->childOrderPayment = $childOrderPayment;
        $this->integrationApprovation = $integrationApprovation;
        $this->_logger = $logger;
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

                // Só faz sentido enviar o status de um filho que já existe na Omnik
                // (ou seja, que teve o POST de pedido novo confirmado).
                $isIntegratedOmnik = $this->isIntegratedOrderOmnik($order);

                if (!$isIntegratedOmnik) {
                    continue;
                }

                $isGetnetCard = $parentOrder->getPayment()->getMethod() == Params::GETNET_CARD;

                if ($isUpdate || $isGetnetCard) {
                    $this->integrationApprovation->integrate($order);
                }

            } catch (\Exception $e) {
                $this->_logger->error(
                    'Omnik HandleChildOrders failed for order '
                    . $order->getIncrementId() . ': ' . $e->getMessage(),
                    ['exception' => $e, 'parent_order_id' => $parentOrderId]
                );
            }
        }
    }

    /**
     * Informa se o pedido já foi confirmado na Omnik.
     *
     * Usa a flag persistida `sales_order.has_integrated_omnik`, gravada por
     * IntegrationSalesOmnik::setOrderIntegrated() quando o POST de pedido novo retorna
     * `orderData`. É a mesma fonte que o gate do ProcessQueue consulta.
     *
     * Antes isso era um GET HTTP à Omnik por pedido, o que trazia dois problemas:
     *  - Falha de transporte (timeout/5xx/DNS) devolvia a mesma resposta de "não existe",
     *    então o envio do status era pulado silenciosamente e sem retry — o pedido ficava
     *    sem a confirmação de pagamento na Omnik. Falha de transporte não é NOT_FOUND.
     *  - Uma chamada de rede por filho dentro do laço, a cada save do pedido pai.
     *
     * @param $order
     * @return bool
     */
    public function isIntegratedOrderOmnik($order)
    {
        return (bool)$order->getData(SplitOrderInterface::SPLIT_ORDER_HAS_INTEGRATED);
    }

    /**
     * @param $order
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTenant($order)
    {
        $storeId   = (int)$order->getStoreId();
        $attrCode  = $this->configHelper->getAttrTenant($storeId);
        $item      = current($order->getItems())->getData();
        $product   = $this->productRepository->get($item['sku']);
        $tenantVal = $product->getCustomAttribute($attrCode)?->getValue();
        if (empty($tenantVal)) {
            throw new \RuntimeException(
                sprintf('Produto "%s" sem atributo "%s" (Tenant) preenchido.', $item['sku'], $attrCode)
            );
        }
        return $tenantVal;
    }
}
