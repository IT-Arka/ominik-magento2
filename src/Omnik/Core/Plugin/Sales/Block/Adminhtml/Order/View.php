<?php

declare(strict_types=1);

namespace Omnik\Core\Plugin\Sales\Block\Adminhtml\Order;

use Omnik\Core\Helper\SalesIntegration\Data;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    /**
     * @var Data
     */
    private Data $salesHelper;

    /**
     * @param Data $salesHelper
     */
    public function __construct(Data $salesHelper)
    {
        $this->salesHelper = $salesHelper;
    }

    /**
     * @param OrderView $subject
     * @return void
     */
    public function beforeSetLayout(OrderView $subject)
    {
        $order = $subject->getOrder();
        $incrementId = $order->getIncrementId();

        $notificationOrderIncrementId = $this->salesHelper->getNotificationNewOrder($incrementId);
        $notificationOrder = $this->salesHelper->getNotificationNewOrder($subject->getOrderId());

        if (empty($notificationOrder) && empty($notificationOrderIncrementId)) {
            $message = __('Are you sure you want reintegrate order?');

            $pathReintegrateOrder = $subject->getUrl(
                'notify/reintegrationorder/index/order_id/' . $subject->getOrderId()
            );

            $subject->addButton(
                'button_reintegrate_order',
                [
                    'label' => __('Reintegrate order OMNIK'),
                    'id' => 'order-view-reintegrate-order',
                    'onclick' => "confirmSetLocation('{$message}', '{$pathReintegrateOrder}')"
                ]
            );
        }
    }
}
