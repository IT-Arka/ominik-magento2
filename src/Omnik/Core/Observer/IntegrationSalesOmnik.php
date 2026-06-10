<?php

declare(strict_types=1);

namespace Omnik\Core\Observer;

use Exception;
use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Params;
use Omnik\Core\Model\Integration\Order\SendStatusNew;
use Omnik\Core\Api\ProductSellerInterface;
use Omnik\Core\Api\SplitOrderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

class IntegrationSalesOmnik implements ObserverInterface
{
    /**
     * @var Params
     */
    private Params $params;

    /**
     * @var SendStatusNew
     */
    private SendStatusNew $sendStatusNew;

    /**
     * @var ProductSellerInterface
     */
    private ProductSellerInterface $productSellerInterface;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepositoryInterface;

    /**
     * @var Logger
     */
    private Logger $salesLogger;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var ConfigHelper
     */
    private ConfigHelper $_configHelper;

    /**
     * @param Params $params
     * @param SendStatusNew $sendStatusNew
     * @param ProductSellerInterface $productSellerInterface
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Logger $salesLogger
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Params                     $params,
        SendStatusNew              $sendStatusNew,
        ProductSellerInterface     $productSellerInterface,
        ProductRepositoryInterface $productRepositoryInterface,
        Logger                     $salesLogger,
        StoreManagerInterface      $storeManager,
        OrderRepositoryInterface   $orderRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        ConfigHelper               $configHelper
    ) {
        $this->params = $params;
        $this->sendStatusNew = $sendStatusNew;
        $this->productSellerInterface = $productSellerInterface;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->salesLogger = $salesLogger;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_configHelper = $configHelper;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $orders = $observer->getEvent()->getData('orders');
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    $this->integrateOrder($order);
                }
            }
        } catch (Exception $e) {
            $this->salesLogger->error($e->getMessage());
        }
    }

    /**
     * @param Order $order
     * @return array|mixed|true[]|null
     * @throws NoSuchEntityException
     */
    public function integrateOrder(Order $order)
    {
        if((boolean)$order->getHasIntegratedOmnik()){
            $this->salesLogger->info('Pedido já integrado order id: ' . $order->getIncrementId());
            return ['has_integrated' => true];
        }

        $params = $this->params->createParameters($order);
        $storeId = (int)$order->getStoreId();
        $tenant = $this->getTenant($order);

        $integratedOrder = $this->sendStatusNew->execute($params, $tenant, $storeId);

        $this->setOrderIntegrated($integratedOrder, $order);

        if (isset($integratedOrder['fails']) && $integratedOrder['fails'] == true) {
            $this->salesLogger->info('Erro ao integrar o pedido: ' . $order->getIncrementId());
            if(gettype($integratedOrder) == 'array' && isset($integratedOrder['response'])) {
                $this->salesLogger->info(print_r($integratedOrder['response'], true));
            }

            foreach($integratedOrder as $key => $value) {
                if ($key == 'messages' || $key == 'message') {
                    $this->salesLogger->error('Erro de integração do pedido: ' . $integratedOrder[$key]);
                }
            }
        }
        
        return $integratedOrder;
    }

    /**
     * @param array $integratedResponse
     * @param Order $order
     * @return void
     * @throws Exception
     */
    public function setOrderIntegrated(array $integratedResponse, Order $order)
    {
        if (isset($integratedResponse['orderData'])) {
            $order->setHasIntegratedOmnik(1);
            $order->save();
        }
    }

    /**
     * @param $order
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getTenant($order)
    {
        $storeId   = (int)$order->getStoreId();
        $attrCode  = $this->_configHelper->getAttrTenant($storeId);
        $item      = current($order->getItems())->getData();
        $product   = $this->productRepositoryInterface->get($item['sku']);
        $tenantVal = $product->getCustomAttribute($attrCode)?->getValue();
        if (empty($tenantVal)) {
            throw new NoSuchEntityException(
                __('Produto "%1" sem atributo "%2" (Tenant) preenchido.', $item['sku'], $attrCode)
            );
        }
        return $tenantVal;
    }

    /**
     * @param $order
     * @return array|bool[]|mixed|void|null
     */
    public function reintegrateOrder($order)
    {
        try {
            if ($order->getSplitOrderType() == SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD) {
                return $this->integrateOrder($order);
            }

            $orders = $this->getChildOrdersToReintegrate($order);

            // Pedido único (não-split): não tem filhos, então reintegra ele mesmo.
            if (empty($orders)) {
                return $this->integrateOrder($order);
            }

            $return = true;
            foreach ($orders as $orderChild) {
                $response = $this->integrateOrder($orderChild);
                if (!isset($response['orderData']['id'])) {
                    $return = false;
                }
            }

            return $return;
        } catch (Exception $e) {
            $this->salesLogger->error($e->getMessage());
        }

    }

    /**
     * @param $order
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getChildOrders($order)
    {
        $this->searchCriteriaBuilder->addFilter(SplitOrderInterface::SPLIT_ORDER_TYPE, SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD, 'eq');
        $this->searchCriteriaBuilder->addFilter(SplitOrderInterface::SPLIT_ORDER_PARENT_ID, $order->getId(), 'eq');
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->orderRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param $order
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getChildOrdersToReintegrate($order)
    {
        $this->searchCriteriaBuilder->addFilter(SplitOrderInterface::SPLIT_ORDER_TYPE, SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD, 'eq');
        $this->searchCriteriaBuilder->addFilter(SplitOrderInterface::SPLIT_ORDER_PARENT_ID, $order->getId(), 'eq');
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->orderRepository->getList($searchCriteria)->getItems();
    }

}
