<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Config;

use Omnik\Core\Model\Repositories\BrandRepository;
use Omnik\Core\Helper\Config;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Params
{
    public const CATEGORY_ID_DEFAULT = 444;
    public const CATEGORY_NAME_DEFAULT = 'Todas as categorias';
    public const BRAND_DEFAULT = 'Omnik';
    public const SKU_NAME_DEFAULT = 'sku';

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Config
     */
    private Config $integrationHelper;

    /**
     * @var BrandRepository
     */
    private BrandRepository $brandRepository;

    /**
     * @var Image
     */
    private Image $imageHelper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param Json $json
     * @param Config $integrationHelper
     * @param BrandRepository $brandRepository
     * @param Image $imageHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Json $json,
        Config $integrationHelper,
        BrandRepository $brandRepository,
        Image $imageHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->json = $json;
        $this->integrationHelper = $integrationHelper;
        $this->brandRepository = $brandRepository;
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $product
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createParameters($product)
    {
        $productData = $product->getData();

        $urlImage = $this->imageHelper->getDefaultPlaceholderUrl('image');

        if (isset($productData['image'])) {
            $storeMediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $urlImage = $storeMediaUrl . 'catalog/product' . $productData['image'];
        }

        $brand = self::BRAND_DEFAULT;
        if (isset($productData['brands'])) {
            $brandData = $this->brandRepository->getByBrandCode($productData['brands']);
            $brand = $brandData->getBrandName();
        }

        //productData
        $params["productData"]["id"] = $productData['entity_id'];
        $params["productData"]["active"] = (bool) $productData['status'];
        $params["productData"]["productName"] = $productData['name'];
        $params["productData"]["description"] = $productData['name'];
        $params["productData"]["descriptionHTML"] = $productData['description'];
        $params["productData"]["brand"] = $brand;
        $params["productData"]["warranty"] = 0;
        $params["productData"]["variant"] = true;

        //categoriesData
        $params["categories"][0]["id"] = (string) self::CATEGORY_ID_DEFAULT;
        $params["categories"][0]["name"] = self::CATEGORY_NAME_DEFAULT;

        //skuData
        $params["skus"][0]["skuData"]["id"] = $productData['entity_id'];
        $params["skus"][0]["skuData"]["sku"] = $productData['sku'];
        $params["skus"][0]["skuData"]["skuName"] = self::SKU_NAME_DEFAULT;
        $params["skus"][0]["skuData"]["active"] = (bool) $productData['status'];

        //priceData
        $params["skus"][0]["priceData"]["price"] = (float) $productData['price'];

        //stockData
        $params["skus"][0]["stockData"]["stock"] = 1;
        $params["skus"][0]["stockData"]["minStock"] = 1;

        //DimensionData
        $params["skus"][0]["packageDimensionData"]["width"] = 0;
        $params["skus"][0]["packageDimensionData"]["height"] = 0;
        $params["skus"][0]["packageDimensionData"]["depth"] = 0;
        $params["skus"][0]["packageDimensionData"]["grossWeight"] = (float) $productData['weight'];

        //imageData
        $params["skus"][0]["images"][0]["link"] = $urlImage;

        $params["skus"][0]["marketplaces"][0]["marketplace"] = $this->integrationHelper->getTenantMarketplace();
        $params["skus"][0]["marketplaces"][0]["marketPlaceId"] = $productData['sku'];
        $params["skus"][0]["marketplaces"][0]["marketPlaceProductId"] = $productData['entity_id'];

        $params["marketplaces"][0]["marketplace"] = $this->integrationHelper->getTenantMarketplace();
        $params["marketplaces"][0]["marketPlaceId"] = $productData['sku'];
        $params["marketplaces"][0]["marketPlaceProductId"] = $productData['entity_id'];

        return $this->json->serialize([$params]);
    }
}
