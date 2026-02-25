<?php

namespace Omnik\Core\Model\Cron\SaveHandler\Orders;

use Omnik\Core\Api\NotifyHandlerInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use \Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;

class Cancel
{
    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepositoryInterface;

    /**
     * @var CreditmemoSender
     */
    private CreditmemoSender $creditmemoSender;

    /**
     * @var CreditmemoLoader
     */
    private CreditmemoLoader $creditmemoLoader;

    /**
     * @var CreditmemoManagementInterface
     */
    private CreditmemoManagementInterface $creditmemoManagement;

    public function __construct(
        OrderRepositoryInterface $orderRepositoryInterface,
        CreditmemoSender $creditmemoSender,
        CreditmemoLoader $creditmemoLoader,
        CreditmemoManagementInterface $creditmemoManagement
    ) {
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->creditmemoSender = $creditmemoSender;
        $this->creditmemoLoader = $creditmemoLoader;
        $this->creditmemoManagement = $creditmemoManagement;
    }

    /**
     * @param $order
     * @param $event
     * @return bool
     */
    public function proccess($order, $event): bool
    {
        $isCanceled = false;
        switch ($event) {
            case NotifyHandlerInterface::NOT_APPROVED:
            case NotifyHandlerInterface::CANCELED:
                $isCanceled = $this->cancelOrder($order);
                break;
            case NotifyHandlerInterface::PARTIALLYCANCELED:
                $isCanceled = $this->partiallyCancelOrder($order);
                break;
        }
        return $isCanceled;
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function cancelOrder(OrderInterface $order): bool
    {
        if (!$order->canCancel()) {
            return $this->fullyCancelOrder($order);
        }

        try {
            $order->cancel();
            $this->orderRepositoryInterface->save($order);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function partiallyCancelOrder(OrderInterface $order): bool
    {
        try {
            // TODO: Get the items that were canceled through the Omnik API and perform a partial refund
            return false;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function fullyCancelOrder(OrderInterface $order): bool
    {
        try {
            return $this->createCreditMemo(
                $order->getId(),
                $order->getItems(),
                $order->getBaseTotalPaid(),
                $order->getShippingAmount()
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $orderId
     * @param $items
     * @param $amountToRefund
     * @param $shippingAmount
     * @param bool $sendEmail
     * @return bool
     */
    public function createCreditMemo($orderId, $items, $amountToRefund, $shippingAmount, $sendEmail = false): bool
    {
        $creditMemoData = [];
        $creditMemoData['do_offline'] = 1;
        $creditMemoData['send_email'] = $sendEmail;
        $creditMemoData['comment_customer_notify'] = $sendEmail;
        $creditMemoData['is_visible_on_front'] = false;
        $creditMemoData['shipping_amount'] = $shippingAmount;
        $creditMemoData['comment_text'] = 'Canceled';
        $creditMemoData['refund_customerbalance_return_enable'] = true;
        $creditMemoData['refund_customerbalance_return'] = $amountToRefund;

        $itemToCredit = null;

        /** @var OrderItemInterface $item */
        foreach ($items as $item) {
            $itemToCredit[$item->getItemId()] = ['qty'=>$item->getQtyInvoiced()];
        }

        $creditMemoData['items'] = $itemToCredit;

        try {
            $this->creditmemoLoader->setOrderId($orderId);
            $this->creditmemoLoader->setCreditmemo($creditMemoData);

            $creditmemo = $this->creditmemoLoader->load();
            if ($creditmemo) {
                if (!$creditmemo->isValidGrandTotal()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The credit memo\'s total must be positive.')
                    );
                }

                if (!empty($creditMemoData['comment_text'])) {
                    $creditmemo->addComment(
                        $creditMemoData['comment_text'],
                        $creditMemoData['comment_customer_notify'],
                        $creditMemoData['is_visible_on_front']
                    );

                    $creditmemo->setCustomerNote($creditMemoData['comment_text']);
                    $creditmemo->setCustomerNoteNotify($creditMemoData['comment_customer_notify']);
                }

                $creditmemo->getOrder()->setCustomerNoteNotify($creditMemoData['send_email']);
                $this->creditmemoManagement->refund($creditmemo, (bool)$creditMemoData['do_offline']);

                if ($creditMemoData['send_email']) {
                    $this->creditmemoSender->send($creditmemo);
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
