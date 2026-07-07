<?php

declare(strict_types=1);

namespace Omnik\Core\Cron\Orders;

use Omnik\Core\Model\Order\StatusQueue\ProcessQueue;

/**
 * Cron da fila outbound de status de pedido (Magento -> Omnik).
 *
 * Envia o PUT approved/not-approved somente após o POST de pedido novo confirmar a
 * integração na Omnik, resolvendo a corrida entre POST e PUT quando o gateway aprova
 * o pagamento junto com a criação do pedido.
 */
class CronProcessOrderStatusQueue
{
    /**
     * @param ProcessQueue $processQueue
     */
    public function __construct(
        private readonly ProcessQueue $processQueue
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->processQueue->execute();
    }
}
