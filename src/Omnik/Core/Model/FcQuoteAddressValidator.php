<?php

namespace Omnik\Core\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteAddressValidator;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;

class FcQuoteAddressValidator extends QuoteAddressValidator
{
    /**
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession
     */
    public function __construct(
        AddressRepositoryInterface  $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        Session                     $customerSession
    ) {
        parent::__construct($addressRepository, $customerRepository, $customerSession);
    }

    /**
     * @param CartInterface $cart
     * @param AddressInterface $address
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validateForCart(CartInterface $cart, AddressInterface $address): void
    {
        if ($cart->getCustomerIsGuest()) {
            if (!empty($cart->getCustomerId())) {
                $cart->setCustomerIsGuest(false);
            }
        }
        parent::validateForCart($cart, $address);
    }
}
