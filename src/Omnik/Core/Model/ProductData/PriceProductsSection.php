<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ProductData;

use Omnik\Core\Api\PriceProductsSectionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Omnik\Core\Model\CalculatePrice;
use Omnik\Core\Model\Stock;

class PriceProductsSection implements PriceProductsSectionInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepositoryInterface;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var CustomerSessionFactory
     */
    private CustomerSessionFactory $customerSessionFactory;

    /**
     * @var Stock
     */
    private Stock $stock;

    /**
     * @var CalculatePrice
     */
    private CalculatePrice $calculatePrice;

    /**
     * @var PriceProducts
     */
    private PriceProducts $priceProducts;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Request $request
     * @param Json $json
     * @param CustomerSessionFactory $customerSessionFactory
     * @param Stock $stock
     * @param PriceProducts $priceProducts
     * @param CalculatePrice $calculatePrice
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepositoryInterface,
        Request $request,
        Json $json,
        CustomerSessionFactory $customerSessionFactory,
        Stock $stock,
        PriceProducts $priceProducts,
        CalculatePrice $calculatePrice
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->request = $request;
        $this->json = $json;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->stock = $stock;
        $this->priceProducts = $priceProducts;
        $this->calculatePrice = $calculatePrice;
    }

    /**
     * @return array
     */
    private function getParametersProductsId(): array
    {
        $productIds = [];
        $products = $this->request->getBodyParams()['productIds'];

        if (is_array($products)) {
            $productIds = array_unique($products);
        } else {
            $productIds[] = $products;
        }

        return $productIds;
    }

    /**
     * @return int
     */
    private function getParametersSellerId(): int
    {
        $sellerId = 0;
        $parameters = $this->request->getBodyParams();

        if (isset($parameters['variantSeller'])) {
            $sellerId = (int) $parameters['variantSeller'];
        }

        return $sellerId;
    }

    /**
     * @return string
     */
    public function execute(): string
    {
        $customerSession = $this->customerSessionFactory->create();
        if (!$customerSession->isLoggedIn()) {
            return "";
        }

        $section = [];
        $productIds = $this->getParametersProductsId();
        $sellerId = $this->getParametersSellerId();
        $swatchId = (int) $this->request->getBodyParams()['dataOptionSwatchId'];

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                'entity_id',
                $productIds,
                'in'
            )
            ->create();

        $productSearch = $this->productRepositoryInterface->getList($searchCriteria);
        $items = $productSearch->getItems();

        foreach ($items as $item) {
            $section[$item->getId()] = $this->getArrayPrices($item, $swatchId, $sellerId)[$item->getId()];
        }

        $products = ['products' => $section];
        return $this->json->serialize($products);
    }

    /**
     * @param ProductInterface $product
     * @param int $swatchId
     * @param int $sellerId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getArrayPrices(ProductInterface $product, int $swatchId, int $sellerId): array
    {
        $result = [];
        $products = $product->getTypeInstance()->getUsedProducts($product);
        $sellerId = $this->getSellerIdBySwatchId($product, $swatchId, $sellerId);

        foreach($products as $children) {

            if (!$this->rangeZipcode->execute((int) $children->getId())) {
                continue;
            }

            if ($this->restrictionProduct->execute($children)) {
                continue;
            }

            if (!$this->stock->getStockStatus((int) $children->getId())) {
                continue;
            }

            $seller = (int) $children->getCustomAttribute("variant_seller")->getValue();

            if ($sellerId == $seller) {
                $swatch = (int)$children->getCustomAttribute("variant_embalagem")->getValue();
                $variant = "#" . $swatch;
                $result[$product->getId()][$variant]['price'] = $this->priceProducts->getResultPrices(PriceProducts::TYPE_PRICE, $children);

                if (!empty($children->getSpecialPrice())) {
                    $result[$product->getId()][$variant]['special_price'] = $this->priceProducts->getResultPrices(PriceProducts::TYPE_SPECIAL_PRICE, $children);
                }

                $final_price = $this->calculatePrice->getFinalPrice($children);
                if (!empty($final_price) && !empty($children->getSpecialPrice())) {
                    $result[$product->getId()][$variant]['special_price'] = $final_price;
                } elseif (!empty($final_price) && empty($children->getSpecialPrice())) {
                    $result[$product->getId()][$variant]['special_price'] = $final_price;
                    $result[$product->getId()][$variant]['price'] = $this->priceProducts->getResultPrices(PriceProducts::TYPE_PRICE, $children);
                }
            }
        }

        return $result;
    }

    /**
     * @param ProductInterface $product
     * @param int $swatchId
     * @param int $sellerId
     * @return int
     */
    private function getSellerIdBySwatchId(ProductInterface $product, int $swatchId, int $sellerId): int
    {

        if ($sellerId == 0) {
            $products = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($products as $children) {
                $swatchVariantId = (int) $children->getCustomAttribute('variant_embalagem')->getValue();
            }
        }

        return $sellerId;
    }
}
