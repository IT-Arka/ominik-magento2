<?php

namespace Omnik\Core\Model\Integration\Sales;

use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Helper\StatusMapping as StatusMappingHelper;
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
     * @var StatusMappingHelper
     */
    private StatusMappingHelper $_statusMappingHelper;

    /**
     * @var ConfigHelper
     */
    private ConfigHelper $_configHelper;

    /**
     * @param Params $params
     * @param SendStatus $sendStatus
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Logger $salesLogger
     * @param StatusMappingHelper $statusMappingHelper
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Params                     $params,
        SendStatus                 $sendStatus,
        ProductRepositoryInterface $productRepositoryInterface,
        Logger                     $salesLogger,
        StatusMappingHelper        $statusMappingHelper,
        ConfigHelper               $configHelper
    ) {
        $this->params = $params;
        $this->sendStatus = $sendStatus;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->salesLogger = $salesLogger;
        $this->_statusMappingHelper = $statusMappingHelper;
        $this->_configHelper = $configHelper;
    }

    /**
     * @param $order
     * @return void
     */
    public function integrate($order)
    {
        try {
            $storeId = (int)$order->getStoreId();
            $tenant  = $this->getTenant($order);
            $params  = $this->params->createParametersForUpdate($order);

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
    public function isApproved($order): bool
    {
        if ($this->_statusMappingHelper->isMapEnabled()) {
            $omnikStatus = $this->_statusMappingHelper->getOmnikStatusByAdobeStatus($order->getStatus());
            if ($omnikStatus === 'APPROVED') {
                return true;
            }
            if (in_array($omnikStatus, ['CANCELED', 'NOT_APPROVED'])) {
                return false;
            }
        }

        // Fallback: comportamento original quando mapeamento não está ativo
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
    public function isNotApproved($order): bool
    {
        if ($this->_statusMappingHelper->isMapEnabled()) {
            $omnikStatus = $this->_statusMappingHelper->getOmnikStatusByAdobeStatus($order->getStatus());
            return in_array($omnikStatus, ['CANCELED', 'NOT_APPROVED']);
        }

        return $order->getStatus() === 'canceled';
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
        $itemData  = current($order->getItems())->getData();
        $product   = $this->productRepositoryInterface->get($itemData['sku']);
        $tenantVal = $product->getCustomAttribute($attrCode)?->getValue();
        if (empty($tenantVal)) {
            throw new \RuntimeException(
                sprintf('Produto "%s" sem atributo "%s" (Tenant) preenchido.', $itemData['sku'], $attrCode)
            );
        }
        return $tenantVal;
    }
}
