<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Shipping;

/**
 * Centraliza a regra de leitura do prazo de entrega de uma delivery option
 * retornada pela Omnik, mantendo a regra única entre os pontos que exibem
 * ou persistem esse prazo (carrier, controller e observer).
 */
class DeliveryEstimate
{
    /**
     * Campo com o prazo (em dias) na resposta da Omnik.
     */
    public const FIELD_DAYS = 'deliveryEstimateBusinessDays';

    /**
     * Campo com o tipo de prazo: `bd` = dias úteis, `d` = dias.
     */
    public const FIELD_TYPE = 'deliveryTimeType';

    /**
     * Resolve o número de dias do prazo de entrega de uma delivery option.
     *
     * @param array $deliveryOption
     * @return int|null Número de dias, ou null quando indisponível.
     */
    public static function resolveDays(array $deliveryOption): ?int
    {
        $value = $deliveryOption[self::FIELD_DAYS] ?? null;
        if ($value !== null && $value !== '') {
            return (int)$value;
        }

        return null;
    }

    /**
     * Indica se o prazo é contado em dias úteis (default) ou dias corridos.
     *
     * @param array $deliveryOption
     * @return bool
     */
    public static function isBusinessDays(array $deliveryOption): bool
    {
        return ($deliveryOption[self::FIELD_TYPE] ?? 'bd') !== 'd';
    }
}
