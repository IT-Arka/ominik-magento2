<?php

namespace Omnik\Core\Model\Integration\Sales;

use Omnik\Core\Model\Integration\Order\SendStatus;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Params;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Approvation
{
    /**
     * @var Params
     */
    private Params $params;

    /**
     * @var SendStatus
     */
    private SendStatus $sendStatus;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepositoryInterface;

    /**
     * @var Logger
     */
    private Logger $salesLogger;

    /**
     * @param Params $params
     * @param SendStatus $sendStatus
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Logger $salesLogger
     */
    public function __construct(
        Params                     $params,
        SendStatus                 $sendStatus,
        ProductRepositoryInterface $productRepositoryInterface,
        Logger                     $salesLogger
    ) {
        $this->params = $params;
        $this->sendStatus = $sendStatus;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->salesLogger = $salesLogger;
    }

    /**
     * @param $order
     * @return void
     */
    public function integrate($order)
    {
        try {
            $storeId = (int)$order->getStoreId();
            $tenant = $this->getTenant($order);
            $params = $this->params->createParametersForUpdate($order);

            if ($this->isApproved($order)) {
                $this->sendStatus->execute($params, $tenant, $storeId, $order->getIncrementId(), true);
            }

            if ($this->isNotApproved($order)) {
                $this->sendStatus->execute($params, $tenant, $storeId, $order->getIncrementId(), false);
            }
        } catch (\Exception $e) {
            $this->salesLogger->error($e->getMessage());
        }
    }

    /**
     * @param $order
     * @return bool
     */
    public function isApproved($order)
    {
        foreach ($order->getStatusHistories() as $statusHistory) {
            if ($statusHistory->getEntityName() == 'order' && $statusHistory->getStatus() == 'canceled') {
                return false;
            }
        }

        return $order->getStatus() === 'processing';
    }

    /**
     * @param $order
     * @return bool
     */
    public function isNotApproved($order)
    {
        return $order->getStatus() == 'canceled';
    }

    /**
     * @param $order
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getTenant($order)
    {
        $item = current($order->getItems())->getData();
        $product = $this->productRepositoryInterface->get($item['sku']);
        return $product->getCustomAttribute('tenant')->getValue();
    }
}
