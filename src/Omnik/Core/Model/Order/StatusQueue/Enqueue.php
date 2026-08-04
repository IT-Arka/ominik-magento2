<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Order\StatusQueue;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Omnik\Core\Logger\Logger;

/**
 * Enfileira o envio outbound de status de pedido (approved/not-approved) para a Omnik.
 *
 * É idempotente por order_id: um pedido tem no máximo uma linha na fila. Reenfileirar
 * atualiza is_approved e devolve o registro para PENDING (zerando tentativas), de modo
 * que uma mudança de status posterior sobrescreva um envio anterior ainda não concluído.
 */
class Enqueue
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param Logger $logger
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param OrderInterface $order
     * @param bool $isApproved
     * @param bool $preserveAttempts Mantém o contador de tentativas de um registro já
     *        existente em vez de zerá-lo. Usado pela varredura de recuperação
     *        (BackfillQueue), onde zerar faria um erro permanente ser re-tentado para
     *        sempre. O caminho normal (mudança real de status) zera, porque ali o
     *        reenfileiramento representa um fato novo do pedido.
     * @return void
     */
    public function execute(OrderInterface $order, bool $isApproved, bool $preserveAttempts = false): void
    {
        $orderId = (int)$order->getEntityId();
        $incrementId = (string)$order->getIncrementId();

        if ($orderId <= 0 || $incrementId === '') {
            $this->logger->error('Omnik OrderStatusQueue: pedido sem entity_id/increment_id, não enfileirado.');
            return;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName(OrderStatusQueue::TABLE);

            $connection->insertOnDuplicate(
                $table,
                [
                    'order_id' => $orderId,
                    'increment_id' => $incrementId,
                    'store_id' => (int)$order->getStoreId(),
                    'is_approved' => $isApproved ? OrderStatusQueue::IS_APPROVED : OrderStatusQueue::IS_NOT_APPROVED,
                    'status' => OrderStatusQueue::STATUS_PENDING,
                    'attempts' => 0,
                    'error_body' => null,
                    'worker_token' => null,
                ],
                // Colunas atualizadas quando o pedido já está na fila.
                $preserveAttempts
                    ? ['increment_id', 'store_id', 'is_approved', 'status', 'error_body', 'worker_token']
                    : ['increment_id', 'store_id', 'is_approved', 'status', 'attempts', 'error_body', 'worker_token']
            );

            $this->logger->info(sprintf(
                'Omnik OrderStatusQueue: pedido %s enfileirado (is_approved=%d).',
                $incrementId,
                $isApproved ? 1 : 0
            ));
        } catch (\Throwable $e) {
            // Nunca engolir: enfileiramento falho deixa o status fora de sincronia com a Omnik.
            $this->logger->error(sprintf(
                'Omnik OrderStatusQueue: falha ao enfileirar pedido %s - %s',
                $incrementId,
                $e->getMessage()
            ));
        }
    }
}
