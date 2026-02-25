<?php

namespace Omnik\Core\Block\Cart;

use Magento\Checkout\Block\Cart;
use Magento\Framework\Exception\LocalizedException as LocalizedExceptionAlias;
use Magento\Framework\Exception\NoSuchEntityException as NoSuchEntityExceptionAlias;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Framework\App\Http\Context as HttpContext;
use Omnik\Core\Model\Config\Configurable\ProductsOptions;

class Grid extends Cart
{
    public const IS_GIFT_ITEMS = 'IS_GIFT_ITEMS';

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param Url $catalogUrlBuilder
     * @param CartHelper $cartHelper
     * @param HttpContext $httpContext
     * @param CheckoutSession $checkoutSession
     * @param ProductsOptions $productsOptions
     * @param array $data
     */
    public function __construct(
        Context                          $context,
        CustomerSession                  $customerSession,
        Url                              $catalogUrlBuilder,
        CartHelper                       $cartHelper,
        HttpContext                      $httpContext,
        private readonly CheckoutSession $checkoutSession,
        private readonly ProductsOptions $productsOptions,
        array                            $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $catalogUrlBuilder, $cartHelper, $httpContext, $data);
    }

    /**
     * @return array
     * @throws LocalizedExceptionAlias
     * @throws NoSuchEntityExceptionAlias
     */
    public function getItemsByVendor()
    {
        $itemsByVendor = $this->productsOptions->separeItemsByVendor($this->checkoutSession->getQuote()->getItems());

        foreach ($itemsByVendor as $vendor => $items) {
            foreach ($items as $key => $item) {
                if ($item->getIsGift()) {
                    unset($itemsByVendor[$vendor][$key]);
                }
            }

            if(!count($itemsByVendor[$vendor])){
                unset($itemsByVendor[$vendor]);
            }
        }

        $giftItems = $this->getGiftItems();
        if (count($giftItems)) {
            $itemsByVendor[self::IS_GIFT_ITEMS] = $giftItems;
        }

        return $itemsByVendor;
    }

    /**
     * @return array
     * @throws LocalizedExceptionAlias
     * @throws NoSuchEntityExceptionAlias
     */
    public function getGiftItems(): array
    {
        $isGift = [];
        $items = $this->checkoutSession->getQuote()->getItems();
        foreach ($items as $item) {
            if ($item->getIsGift()) {
                $isGift[] = $item;
            }
        }

        return $isGift;
    }

}
