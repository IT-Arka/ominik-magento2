<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Order\StatusQueue;

/**
 * Constantes compartilhadas da fila outbound de status de pedido (Magento -> Omnik).
 *
 * A fila resolve a corrida entre o POST de "pedido novo" e o PUT de "aprovado":
 * o PUT approved só é enviado depois que o POST confirmar a integração
 * (sales_order.has_integrated_omnik = 1).
 */
interface OrderStatusQueue
{
    public const TABLE = 'omnik_order_status_queue';

    public const STATUS_PENDING = 0;
    public const STATUS_SENT = 1;
    public const STATUS_ERROR = 2;

    public const IS_APPROVED = 1;
    public const IS_NOT_APPROVED = 0;
}
