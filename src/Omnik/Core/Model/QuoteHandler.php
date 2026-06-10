<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Omnik\Core\Api\QuoteHandlerInterface;
use Omnik\Core\Model\ShippingAmount;
use Omnik\Core\Model\Config\Configurable\ProductsOptions;

class QuoteHandler implements QuoteHandlerInterface
{
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var ProductsOptions
     */
    private ProductsOptions $productsOptions;

    /**
     * @var ShippingAmount
     */
    private ShippingAmount $shippingAmount;

    /**
     * @param CheckoutSession $checkoutSession
     * @param ProductsOptions $productsOptions
     * @param ShippingAmount $shippingAmount
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ProductsOptions $productsOptions,
        ShippingAmount  $shippingAmount
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->productsOptions = $productsOptions;
        $this->shippingAmount = $shippingAmount;
    }

    /**
     * @param Quote $quote
     * @return array
     * @throws NoSuchEntityException
     */
    public function normalizeQuotes(Quote $quote): array
    {
        $items = $quote->getAllItems();
        return $this->productsOptions->separeItemsByVendor($items);
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function collectAddressesData(Quote $quote): array
    {
        $billing = $quote->getBillingAddress()->getData();
        unset($billing['id']);
        unset($billing['quote_id']);

        $shipping = $quote->getShippingAddress()->getData();
        unset($shipping['id']);
        unset($shipping['quote_id']);

        return [
            'payment' => $quote->getPayment()->getMethod(),
            'billing' => $billing,
            'shipping' => $shipping
        ];
    }

    /**
     * @inheritdoc
     */
    public function setCustomerData(Quote $quote, Quote $split): QuoteHandlerInterface
    {
        $split->setStoreId($quote->getStoreId());
        $split->setCustomer($quote->getCustomer());
        $split->setCustomerIsGuest($quote->getCustomerIsGuest());

        if ($quote->getCheckoutMethod() === CartManagementInterface::METHOD_GUEST) {
            $split->setCustomerId(0);
            $split->setCustomerEmail($quote->getBillingAddress()->getEmail());
            $split->setCustomerIsGuest(true);
            $split->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }
        return $this;
    }

    /**
     * @param array $quotes
     * @param Quote $split
     * @param array $items
     * @param array $addresses
     * @param PaymentInterface|null $payment
     * @return QuoteHandlerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function populateQuote(array $quotes, Quote $split, array $items, array $addresses, PaymentInterface $payment = null): QuoteHandlerInterface
    {
        $this->recollectTotal($quotes, $items, $split, $addresses);
        $this->setPaymentMethod($split, $payment, $addresses['payment']);

        return $this;
    }

    /**
     * @param array $quotes
     * @param array $items
     * @param Quote $quote
     * @param array $addresses
     * @return QuoteHandlerInterface
     */
    public function recollectTotal(array $quotes, array $items, Quote $quote, array $addresses): QuoteHandlerInterface
    {
        $tax = 0.0;
        $discount = 0.0;
        $finalPrice = 0.0;

        foreach ($items as $item) {
            $tax += $item->getData('tax_amount');
            $discount += $item->getData('discount_amount');

            $finalPrice += ($item->getPrice() * $item->getQty());
        }

        $quote->getBillingAddress()->setData($addresses['billing']);
        $quote->getShippingAddress()->setData($addresses['shipping']);

        $shipping = $this->shippingAmount($quotes, $quote, current($items));

        foreach ($quote->getAllAddresses() as $address) {
            $grandTotal = (($finalPrice + $shipping + $tax) - $discount);

            $address->setBaseSubtotal($finalPrice);
            $address->setSubtotal($finalPrice);
            $address->setDiscountAmount($discount);
            $address->setTaxAmount($tax);
            $address->setBaseTaxAmount($tax);
            $address->setBaseGrandTotal($grandTotal);
            $address->setGrandTotal($grandTotal);
        }
        return $this;
    }

    /**
     * @param array $quotes
     * @param Quote $quote
     * @param Item $item
     * @param float $total
     * @return float
     * @throws NoSuchEntityException
     */
    public function shippingAmount(array $quotes, Quote $quote, Item $item, float $total = 0.0): float
    {
        if ($quote->hasVirtualItems() === true) {
            return $total;
        }

        $shippingAmount = $this->shippingAmount->calculateShippingAmount($quotes, $quote, $item, $total);

        $quote->getShippingAddress()->setShippingAmount($shippingAmount);
        $quote->getShippingAddress()->setBaseShippingAmount($shippingAmount);

        return $shippingAmount;
    }

    /**
     * @param Quote $split
     * @param PaymentInterface|null $payment
     * @param string $paymentMethod
     * @return QuoteHandlerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setPaymentMethod(Quote $split, PaymentInterface $payment = null, string $paymentMethod): QuoteHandlerInterface
    {
        $split->getPayment()->setMethod($paymentMethod);

        if (!is_null($payment)) {
            $split->getPayment()->setQuote($split);
            $data = $payment->getData();
            $split->getPayment()->importData($data);
        }
        return $this;
    }

    /**
     * @param Quote $split
     * @param Order $order
     * @param array $orderIds
     * @return QuoteHandlerInterface
     */
    public function defineSessions(Quote $split, Order $order, array $orderIds): QuoteHandlerInterface
    {
        $this->checkoutSession->setLastQuoteId($split->getId());
        $this->checkoutSession->setLastSuccessQuoteId($split->getId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
        $this->checkoutSession->setOrderIds($orderIds);

        return $this;
    }
}
