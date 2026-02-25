<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Omnik\Core\Model\CalculatePrice;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Items
{

    public const PRODUCT_CONFIGURABLE = "configurable";

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var CalculatePrice
     */
    private CalculatePrice $calculatePrice;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CalculatePrice $calculatePrice
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CalculatePrice $calculatePrice,
        ProductRepositoryInterface $productRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->calculatePrice = $calculatePrice;
        $this->productRepository = $productRepository;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updatePriceCartItems(): void
    {
        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {

            if ($item->getProductType() == self::PRODUCT_CONFIGURABLE) {
                $productId = key($item->getQtyOptions());
            } else {
                $productId = $item->getProductId();
            }

            $product = $this->productRepository->getById($productId);

            $productPrice = (float) $product->getSpecialPrice();
            if (!$productPrice) {
                $productPrice = (float) $product->getPrice();
            }

            $productPrice = $this->calculatePrice->applyFactor($productPrice, $product);

            $final_price = $this->calculatePrice->getFinalPrice($product);
            if (!empty($final_price)) {
                $productPrice = $final_price;
            }

            $item->setItemId($item->getItemId());
            $item->setPrice($productPrice);
            $item->setBasePrice($productPrice);
            $item->setRowTotal($productPrice * $item->getQty());
            $item->setBaseRowTotal($productPrice);
            $item->setPriceInclTax($productPrice);
            $item->setBasePriceInclTax($productPrice);
            $item->setRowTotalInclTax($productPrice);
            $item->setBaseRowTotalInclTax($productPrice);
            $item->setOriginalCustomPrice($productPrice);
            $item->setCustomPrice($productPrice);
            $item->getProduct()->setIsSuperMode(true);
            $item->save();
        }
    }
}
