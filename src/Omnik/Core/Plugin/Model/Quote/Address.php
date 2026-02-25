<?php

declare(strict_types=1);

namespace Omnik\Core\Plugin\Model\Quote;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\SessionFactory as CheckoutSessionFactory;
use Magento\Store\Model\ScopeInterface;
use Omnik\Core\Helper\Cart;

class Address
{

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var CheckoutSessionFactory
     */
    private CheckoutSessionFactory $checkoutSessionFactory;

    /**
     * @var Cart
     */
    private Cart $cart;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CheckoutSessionFactory $checkoutSessionFactory
     * @param Cart $cart
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CheckoutSessionFactory $checkoutSessionFactory,
        Cart $cart
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSessionFactory = $checkoutSessionFactory;
        $this->cart = $cart;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param $result
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterValidateMinimumAmount(
        \Magento\Quote\Model\Quote\Address $subject,
        $result
    ) {

        $validators = [];
        $checkoutSession = $this->checkoutSessionFactory->create();
        $storeId = $checkoutSession->getQuote()->getStoreId();

        $amount = $this->scopeConfig->getValue(
            'sales/minimum_order/amount',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $items = $this->cart->getItems($checkoutSession->getQuote()->getItems());

        foreach ($items as $item) {
            foreach ($item as $vendor) {
                $prices = 0;
                foreach ($vendor as $value) {
                    $prices += $value->getRowTotal();
                }
                $validators[] = ($prices >= $amount) ? false : true;
            }
        }

        return !in_array(true, $validators);
    }
}
