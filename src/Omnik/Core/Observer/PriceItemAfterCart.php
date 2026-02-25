<?php

declare(strict_types=1);

namespace Omnik\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Omnik\Core\Model\CalculatePrice;

class PriceItemAfterCart implements ObserverInterface
{

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var CalculatePrice
     */
    private CalculatePrice $calculatePrice;

    /**
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param CalculatePrice $calculatePrice
     */
    public function __construct(
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        CalculatePrice $calculatePrice
    ) {
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->calculatePrice = $calculatePrice;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $item = $observer->getEvent()->getData('quote_item');
        $item = ($item->getParentItem() ? $item->getParentItem() : $item);

        $priceOld = (float) $item->getPrice();

        $product =  $observer->getEvent()->getData('product');

        $price = $this->calculatePrice->applyFactor($priceOld, $product);

        $item->setCustomPrice($price);
        $item->setOriginalCustomPrice($price);
        $item->getProduct()->setIsSuperMode(true);
    }

}
