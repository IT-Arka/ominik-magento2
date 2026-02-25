<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Orders;

use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Model\Integration\Order\GetOrder;
use Omnik\Core\Api\SplitOrderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\InvoiceOrderInterface;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class Invoice implements NotifyHandlerInterface
{
    public const STATUS_ORDER_INVOICED = 'order_invoiced';
    public const STATUS_ORDER_PENDING = 'pending';


    /**
     * @var InvoiceOrderInterface
     */
    private InvoiceOrderInterface $invoiceOrderInterface;

    /**
     * @var NotifyOmnikDataInterface
     */
    private NotifyOmnikDataInterface $notifyOmnikDataInterface;

    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;

    /**
     * @var OrderResourceInterface
     */
    private OrderResourceInterface $orderResource;

    /**
     * @var GetOrder
     */
    private GetOrder $getOrder;

    /**
     * @param InvoiceOrderInterface $invoiceOrderInterface
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param OrderFactory $orderFactory
     * @param OrderResourceInterface $orderResource
     * @param GetOrder $getOrder
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        InvoiceOrderInterface          $invoiceOrderInterface,
        NotifyOmnikDataInterface       $notifyOmnikDataInterface,
        OrderFactory                   $orderFactory,
        OrderResourceInterface         $orderResource,
        GetOrder                       $getOrder,
        private readonly InvoiceSender $invoiceSender
    ) {
        $this->orderResource = $orderResource;
        $this->orderFactory = $orderFactory;
        $this->invoiceOrderInterface = $invoiceOrderInterface;
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
        $this->getOrder = $getOrder;
    }

    /**
     * @param array $registers
     * @return void
     */
    public function execute(array $registers): void
    {
        $qtyRegisters = 0;

        foreach ($registers as $data) {
            if ($data['resource_type'] == self::RESOURCE_ORDER) {
                if ($data['event'] !== self::INVOICED) {
                    continue;
                }

                if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                    break;
                }

                try {
                    $orderId = (string)$data['resource_market_place_id'];
                    $order = $this->getOrderByIncrementId($orderId);

                    if ($order->getInvoiceCollection()->getTotalCount() > 0) {
                        $invoiceOmnik = $this->getKeyOmnik($data, $orderId);
                        if (!empty($invoiceOmnik)) {
                            $orderInvoice = $order->getInvoiceCollection()->getFirstItem();
                            $orderInvoice->addComment('KEY NFE: ' . $invoiceOmnik['key']);

                            if ($invoiceOmnik['linkXml']) {
                                $orderInvoice->addComment('LINK XML: ' . $invoiceOmnik['linkXml']);
                            }

                            if ($invoiceOmnik['linkDanfe']) {
                                $orderInvoice->addComment('LINK DANFE: ' . $invoiceOmnik['linkDanfe']);
                            }

                            $orderInvoice->save();
                            $this->invoiceSender->send($orderInvoice);
                        }

                        $order->setStatus(self::STATUS_ORDER_INVOICED);
                        $order->save();
                        $this->setStatusToParentOrder($order);

                        $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id']);
                        $qtyRegisters++;
                        continue;
                    }

                    $this->invoiceOrderInterface->execute(
                        $order->getId(),
                        true,
                        [],
                        true,
                        false,
                        null,
                        null
                    );

                    $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id']);
                    $qtyRegisters++;
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
     * @param $data
     * @param $orderId
     * @return array
     * @throws \Exception
     */
    public function getKeyOmnik($data, $orderId)
    {
        $orderOmnik = $this->getOrder->execute($data['seller'], (int)$data['store_id'], $orderId);
        if (isset($orderOmnik['invoiceData']) && !empty($orderOmnik['invoiceData'])) {
            return [
                'key' => $orderOmnik['invoiceData']['key'],
                'linkXml' => $orderOmnik['invoiceData']['linkXML'],
                'linkDanfe' => $orderOmnik['invoiceData']['linkDANFE']
            ];
        }
        return [];
    }

    /**
     * @param $incrementId
     * @return OrderInterface
     * @throws \Exception
     */
    private function getOrderByIncrementId($incrementId): OrderInterface
    {
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $incrementId, OrderInterface::INCREMENT_ID);

        if (empty($order->getId())) {
            throw new \Exception('Pedido com Increment ID: ' . $incrementId . ' não encontrado.');
        }
        return $order;
    }

    /**
     * @param $childOrder
     * @return void
     */
    private function setStatusToParentOrder($childOrder)
    {
        try {
            $parentOrder = $this->getParentOrder($childOrder);
            if ($parentOrder->getStatus() == self::STATUS_ORDER_INVOICED) {
                return;
            }

            $parentOrder->setStatus(self::STATUS_ORDER_INVOICED);
            $parentOrder->save();

        } catch (\Exception $e) {

        }

    }

    /**
     * @param Order $order
     * @return Order
     */
    private function getParentOrder(Order $order)
    {
        if ($order->getSplitOrderType() != SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD) {
            return $order;
        }
        return $this->orderFactory->create()->load($order->getSplitOrderParentId());
    }
}
