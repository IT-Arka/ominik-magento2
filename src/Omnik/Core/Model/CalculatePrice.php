<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class CalculatePrice
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param float $price
     * @param \Magento\Catalog\Model\Product $product
     * @return float
     */
    public function applyFactor(float $price, \Magento\Catalog\Model\Product $product): float
    {
//        $sellerOptionId =  $this->getSellerOptionIdBySku($product->getSku());
//        $factorBillet = $this->optionsBillet->getCustomerPercent();
//        if ($factorBillet > 0) {
//            $price = $price * $factorBillet;
//        }

        return $price;
    }

    /**
     * @param string $sku
     * @return int
     */
    public function getSellerOptionIdBySku(string $sku): int
    {
        $sellerOptionId = 0;
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $sku)->create();
        $productList = $this->productRepository->getList($searchCriteria);

        if ($productList->getTotalCount() > 0) {
            $productData = current($productList->getItems())->getData();

            if (isset($productData['variant_seller'])) {
                $sellerOptionId = $productData['variant_seller'];
            }
        }

        return (int) $sellerOptionId;
    }

    /**
     * @param \Magento\Catalog\Model\Product $children
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFinalPrice(\Magento\Catalog\Model\Product $children): float
    {
        $price = 0.0;
        if ($children->getFinalPrice() < $children->getSpecialPrice()) {
            $price = (float) $children->getFinalPrice();
        }

        if (empty($children->getSpecialPrice()) && !empty($children->getFinalPrice())) {
            $price = (float) $children->getFinalPrice();
        }

        if (empty($price)) {
            return $price;
        }

        $result = $this->applyFactor($price, $children);
        return $result;
    }
}
