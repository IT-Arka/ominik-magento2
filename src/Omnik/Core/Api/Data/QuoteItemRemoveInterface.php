<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;

interface QuoteItemRemoveInterface
{
    /**
     * Remove an item from the quote
     *
     * @param int $cartId
     * @param int $itemId
     * @return void
     */
    public function removeItemFromQuote($cartId, $itemId): void;
}
