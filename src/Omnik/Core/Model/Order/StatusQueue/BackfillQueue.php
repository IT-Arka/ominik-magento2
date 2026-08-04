<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Order\StatusQueue;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Sales\Approvation;

/**
 * Rede de segurança da fila outbound de status: recupera pedidos filhos que ficaram
 * sem o envio de status para a Omnik.
 *
 * O caminho normal é orientado a evento: `sales_order_save_after` -> ChangeChildStatus ->
 * HandleChildOrders -> Approvation::integrate(). Esse caminho só dispara quando o observer
 * flagra a transição de status do pai (`getOrigData('status') != getData('status')`), e
 * processa os filhos em sequência dentro do mesmo request. Um filho que ainda não tinha
 * `has_integrated_omnik = 1` no instante em que o laço passou por ele — ou cujo pai foi
 * salvo sem a transição ser detectada naquele request — perde a única janela de
 * enfileiramento e fica em `processing` para sempre, aparecendo como "Novo" na Omnik.
 *
 * Esta varredura torna o fluxo convergente: em vez de depender de flagrar o evento, ela
 * compara periodicamente o estado desejado (pai aprovado + filho integrado) com o estado
 * da fila, e enfileira o que estiver faltando. É idempotente — `Enqueue` usa
 * `insertOnDuplicate` sobre a UNIQUE de `order_id`, e registros já enviados (SENT) ou em
 * andamento (PENDING) são ignorados pela própria consulta.
 */
class BackfillQueue
{
    /**
     * Máximo de pedidos pai inspecionados por passada, para não estourar o tempo do cron.
     */
    private const BATCH_SIZE = 100;

    /**
     * Janela de varredura. Pedidos mais antigos que isso não são recuperados
     * automaticamente: a essa altura o caso é operacional (ver RUNBOOK), e varrer o
     * histórico inteiro a cada passada seria caro sem ganho real.
     */
    private const LOOKBACK_DAYS = 7;

    /**
     * Teto de recuperações de um registro em ERROR.
     *
     * `Enqueue` zera `attempts` ao reenfileirar, então sem este limite um erro permanente
     * (payload inválido, pedido inexistente na Omnik) seria re-tentado indefinidamente:
     * a varredura devolveria para PENDING, o ProcessQueue falharia de novo, e assim por
     * diante. Acima deste teto o registro fica em ERROR para tratamento manual.
     */
    private const MAX_BACKFILL_ATTEMPTS = 60;

    /**
     * @param ResourceConnection $resourceConnection
     * @param OrderRepositoryInterface $orderRepository
     * @param Approvation $approvation
     * @param Enqueue $enqueue
     * @param Logger $logger
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Approvation $approvation,
        private readonly Enqueue $enqueue,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $candidates = $this->findPendingChildren();

        if (empty($candidates)) {
            return;
        }

        $enqueuedCount = 0;

        foreach ($candidates as $row) {
            // Isola cada filho: a falha de um não pode impedir a recuperação dos demais.
            try {
                if ($this->enqueueChild((int)$row['parent_id'], (int)$row['child_id'])) {
                    $enqueuedCount++;
                }
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    'Omnik BackfillQueue: falha ao recuperar o pedido filho %s - %s',
                    (string)$row['child_increment_id'],
                    $e->getMessage()
                ));
            }
        }

        if ($enqueuedCount > 0) {
            $this->logger->info(sprintf(
                'Omnik BackfillQueue: %d pedido(s) filho(s) recuperado(s) e enfileirado(s).',
                $enqueuedCount
            ));
        }
    }

    /**
     * Enfileira um filho, reconfirmando pelo pai se o status realmente deve ser enviado.
     *
     * A decisão de aprovado/cancelado é delegada a Approvation para respeitar o
     * StatusMapping configurado no admin, em vez de duplicar a regra aqui.
     *
     * @param int $parentId
     * @param int $childId
     * @return bool Se o filho foi enfileirado
     */
    private function enqueueChild(int $parentId, int $childId): bool
    {
        $parent = $this->orderRepository->get($parentId);

        $isApproved = $this->approvation->isApproved($parent);
        $isNotApproved = $this->approvation->isNotApproved($parent);

        if (!$isApproved && !$isNotApproved) {
            return false;
        }

        // preserveAttempts: a recuperação não pode zerar o contador, senão um erro
        // permanente nunca atinge o teto e é re-tentado indefinidamente.
        $this->enqueue->execute($this->orderRepository->get($childId), $isApproved, true);

        return true;
    }

    /**
     * Filhos integrados na Omnik, de pais já finalizados, que não têm envio de status
     * concluído nem pendente na fila.
     *
     * Só entram filhos com `has_integrated_omnik = 1` (existem na Omnik para receber o
     * PUT) cujo pai NÃO está mais em estado inicial. Linhas já SENT ou PENDING são
     * descartadas: as primeiras já cumpriram o objetivo, as segundas serão tratadas
     * pelo ProcessQueue na próxima passada.
     *
     * @return array<int, array<string, mixed>>
     */
    private function findPendingChildren(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');
        $queueTable = $this->resourceConnection->getTableName(OrderStatusQueue::TABLE);

        $select = $connection->select()
            ->from(['child' => $orderTable], [
                'child_id' => 'child.entity_id',
                'child_increment_id' => 'child.increment_id',
                'parent_id' => 'parent.entity_id',
            ])
            ->join(
                ['parent' => $orderTable],
                'parent.entity_id = child.' . SplitOrderInterface::SPLIT_ORDER_PARENT_ID,
                []
            )
            ->joinLeft(
                ['q' => $queueTable],
                'q.order_id = child.entity_id',
                []
            )
            ->where('child.' . SplitOrderInterface::SPLIT_ORDER_TYPE . ' = ?', SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD)
            ->where('child.' . SplitOrderInterface::SPLIT_ORDER_HAS_INTEGRATED . ' = ?', 1)
            ->where('parent.status NOT IN (?)', ['pending', 'pending_payment', 'new'])
            ->where('child.created_at >= ?', $this->getLookbackDate())
            // Sem linha na fila (nunca enfileirado) ou linha em ERROR que ainda não
            // esgotou o teto de recuperação. SENT e PENDING ficam de fora.
            ->where(
                'q.entity_id IS NULL OR (q.status = ' . OrderStatusQueue::STATUS_ERROR
                . ' AND q.attempts < ?)',
                self::MAX_BACKFILL_ATTEMPTS
            )
            ->order('child.entity_id ASC')
            ->limit(self::BATCH_SIZE);

        return $connection->fetchAll($select);
    }

    /**
     * @return string
     */
    private function getLookbackDate(): string
    {
        return (new \DateTimeImmutable('-' . self::LOOKBACK_DAYS . ' days'))->format('Y-m-d H:i:s');
    }
}
