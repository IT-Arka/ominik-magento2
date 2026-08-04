<?php

declare(strict_types=1);

namespace Omnik\Core\Cron\Orders;

use Omnik\Core\Model\Order\StatusQueue\BackfillQueue;

/**
 * Cron da varredura de recuperação da fila outbound de status.
 *
 * Rede de segurança para os filhos que não foram enfileirados pelo caminho orientado a
 * evento (observer de mudança de status). Roda com folga em relação ao
 * `omnik_process_order_status_queue`: aqui o objetivo é convergir, não ser imediato.
 */
class CronBackfillOrderStatusQueue
{
    /**
     * @param BackfillQueue $backfillQueue
     */
    public function __construct(
        private readonly BackfillQueue $backfillQueue
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->backfillQueue->execute();
    }
}
