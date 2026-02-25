<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Orders;

use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Model\Cron\SaveHandler\Orders\Cancel;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Omnik\Core\Helper\StatusMapping;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Omnik\Core\Logger\Logger;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;

class StatusOrder implements NotifyHandlerInterface
{
    /**
     * @var OrderResourceInterface
     */
    private OrderResourceInterface $orderResource;

    /**
     * @var OrderInterfaceFactory
     */
    private OrderInterfaceFactory $orderFactory;

    /**
     * @var OrderManagementInterface
     */
    private OrderManagementInterface $orderManagementInterface;

    /**
     * @var OrderStatusHistoryInterface
     */
    private OrderStatusHistoryInterface $orderStatusHistoryInterface;

    /**
     * @var NotifyOmnikDataInterface
     */
    private NotifyOmnikDataInterface $notifyOmnikDataInterface;

    /**
     * @var Cancel
     */
    private Cancel $cancel;

    /**
     * @var StatusMapping
     */
    private StatusMapping $helperStatusMapping;

    /**
     * @var OrderRepositoryInterface $orderRepository
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var OrderStatusHistoryInterfaceFactory
     */
    private $statusHistoryFactory;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $statusHistoryRepository;

    /**
     * @param OrderResourceInterface $orderResource
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderManagementInterface $orderManagementInterface
     * @param OrderStatusHistoryInterface $orderStatusHistoryInterface
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param Cancel $cancel
     * @param StatusMapping $helperStatusMapping
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $logger
     * @param OrderStatusHistoryInterfaceFactory $statusHistoryFactory
     * @param OrderStatusHistoryRepositoryInterface $statusHistoryRepository
     */
    public function __construct(
        OrderResourceInterface      $orderResource,
        OrderInterfaceFactory       $orderFactory,
        OrderManagementInterface    $orderManagementInterface,
        OrderStatusHistoryInterface $orderStatusHistoryInterface,
        NotifyOmnikDataInterface    $notifyOmnikDataInterface,
        Cancel                      $cancel,
        StatusMapping               $helperStatusMapping,
        OrderRepositoryInterface $orderRepository,
        Logger $logger,
        OrderStatusHistoryInterfaceFactory $statusHistoryFactory,
        OrderStatusHistoryRepositoryInterface $statusHistoryRepository
    ) {
        $this->orderResource = $orderResource;
        $this->orderFactory = $orderFactory;
        $this->orderManagementInterface = $orderManagementInterface;
        $this->orderStatusHistoryInterface = $orderStatusHistoryInterface;
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
        $this->cancel = $cancel;
        $this->helperStatusMapping = $helperStatusMapping;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->statusHistoryFactory = $statusHistoryFactory;
        $this->statusHistoryRepository = $statusHistoryRepository;
    }

