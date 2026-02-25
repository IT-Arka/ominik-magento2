<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Seller;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Omnik\Core\Api\SellerRepositoryInterface;
use Omnik\Core\Api\ProductSellerInterface;
use Omnik\Core\Model\Config\Configurable\ProductsOptions;

class ProductSeller implements ProductSellerInterface
{

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepositoryInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var SellerRepositoryInterface
     */
    private SellerRepositoryInterface $sellerRepositoryInterface;

    /**
     * @var ProductsOptions
     */
    private ProductsOptions $productsOptions;

    /**
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SellerRepositoryInterface $sellerRepositoryInterface
     * @param ProductsOptions $productsOptions
     */
    public function __construct(
        ProductRepositoryInterface $productRepositoryInterface,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        SellerRepositoryInterface  $sellerRepositoryInterface,
        ProductsOptions            $productsOptions
    ) {
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sellerRepositoryInterface = $sellerRepositoryInterface;
        $this->productsOptions = $productsOptions;
    }

    /**
     * @param string $sku
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSellerId(string $sku): string
    {
        $sellerFantasyName = $this->getSellerNameBySku($sku);
        $searchCriteria = $this->searchCriteriaBuilder->addFilter("fantasy_name", $sellerFantasyName)->create();
        $result = $this->sellerRepositoryInterface->getList($searchCriteria);
        $item = array_values($result->getItems())[0];

        return $item->getOmnikId();
    }

    /**
     * @param $sku
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSellerNameBySku($sku)
    {
        $product = $this->productRepositoryInterface->get($sku);
        $sellerCode = (int)$product->getCustomAttribute('variant_seller')->getValue();

        return $this->productsOptions->getSellerFantasy($sellerCode);
    }
}
