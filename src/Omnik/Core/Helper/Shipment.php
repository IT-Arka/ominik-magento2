<?php

namespace Omnik\Core\Helper;

use Magento\Sales\Model\Convert\Order as OrderConvert;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ShipOrder;
use Magento\Sales\Model\Order\Shipment\TrackFactory;

class Shipment
{
    /**
     * @param TrackFactory $trackFactory
     * @param ShipmentCommentCreationInterface $shipmentCommentCreation
     * @param ShipOrder $shipOrder
     * @param OrderConvert $orderConvert
     */
    public function __construct(
        private readonly TrackFactory                     $trackFactory,
        private readonly ShipmentCommentCreationInterface $shipmentCommentCreation,
        private readonly ShipOrder                        $shipOrder,
        private readonly OrderConvert                     $orderConvert
    ) {

    }

    /**
     * @param Order $order
     * @param array $items
     * @param $comment
     * @param $notify
     * @param $includeComment
     * @param $trackingNumber
     * @param $carrierCode
     * @return int|null
     */
    public function createShipment(Order $order, array $items, $comment = null, $notify = false,
                                   $includeComment = false, $trackingNumber = '', $carrierCode = '')
    {
        $shipmentId = null;
        if ($order->canShip()) {
            try {
                $orderId = $order->getId();
                $tracks = $this->getTrackingData($trackingNumber, $carrierCode);
                $comment = $this->setShipmentComment($comment);
                $shippedItems = $this->createShipmentItems($items, $order);

                $shipmentId = $this->shipOrder->execute($orderId,
                    $shippedItems,
                    $notify,
                    $includeComment,
                    $comment,
                    $tracks);
            } catch (\Exception $e) {
            }

            return $shipmentId;
        }

        return null;
    }

    /**
     * @param $trackingNumber
     * @param $carrierTitle
     * @return array
     */
    public function getTrackingData($trackingNumber, $carrierTitle)
    {
        $track = $this->trackFactory->create();
        $track->setTrackNumber($trackingNumber);
        $track->setCarrierCode("Custom");
        $track->setTitle($carrierTitle);
        $trackInfo[] = $track;

        return $trackInfo;
    }

    /**
     * @param $comment
     * @return ShipmentCommentCreationInterface
     */
    protected function setShipmentComment($comment)
    {
        $comment = !empty($comment) ? $comment : 'Not Available';
        return $this->shipmentCommentCreation->setComment($comment);
    }

    /**
     * @param array $items
     * @param $order
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createShipmentItems(array $items, $order)
    {
        $shipmentItem = [];
        foreach ($order->getAllItems() as $orderItem) {
            if (array_key_exists($orderItem->getId(), $items)) {
                $shipmentItem[] = $this->orderConvert
                    ->itemToShipmentItem($orderItem)
                    ->setQty($items[$orderItem->getId()]);
            }
        }

        return $shipmentItem;
    }
}



