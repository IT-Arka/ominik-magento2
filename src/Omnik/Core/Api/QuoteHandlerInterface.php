<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

use Magento\Sales\Model\Order;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Interface QuoteHandlerInterface
 * @api
 */
interface QuoteHandlerInterface
{
    /**
     * @param Quote $quote
     * @return array
     */
    public function normalizeQuotes(Quote $quote): array;

    /**
     * @param Quote $quote
     * @return array
     */
    public function collectAddressesData(Quote $quote): array;

    /**
     * @param Quote $quote
     * @param Quote $split
     * @return QuoteHandlerInterface
     */
    public function setCustomerData(Quote $quote, Quote $split): QuoteHandlerInterface;

    /**
     * @param array $quotes
     * @param Quote $split
     * @param array $items
     * @param array $addresses
     * @param PaymentInterface|null $payment
     * @return QuoteHandlerInterface
     */
    public function populateQuote(array $quotes, Quote $split, array $items, array $addresses, PaymentInterface $payment = null): QuoteHandlerInterface;

    /**
     * @param array $quotes
     * @param array $items
     * @param Quote $quote
     * @param array $addresses
     * @return QuoteHandlerInterface
     */
    public function recollectTotal(array $quotes, array $items, Quote $quote, array $addresses): QuoteHandlerInterface;

    /**
     * @param array $quotes
     * @param Quote $quote
     * @param Item $item
     * @param float $total
     * @return float
     */
    public function shippingAmount(array $quotes, Quote $quote, Item $item, float $total = 0.0): float;

    /**
     * @param Quote $split
     * @param PaymentInterface $payment
     * @param string $paymentMethod
     * @return QuoteHandlerInterface
     */
    public function setPaymentMethod(Quote $split, PaymentInterface $payment, string $paymentMethod): QuoteHandlerInterface;

    /**
     * @param Quote $split
     * @param Order $order
     * @param array $orderIds
     * @return QuoteHandlerInterface
     */
    public function defineSessions(Quote $split, Order $order, array $orderIds): QuoteHandlerInterface;
}
