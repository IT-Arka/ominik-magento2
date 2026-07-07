<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Order\StatusQueue;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Math\Random;
use Magento\Sales\Api\OrderRepositoryInterface;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Order\SendStatus;
use Omnik\Core\Model\Integration\Params;

/**
 * Processa a fila outbound de status de pedido (Magento -> Omnik).
 *
 * Fluxo, por registro pendente:
 *  1. Claim atômico via worker_token (evita processamento duplicado entre workers de cron).
 *  2. Gate de corrida: só envia o PUT approved/not-approved se o pedido já foi confirmado
 *     na Omnik (sales_order.has_integrated_omnik = 1, setado pelo POST de pedido novo).
 *     Enquanto não confirmar, o registro é devolvido para PENDING e re-tentado na próxima passada.
 *  3. Ao atingir o teto de tentativas sem confirmação/sucesso, marca ERROR e para de tentar.
 */
class ProcessQueue
{
    /**
     * Teto de tentativas antes de marcar ERROR. Com o cron a cada 1min, ~20 tentativas ≈ 20min
     * de janela para o POST do pedido novo confirmar na Omnik.
     */
    private const MAX_ATTEMPTS = 20;

    /**
     * Máximo de registros processados por passada, para não estourar o tempo do cron.
     */
    private const BATCH_SIZE = 50;

    /**
     * @param ResourceConnection $resourceConnection
     * @param OrderRepositoryInterface $orderRepository
     * @param Params $params
     * @param SendStatus $sendStatus
     * @param Random $random
     * @param Logger $logger
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Params $params,
        private readonly SendStatus $sendStatus,
        private readonly Random $random,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $token = $this->random->getRandomString(32);
        $claimed = $this->claim($token);

        if ($claimed === 0) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(OrderStatusQueue::TABLE);

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($table)
                ->where('worker_token = ?', $token)
                ->where('status = ?', OrderStatusQueue::STATUS_PENDING)
        );

        foreach ($rows as $row) {
            $this->processRow($row);
        }
    }

    /**
     * Reivindica atomicamente um lote de pendentes marcando-os com o worker_token.
     *
     * Seleciona os ids pendentes/sem dono e faz o UPDATE condicionado a worker_token IS NULL,
     * de forma que dois workers concorrentes nunca reivindiquem o mesmo registro.
     *
     * @param string $token
     * @return int Quantidade reivindicada
     */
    private function claim(string $token): int
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(OrderStatusQueue::TABLE);

        $ids = $connection->fetchCol(
            $connection->select()
                ->from($table, ['entity_id'])
                ->where('status = ?', OrderStatusQueue::STATUS_PENDING)
                ->where('worker_token IS NULL')
                ->order('entity_id ASC')
                ->limit(self::BATCH_SIZE)
        );

        if (empty($ids)) {
            return 0;
        }

        return (int)$connection->update(
            $table,
            ['worker_token' => $token],
            [
                'worker_token IS NULL',
                'entity_id IN (?)' => array_map('intval', $ids),
            ]
        );
    }

    /**
     * @param array $row
     * @return void
     */
    private function processRow(array $row): void
    {
        $entityId = (int)$row['entity_id'];
        $orderId = (int)$row['order_id'];
        $incrementId = (string)$row['increment_id'];

        try {
            // Gate de corrida: o approved/not-approved só sai depois que o POST de pedido novo
            // for confirmado pela Omnik (has_integrated_omnik = 1). A flag é lida direto do
            // banco para refletir o estado persistido mais recente, sem depender de instância
            // eventualmente cacheada no registry do repositório.
            if (!$this->isIntegratedInOmnik($orderId)) {
                $this->requeueWaitingIntegration($entityId, $incrementId);
                return;
            }

            $order = $this->orderRepository->get($orderId);
            $tenant = $this->params->getOrderTenant($order);
            $payload = $this->params->createParametersForUpdate($order);
            $isApproved = (int)$row['is_approved'] === OrderStatusQueue::IS_APPROVED;

            $response = $this->sendStatus->execute(
                $payload,
                (string)$tenant,
                (int)$order->getStoreId(),
                $incrementId,
                $isApproved
            );

            if (is_array($response) && !empty($response['fails'])) {
                $this->fail($entityId, $incrementId, 'Omnik retornou fails:true - ' . $this->encode($response));
                return;
            }

            $this->markSent($entityId, $incrementId);
        } catch (\Throwable $e) {
            $this->fail($entityId, $incrementId, $e->getMessage());
        }
    }

    /**
     * Lê has_integrated_omnik direto da tabela sales_order (estado persistido).
     *
     * @param int $orderId
     * @return bool
     */
    private function isIntegratedInOmnik(int $orderId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('sales_order');

        $flag = $connection->fetchOne(
            $connection->select()
                ->from($table, [SplitOrderInterface::SPLIT_ORDER_HAS_INTEGRATED])
                ->where('entity_id = ?', $orderId)
        );

        return (bool)$flag;
    }

    /**
     * Pedido ainda não confirmado na Omnik: devolve para PENDING e conta a tentativa.
     * Ao atingir o teto, marca ERROR para não reprocessar indefinidamente.
     *
     * @param int $entityId
     * @param string $incrementId
     * @return void
     */
    private function requeueWaitingIntegration(int $entityId, string $incrementId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(OrderStatusQueue::TABLE);

        $attempts = (int)$connection->fetchOne(
            $connection->select()->from($table, ['attempts'])->where('entity_id = ?', $entityId)
        ) + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->fail(
                $entityId,
                $incrementId,
                'Pedido não confirmado na Omnik (has_integrated_omnik=0) após ' . self::MAX_ATTEMPTS . ' tentativas.'
            );
            return;
        }

        $connection->update(
            $table,
            [
                'status' => OrderStatusQueue::STATUS_PENDING,
                'attempts' => $attempts,
                'worker_token' => null,
            ],
            ['entity_id = ?' => $entityId]
        );
    }

    /**
     * @param int $entityId
     * @param string $incrementId
     * @return void
     */
    private function markSent(int $entityId, string $incrementId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(OrderStatusQueue::TABLE);

        $connection->update(
            $table,
            ['status' => OrderStatusQueue::STATUS_SENT, 'error_body' => null, 'worker_token' => null],
            ['entity_id = ?' => $entityId]
        );

        $this->logger->info('Omnik OrderStatusQueue: status do pedido ' . $incrementId . ' enviado à Omnik.');
    }

    /**
     * @param int $entityId
     * @param string $incrementId
     * @param string $error
     * @return void
     */
    private function fail(int $entityId, string $incrementId, string $error): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(OrderStatusQueue::TABLE);

        $attempts = (int)$connection->fetchOne(
            $connection->select()->from($table, ['attempts'])->where('entity_id = ?', $entityId)
        ) + 1;

        $connection->update(
            $table,
            [
                'status' => OrderStatusQueue::STATUS_ERROR,
                'attempts' => $attempts,
                'error_body' => $error,
                'worker_token' => null,
            ],
            ['entity_id = ?' => $entityId]
        );

        $this->logger->error(
            'Omnik OrderStatusQueue: falha ao enviar status do pedido ' . $incrementId . ' - ' . $error
        );
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function encode($value): string
    {
        return (string)json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
