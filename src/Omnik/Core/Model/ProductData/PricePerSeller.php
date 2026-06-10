<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ProductData;

use Omnik\Core\Api\PricePerSellerInterface;
use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Model\CalculatePrice;
use Omnik\Core\Model\Stock;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Serialize\Serializer\Json;

class PricePerSeller implements PricePerSellerInterface
{

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var CalculatePrice
     */
    private CalculatePrice $calculatePrice;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepositoryInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var Stock
     */
    private Stock $stock;

    /**
     * @var PriceProducts
     */
    private PriceProducts $priceProducts;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @param Request $request
     * @param CustomerSession $customerSession
     * @param CalculatePrice $calculatePrice
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Stock $stock
     * @param PriceProducts $priceProducts
     * @param Json $json
     */
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $_configHelper;

    public function __construct(
        Request $request,
        CustomerSession $customerSession,
        CalculatePrice $calculatePrice,
        ProductRepositoryInterface $productRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Stock $stock,
        PriceProducts $priceProducts,
        Json $json,
        ConfigHelper $configHelper
    ) {
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->calculatePrice = $calculatePrice;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stock = $stock;
        $this->priceProducts = $priceProducts;
        $this->json = $json;
        $this->_configHelper = $configHelper;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(): string
    {
        $params = $this->request->getBodyParams();
        $productId = $params['productId'];
        $product = $this->productRepositoryInterface->getById($productId);
        $sellerId = (int) $params['sellerId'];
        $swatchId = (int) $params['swatchId'];

        $result = $this->getPricesOptions($product, $sellerId, $swatchId);
        return $this->json->serialize($result);
    }

    /**
     * @param ProductInterface $product
     * @param int $sellerId
     * @param int $swatchId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getPricesOptions(ProductInterface $product, int $sellerId, int $swatchId): array
    {
        $result = [];
        $products = $product->getTypeInstance()->getUsedProducts($product);

        foreach ($products as $product) {
            if (!$this->stock->getStockStatus((int) $product->getId())) {
                continue;
            }

            $swatch = (int)($product->getCustomAttribute($this->_configHelper->getAttrVariantEmbalagem())?->getValue() ?? 0);
            $seller = (int)($product->getCustomAttribute($this->_configHelper->getAttrVariantSeller())?->getValue() ?? 0);

            if ($swatchId == $swatch && $seller == $sellerId) {
                $result['price'] = $this->priceProducts->getResultPrices(PriceProducts::TYPE_PRICE, $product);

                if (!empty($product->getSpecialPrice())) {
                    $result['special_price'] = $this->priceProducts->getResultPrices(PriceProducts::TYPE_SPECIAL_PRICE, $product);
                }

                $final_price = $this->calculatePrice->getFinalPrice($product);
                if (!empty($final_price) && !empty($product->getSpecialPrice())) {
                    $result['special_price'] = $final_price;
                } elseif (!empty($final_price) && empty($product->getSpecialPrice())) {
                    $result['special_price'] = $final_price;
                    $result['price'] = $this->priceProducts->getResultPrices(PriceProducts::TYPE_PRICE, $product);
                }
            }
        }

        return $result;
    }
}
