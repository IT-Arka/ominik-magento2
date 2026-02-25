<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Orders;

use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\OrderFactory;
use Omnik\Core\Helper\Shipment;
use Omnik\Core\Model\Integration\Order\GetOrder;

class Ship implements NotifyHandlerInterface
{
    /**
     * @param ShipOrderInterface $shipOrderInterface
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param OrderFactory $order
     * @param Shipment $shipment
     * @param GetOrder $getOrderOmnik
     */
    public function __construct(
        private readonly ShipOrderInterface       $shipOrderInterface,
        private readonly NotifyOmnikDataInterface $notifyOmnikDataInterface,
        private readonly OrderFactory             $order,
        private readonly Shipment                 $shipment,
        private readonly GetOrder                 $getOrderOmnik
    ) {

    }

    /**
     * @param array $registers
     * @return void
     * @throws \Exception
     */
    public function execute(array $registers): void
    {
        $qtyRegisters = 0;

        foreach ($registers as $data) {
            if ($data['resource_type'] == self::RESOURCE_ORDER) {
                if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                    break;
                }

                if ($data['event'] == self::SHIPPING_LABEL) {
                    $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id'], 1, "Geração de Etiqueta");
                }

                if ($data['event'] !== self::SHIP) {
                    continue;
                }

                $orderId = $data['resource_market_place_id'];
                $order = $this->order->create()->loadByIncrementId($orderId);
                if (empty($order->getId())) {
                    throw new \Exception('ID de pedido não encontrado: ' . $orderId);
                }

                if (!$order->canShip()) {
                    $this->notifyOmnikDataInterface->changeStatusNotify((int)$data['entity_id'], 0, "Order can't ship");
                    continue;
                }

                $skipShip = false;
                foreach ($order->getAllVisibleItems() as $item) {
                    if (!$item->getQtyToShip() || $item->getIsVirtual()) {
                        $skipShip = $item->getSku();
                        break;
                    }
                }

                if ($skipShip) {
                    throw new \Exception('Item: ' . $skipShip . " is OutOfStock.");
                }

                try {
                    $deliveryInfo = $this->getDeliveryInfo($data, $order);
                    $trackData = $this->shipment->getTrackingData(
                        $deliveryInfo['trackingCode'], $deliveryInfo['methodName']
                    );

                    $this->shipOrderInterface->execute(
                        $order->getId(),
                        [],
                        false,
                        false,
                        null,
                        $trackData,
                        [],
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
     * @param $order
     * @return array
     * @throws \Exception
     */
    public function getDeliveryInfo($data, $order)
    {
        $orderOmnik = $this->getOrderOmnik->execute($data['seller'], (int)$data['store_id'],
            $order->getIncrementId());

        $trackingCode = '000000000';
        $trackingUrl = 'URL not found';
        $methodName = 'Bartofil';
        if (isset($orderOmnik['deliveryData']) && !empty($orderOmnik['deliveryData'])) {
            $trackingCode = $orderOmnik['deliveryData']['trackingCode'];
            $trackingUrl = $orderOmnik['deliveryData']['trackingURL'];
            $methodName = $orderOmnik['deliveryData']['description'];
        }

        return [
            'trackingCode' => $trackingCode,
            'trackingUrl' => $trackingUrl,
            'methodName' => $methodName
        ];
    }
}