    /**
     * @param array $registers
     * @return void
     */
    public function execute(array $registers): void
    {
        $qtyRegisters = 0;
        $orderStatusArray = [self::APPROVED, self::DELIVERED, self::NOT_APPROVED, self::CANCELED, self::PARTIALLYCANCELED];
        
        $isMapEnable = $this->helperStatusMapping->isMapEnabled();

        $statusArray = $this->helperStatusMapping->getAllActiveMappings();

        if (!empty($registers)) {
            foreach ($registers as $data) {
                try {
                    if ($isMapEnable && count($statusArray ) > 0)
                    {
                        if ($data['resource_type'] == self::RESOURCE_ORDER) {
                            if ($data['event'] == 'NEW') {
                                $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id']);
                                continue;
                            }
                            if (!$this->helperStatusMapping->getAdobeStatusByOmnikStatus($data['event'])) {
                                continue;
                            }

                            $orderIncrementId = $data['resource_market_place_id'];
                            $order = $this->getOrderByIncrementId($orderIncrementId);
                            $orderId = $order->getId();
                            $currentState = $order->getState();
                            $validStatuses = $order->getConfig()->getStateStatuses($currentState);

                            if (empty($orderId)) {
                                throw new \Exception('ID pedido não encontrado: ' . $orderIncrementId);
                            }

                            $statusOrder = $this->helperStatusMapping->getAdobeStatusByOmnikStatus($data['event']);

                            if ($statusOrder) {
                                $orderState = $this->getStateOrder($statusOrder);
                                $statusHistory = $this->statusHistoryFactory->create();
                                $statusHistory->setParentId($orderId);
                                $statusHistory->setComment("Status do pedido alterado para: " . $statusOrder);
                                $statusHistory->setIsCustomerNotified(0);
                                $statusHistory->setIsVisibleOnFront(1);
                                $statusHistory->setStatus($statusOrder);
                                $statusHistory->setCreatedAt(date('Y-m-d H:i:s'));
                                $this->statusHistoryRepository->save($statusHistory);
                                $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id']);
                                try {
                                    $orderState = $this->getStateOrder($statusOrder);
                                    $order = $this->orderRepository->get($orderId);
                                    $order->setState($currentState)->setStatus(strtolower($data['event']));
                                    $this->orderRepository->save($order);
                                } catch (\Exception $e) {
                                    $this->logger->error($e->getMessage());
                                }
                                $qtyRegisters++;
                            }
                        }
                    } else {
                        if ($data['resource_type'] == self::RESOURCE_ORDER) {
                            if ($data['event'] == 'NEW') {
                                $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id']);
                                continue;
                            }

                            if (!in_array($data['event'], $orderStatusArray)) {
                                continue;
                            }

                            $orderIncrementId = $data['resource_market_place_id'];
                            $order = $this->getOrderByIncrementId($orderIncrementId);
                            $orderId = $order->getId();

                            if (empty(self::STATUS[$data['event']])) {
                                continue;
                            }

                            if (empty($orderId)) {
                                throw new \Exception('ID pedido não encontrado: ' . $orderIncrementId);
                            }

                            if ($data['event'] == self::APPROVED || $data['event'] == self::DELIVERED) {
                                $status = $this->getStatus($data['event']);

                                $this->orderStatusHistoryInterface->setComment($status[1]);
                                $this->orderStatusHistoryInterface->setIsCustomerNotified(0);
                                $this->orderStatusHistoryInterface->setIsVisibleOnFront(1);
                                $this->orderStatusHistoryInterface->setParentId($orderId);
                                $this->orderStatusHistoryInterface->setStatus($status[0]);
                                $this->orderManagementInterface->addComment($orderId, $this->orderStatusHistoryInterface);
                                $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id']);
                                $qtyRegisters++;
                            }

                            if ($data['event'] == self::NOT_APPROVED || $data['event'] == self::CANCELED || $data['event'] == self::PARTIALLYCANCELED) {
                                $status = $order->canCancel() ? $this->getStatus($data['event']) : ['closed', __('Closed')];
                                $isCanceled = $this->cancel->proccess($order, $data['event']);

                                if ($isCanceled) {
                                    $this->orderStatusHistoryInterface->setComment($status[1]);
                                    $this->orderStatusHistoryInterface->setIsCustomerNotified(0);
                                    $this->orderStatusHistoryInterface->setIsVisibleOnFront(1);
                                    $this->orderStatusHistoryInterface->setParentId($orderId);
                                    $this->orderStatusHistoryInterface->setStatus($status[0]);
                                    $this->orderManagementInterface->addComment($orderId, $this->orderStatusHistoryInterface);
                                    $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id']);

                                    $qtyRegisters++;
                                }
                            }
                        }
                    }

                    if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                        break;
                    }

                } catch (\Exception $e) {
                    $this->notifyOmnikDataInterface->changeStatusNotify(
                        (int)$data['entity_id'],
                        NotifyOmnikDataInterface::STATUS_ERROR,
                        $e->getMessage()
                    );
                }
            }
        }
    }

    /**
     * @param string $status
     * @return array
     */
    private function getStatus(string $status): array
    {
        $descriptions = [];
        $status = self::STATUS[$status];

        foreach ($status as $key => $data) {
            $descriptions[0] = $key;
            $descriptions[1] = __($data);
        }

        return $descriptions;
    }

    /**
     * @param $incrementId
     * @return OrderInterface
     */
    private function getOrderByIncrementId($incrementId): OrderInterface
    {
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $incrementId, OrderInterface::INCREMENT_ID);
        return $order;
    }

    private function getStateOrder($status)
    {
        $state = '';
        switch ($status) {
            case "pending":
                $state = 'new';
                break;
            case "pending_payment":
                $state = 'pending_payment';
                break;
            case "complete":
                $state = 'complete';
                break;
            case "closed":
                $state = 'closed';
                break;
            case "canceled":
                $state = 'canceled';
                break;
            case "holded":
                $state = 'holded';
                break;
            case "payment_review":
                $state = 'payment_review';
                break;
            case "processing":
            case "fraud":
                $state = 'processing';
                break;
            case "approved":
                $state = 'approved';
                break;
            case "partiallyreturned":
                $state = 'partiallyreturned';
                break;
            case "partiallycanceled":
                $state = 'partiallycanceled';
                break;
            case "invoiced":
                $state = 'invoiced';
                break;
            case "sent":
                $state = 'sent';
                break;
            case "delivered":
                $state = 'delivered';
                break;
            case "shipping_label":
                $state = 'shipping_label';
                break;
            case "error_order":
                $state = 'error_order';
                break;
            case "receiving_period":
                $state = 'receiving_period';
                break;
            case "reverse_request":
                $state = 'reverse_request';
                break;
            case "reverse_in_progress":
                $state = 'reverse_in_progress';
                break;
            case "reverse_receive":
                $state = 'reverse_receive';
                break;
            case "reverse_canceled":
                $state = 'reverse_canceled';
                break;
            case "reverse_concluded":
                $state = 'reverse_concluded';
                break;
            default:
                $state = 'new';
                break;
        }
        return $state;
    }
}
