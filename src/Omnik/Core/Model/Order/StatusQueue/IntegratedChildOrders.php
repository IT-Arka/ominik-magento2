<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Order\StatusQueue;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Omnik\Core\Api\SplitOrderInterface;

/**
 * Busca os pedidos filhos de um pedido split que já foram integrados na Omnik.
 *
 * Num pedido splitado, quem recebe o POST de pedido novo (e portanto existe na Omnik)
 * são os filhos, não o pai. A confirmação de pagamento precisa ser enviada para cada
 * filho integrado, usando o increment_id dele como marketplaceId.
 */
class IntegratedChildOrders
{
    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * Retorna os filhos com has_integrated_omnik = 1.
     *
     * Filhos ainda não integrados são omitidos de propósito: enfileirá-los faria o gate
     * do ProcessQueue esperar por uma confirmação que ainda não existe. Quando o POST do
     * filho confirmar, uma nova mudança de status volta a passar por aqui.
     *
     * @param int $parentOrderId
     * @return OrderInterface[]
     */
    public function getIntegratedChildren(int $parentOrderId): array
    {
        if ($parentOrderId <= 0) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SplitOrderInterface::SPLIT_ORDER_PARENT_ID, $parentOrderId)
            ->addFilter(SplitOrderInterface::SPLIT_ORDER_TYPE, SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD)
            ->addFilter(SplitOrderInterface::SPLIT_ORDER_HAS_INTEGRATED, 1)
            ->create();

        return $this->orderRepository->getList($searchCriteria)->getItems();
    }
}
