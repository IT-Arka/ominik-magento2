<?php

declare(strict_types=1);

namespace Omnik\Core\Plugin\Model\Cart;

use Omnik\Core\Model\Cart\DiscountItems;
use Magento\Checkout\Model\Session as CheckoutSession;

class CartTotalRepository
{
    /**
     * @var DiscountItems
     */
    private DiscountItems $discountItems;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @param DiscountItems $discountItems
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        DiscountItems $discountItems,
        CheckoutSession $checkoutSession
    ) {
        $this->discountItems = $discountItems;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Quote\Model\Cart\CartTotalRepository $subject
     * @param $result
     * @param $cartId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGet(
        \Magento\Quote\Model\Cart\CartTotalRepository $subject,
        $result,
        $cartId
    ) {
        $hasDiscount = false;
        $grandTotal = 0.0;
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getItems();

        foreach ($items as $item) {
            if (!empty($item->getDiscountAmount())) {
                $hasDiscount = true;
                $price = $this->discountItems->getDiscountPrice($item);
                $grandTotal += $price * $item->getQty();
            } else {
                $grandTotal += $item->getPrice() * $item->getQty();
            }
        }

        if ($hasDiscount) {
            $result->getTotalSegments()['grand_total']->setValue($grandTotal);
        }

        return $result;
    }
}
