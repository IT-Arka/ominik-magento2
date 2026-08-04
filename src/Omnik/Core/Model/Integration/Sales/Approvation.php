<?php

namespace Omnik\Core\Model\Integration\Sales;

use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Helper\StatusMapping as StatusMappingHelper;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Params;
use Omnik\Core\Model\Order\StatusQueue\Enqueue;
use Omnik\Core\Model\Order\StatusQueue\IntegratedChildOrders;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Approvation
{
    /**
     * @var Params
     */
    private Params $params;

    /**
     * @var Enqueue
     */
    private Enqueue $enqueue;

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
     * @param Enqueue $enqueue
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Logger $salesLogger
     * @param StatusMappingHelper $statusMappingHelper
     * @param ConfigHelper $configHelper
     * @param IntegratedChildOrders $childOrders
     */
    public function __construct(
        Params                     $params,
        Enqueue                    $enqueue,
        ProductRepositoryInterface $productRepositoryInterface,
        Logger                     $salesLogger,
        StatusMappingHelper        $statusMappingHelper,
        ConfigHelper               $configHelper,
        private readonly IntegratedChildOrders $childOrders
    ) {
        $this->params = $params;
        $this->enqueue = $enqueue;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->salesLogger = $salesLogger;
        $this->_statusMappingHelper = $statusMappingHelper;
        $this->_configHelper = $configHelper;
    }

    /**
     * Enfileira o envio outbound do status (approved/not-approved) em vez de enviar de forma
     * síncrona. Isso resolve a corrida com o POST de pedido novo: o cron da fila só envia o PUT
     * depois que a integração do pedido é confirmada na Omnik (has_integrated_omnik = 1).
     *
     * Quem é enfileirado depende do pedido ter sido splitado, porque quem existe na Omnik
     * é sempre quem recebeu o POST de pedido novo:
     *  - Pedido split (pai): quem foi integrado são os FILHOS (um por seller, cada um com seu
     *    increment_id como marketplaceId). O pai nunca é enviado à Omnik e nunca recebe
     *    has_integrated_omnik=1, então enfileirá-lo trava a fila no gate para sempre.
     *    Aprovado o pagamento no pai, o status de pago é enfileirado para cada filho.
     *  - Pedido único (sem split): o próprio pedido foi integrado; enfileira ele mesmo.
     *
     * @param $order
     * @return void
     */
    public function integrate($order)
    {
        try {
            if ($this->isApproved($order)) {
                $this->enqueueTarget($order, true);
                return;
            }

            if ($this->isNotApproved($order)) {
                $this->enqueueTarget($order, false);
            }
        } catch (\Exception $e) {
            $this->salesLogger->error($e->getMessage());
        }
    }

    /**
     * Enfileira o pedido correto conforme o cenário (split x pedido único).
     *
     * @param $order
     * @param bool $isApproved
     * @return void
     */
    private function enqueueTarget($order, bool $isApproved): void
    {
        if ((string)$order->getData(SplitOrderInterface::SPLIT_ORDER_TYPE)
            !== SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT
        ) {
            // Cenário sem split (ou o próprio filho já sendo tratado): fluxo normal.
            $this->enqueue->execute($order, $isApproved);
            return;
        }

        $children = $this->childOrders->getIntegratedChildren((int)$order->getEntityId());

        if (empty($children)) {
            // Sem filho integrado não há o que confirmar na Omnik. Não enfileira o pai:
            // ele não existe lá e só travaria no gate até estourar as tentativas.
            $this->salesLogger->error(sprintf(
                'Omnik Approvation: pedido split %s aprovado, mas nenhum pedido filho '
                . 'integrado na Omnik foi encontrado; status de pagamento não enfileirado.',
                (string)$order->getIncrementId()
            ));
            return;
        }

        foreach ($children as $child) {
            $this->enqueue->execute($child, $isApproved);
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
