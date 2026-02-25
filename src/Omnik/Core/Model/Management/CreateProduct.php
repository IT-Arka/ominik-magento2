<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Management;

use Omnik\Core\Model\AbstractIntegration;
use Omnik\Core\Helper\Product\Data;
use Omnik\Core\Api\SellerRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Eav\Model\Config;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Omnik\Core\Model\SellerFactory;
use Omnik\Core\Logger\Logger;

class CreateProduct
{
    public const STATUS_APPROVED = 'approved';
    public const STATUS_MATCH = 'match';
    public const ATTRIBUTE_CODE_VARIANT_EMBALAGEM = 'variant_embalagem';
    public const ATTRIBUTE_CODE_VARIANT_COR = 'variant_color';
    public const ATTRIBUTE_CODE_VARIANT_TAMANHO = 'variant_tamanho';
    public const ATTRIBUTE_CODE_VARIANT_SELLER = 'variant_seller';
    public const ATTRIBUTE_SKU = 'sku';
    public const ATTRIBUTE_EAN = 'ean';
    public const ATTRIBUTE_BRANDS = 'brand';
    public const ATTRIBUTE_SKU_ID_OMNIK = 'sku_id_omnik';
    public const ATTRIBUTE_PARENT_IMAGE = 'parent_image';
    public const ATTRIBUTE_ERP_CODE = 'erp_code';
    public const ATTRIBUTE_SUPPLIER_DOCUMENT = 'supplier_document';
    public const ATTRIBUTE_SUPPLIER_NAME = 'supplier_name';
    public const ATTRIBUTE_TYPE_PACKAGE = 'type_package';
    public const ATTRIBUTE_SIZE_PACKAGE = 'size_package';
    public const ATTRIBUTE_TENANT = 'tenant';
    public const ATTRIBUTE_WIDTH_PACKAGE = 'width';
    public const ATTRIBUTE_HEIGHT_PACKAGE = 'height';
    public const ATTRIBUTE_LENGHT_PACKAGE = 'lenght';
    /**
     * @var array|string[]
     */
    private array $variantNameData = [
        'EMBALAGEM' => self::ATTRIBUTE_CODE_VARIANT_EMBALAGEM,
        'COR' => self::ATTRIBUTE_CODE_VARIANT_COR,
        'TAMANHO' => self::ATTRIBUTE_CODE_VARIANT_TAMANHO,
        'SELLER' => self::ATTRIBUTE_CODE_VARIANT_SELLER
    ];

    /**
     * @param ProductFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Data $productHelper
     * @param SellerRepositoryInterface $sellerRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param GroupRepositoryInterface $groupRepository
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param ResourceConnection $resourceConnection
     * @param Config $eavConfig
     * @param AttributeOptionManagementInterface $optionManagement
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param SellerFactory $sellerFactory
     * @param Logger $logger
     */
    public function __construct(
        private readonly ProductFactory              $productFactory,
        private readonly ProductRepositoryInterface  $productRepository,
        private readonly Data                        $productHelper,
        private readonly SellerRepositoryInterface   $sellerRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly GroupRepositoryInterface    $groupRepository,
        private readonly ProductUrlPathGenerator     $productUrlPathGenerator,
        private readonly ResourceConnection          $resourceConnection,
        private Config                               $eavConfig,
        private AttributeOptionManagementInterface   $optionManagement,
        private AttributeOptionInterfaceFactory      $optionFactory,
        private SellerFactory                        $sellerFactory,
        private Logger                               $logger
    ) {

    }

    /**
     * @param array $productModeratedData
     * @param int $storeId
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function createProductConfigurable(array $productModeratedData, int $storeId): array
    {
        $erpCode = '';
        $typePackage = '';
        $sizePackage = '';
        $pathParentImage = '';

        $categoryIds = $this->getIdsCategory((int)$productModeratedData['categories'][0]['id'], $storeId);

        $product = $this->productFactory->create();

        if ($productModeratedData['attributes']) {
            foreach ($productModeratedData['attributes'] as $attribute) {
                if ($attribute['name'] == self::ATTRIBUTE_SKU) {
                    $skuProduct = $attribute['value'];

                    if ($storeId != AbstractIntegration::STORE_ID_DEFAULT) {
                        $skuProduct = $storeId . '_' . $attribute['value'];
                    }
                }

                if ($attribute['name'] == self::ATTRIBUTE_EAN) {
                    $ean = $attribute['value'];
                }

                if ($attribute['name'] == self::ATTRIBUTE_PARENT_IMAGE) {
                    $pathParentImage = $attribute['value'];
                }
            }
        }

        if (empty($skuProduct)) {
            $skuProduct = $this->getConfigurableSku($productModeratedData);

            if ($storeId != AbstractIntegration::STORE_ID_DEFAULT) {
                $skuProduct = $storeId . '_' . 'C-' . $productModeratedData['skus'][0]['skuData']['sku'];
            }

            $ean = $productModeratedData['skus'][0]['skuData']['gtin'] ?? '';

            foreach ($productModeratedData['skus'][0]['images'] as $image) {
                if ($image['main']) {
                    $pathParentImage = $image['link'];
                }
            }
        }

        if (
            isset($productModeratedData['productData']['tags']) &&
            strlen(trim($productModeratedData['productData']['tags'])) > 0
        ) {
            $tags = explode(",", $productModeratedData['productData']['tags']);

            foreach ($tags as $tag) {
                [$attribute, $value] = explode(":", $tag);

                if (trim($attribute) == self::ATTRIBUTE_ERP_CODE) {
                    $erpCode = trim($value);
                }

                if (trim($attribute) == self::ATTRIBUTE_TYPE_PACKAGE) {
                    $typePackage = trim($value);
                }

                if (trim($attribute) == self::ATTRIBUTE_SIZE_PACKAGE) {
                    $sizePackage = trim($value);
                }
            }
        }

        $optionIdBrand = $this->productHelper->getOptionIdAttributeByLabel(
            self::ATTRIBUTE_BRANDS,
            $productModeratedData['productData']['brand']
        );

        $product->setStoreId($storeId);
        $product->setWebsiteIds([$storeId]);
        $product->setSku($skuProduct);
        $product->setName($productModeratedData['productData']['productName']);
        $product->setShortDescription($productModeratedData['productData']['description']);
        $product->setDescription($productModeratedData['productData']['descriptionHTML']);
        $product->setAttributeSetId($product->getDefaultAttributeSetId());
        $product->setTypeId(Configurable::TYPE_CODE);
        $product->setStatus(Status::STATUS_ENABLED);
        $product->setVisibility(Visibility::VISIBILITY_BOTH);
        $product->setCategoryIds($categoryIds);

        $urlKey = $this->createUrlKey($product, $skuProduct, $storeId);

        $product->setUrlKey($urlKey);
        $product->setCustomAttribute(self::ATTRIBUTE_EAN, $ean);
        $product->setCustomAttribute(self::ATTRIBUTE_BRANDS, $optionIdBrand);
        $product->setCustomAttribute(self::ATTRIBUTE_SKU_ID_OMNIK, $productModeratedData['productData']['id']);
        $product->setCustomAttribute(self::ATTRIBUTE_ERP_CODE, $erpCode);
        $product->setCustomAttribute(self::ATTRIBUTE_TYPE_PACKAGE, $typePackage);
        $product->setCustomAttribute(self::ATTRIBUTE_SIZE_PACKAGE, $sizePackage);
        $product->setCustomAttribute(self::ATTRIBUTE_WIDTH_PACKAGE, (float)$productModeratedData['packageDimensionData']['width']);
        $product->setCustomAttribute(self::ATTRIBUTE_HEIGHT_PACKAGE, (float)$productModeratedData['packageDimensionData']['height']);
        $product->setCustomAttribute(self::ATTRIBUTE_LENGHT_PACKAGE, (float)$productModeratedData['packageDimensionData']['depth']);

        $product->setStockData(['is_in_stock' => SourceItemInterface::STATUS_IN_STOCK]);
        $product->setWeight((float)$productModeratedData['packageDimensionData']['grossWeight']);

        $productConfigurable = $this->productRepository->save($product);

        if (!empty($pathParentImage)) {
            $this->productHelper->addImage($pathParentImage, $skuProduct);
        }

        return [
            'product_id_configurable' => (int)$productConfigurable->getId(),
            'product_id' => $productModeratedData['productData']['id'],
            'marketplace_id' => $skuProduct
        ];
    }

    /**
     * @param array $productModeratedData
     * @param int $storeId
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function createProductSimple(array $productModeratedData, int $storeId): array
    {
        $matchIdData = [];
        $noMatchIdData = [];
        $matchSkuPublishData = [];
        $noMatchSkuPublishData = [];
        $categoryIds = $this->getIdsCategory((int)$productModeratedData['categories'][0]['id'], $storeId);

        foreach ($productModeratedData['skus'] as $productData) {
            $simpleProduct = $this->getProductSimple($productData, $storeId);
            if ($simpleProduct) {
                $noMatchIdData[] = $simpleProduct->getId();

                $noMatchSkuPublishData[] = [
                    'marketplaceId' => $simpleProduct->getSku(),
                    'skuId' => $productData['skuData']['id']
                ];
                continue;
            }

            $product = $this->productFactory->create();
            $product->setData([]);

            $documentSeller = substr($productModeratedData['tenant'], 4);
            $fantasyName = $this->getFantasyNameSeller($documentSeller);
            $nameProduct = $productData['skuData']['skuName'] . ' - ' .
                $productData['skuData']['sku'] . ' - ' . $fantasyName;


            $optionIdBrand = $this->productHelper->getOptionIdAttributeByLabel(
                self::ATTRIBUTE_BRANDS,
                $productModeratedData['productData']['brand']
            );

            $sku = $this->getSimpleSku($productData, $storeId);

            $product->setStoreId($storeId);
            $product->setWebsiteIds([$storeId]);
            $product->setSku($sku);
            $product->setName($nameProduct);
            $product->setAttributeSetId($product->getDefaultAttributeSetId());
            $product->setTypeId(Type::TYPE_SIMPLE);
            $product->setStatus(Status::STATUS_ENABLED);
            $product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
            $product->setShortDescription($productData['skuData']['description'] ?? '');
            $product->setCategoryIds($categoryIds);

            $urlKey = $this->createUrlKey($product, $sku, $storeId);

            $product->setUrlKey($urlKey);
            $product->setCustomAttribute(self::ATTRIBUTE_BRANDS, $optionIdBrand);
            $product->setCustomAttribute(self::ATTRIBUTE_SKU_ID_OMNIK, $productData['skuData']['id']);
            $product->setCustomAttribute(self::ATTRIBUTE_EAN, $productData['skuData']['gtin'] ?? '');
            $product->setData(self::ATTRIBUTE_TENANT, $productModeratedData['tenant']);
            // $product->setData(self::ATTRIBUTE_CODE_VARIANT_SELLER, $productModeratedData['tenant']);
            $product->setCustomAttribute(self::ATTRIBUTE_WIDTH_PACKAGE, (float)$productModeratedData['packageDimensionData']['width']);
            $product->setCustomAttribute(self::ATTRIBUTE_HEIGHT_PACKAGE, (float)$productModeratedData['packageDimensionData']['height']);
            $product->setCustomAttribute(self::ATTRIBUTE_LENGHT_PACKAGE, (float)$productModeratedData['packageDimensionData']['depth']);
            //attributes variants
            foreach ($productData['attributes'] as $attribute) {
                if (array_key_exists($attribute['name'], $this->variantNameData)) {

                    $optionId = $this->productHelper->getOptionIdAttributeByLabel(
                        $this->variantNameData[$attribute['name']],
                        $attribute['descriptionValue']
                    );

                    $product->setCustomAttribute($this->variantNameData[$attribute['name']], $optionId);
                }
            }

            $seller = $this->setEavSeller($productModeratedData['skus'][0]['tenant']);

            if ($seller) {
                $optionId = $this->productHelper->getOptionIdAttributeByLabel(
                    self::ATTRIBUTE_CODE_VARIANT_SELLER,
                    $seller
                );
                $product->setCustomAttribute(self::ATTRIBUTE_CODE_VARIANT_SELLER, $optionId);
            }
            $product->setPrice((float)$productData['priceData']['price']);
            $product->setWeight((float)$productData['packageDimensionData']['grossWeight']);

            $isInStock = $productData['stockData']['stock'] > 0 ?
                SourceItemInterface::STATUS_IN_STOCK : SourceItemInterface::STATUS_OUT_OF_STOCK;

            $product->setStockData([
                'is_in_stock' => $isInStock,
                'qty' => $productData['stockData']['stock']
            ]);

            if (isset($productData['matchStatus']) && $productData['matchStatus'] == self::STATUS_MATCH) {
                $marketPlaceIdMatch = $productData['matches'][0]['marketPlaceId'];
            }

            $productSimple = $this->productRepository->save($product);

            foreach ($productData['images'] as $image) {
                $this->productHelper->addImage($image['link'], $sku);
            }

            if (isset($productData['matchStatus']) && $productData['matchStatus'] == self::STATUS_MATCH) {
                $matchIdData[$productSimple->getId()] = $marketPlaceIdMatch;

                $matchSkuPublishData[] = [
                    'marketplaceId' => $sku,
                    'skuId' => $productData['skuData']['id']
                ];
            } else {
                $noMatchIdData[] = $productSimple->getId();

                $noMatchSkuPublishData[] = [
                    'marketplaceId' => $sku,
                    'skuId' => $productData['skuData']['id']
                ];
            }

        }

        return [
            'simple_product_match' => $matchIdData,
            'simple_product_no_match' => $noMatchIdData,
            'sku_publish_match' => $matchSkuPublishData,
            'sku_publish_no_match' => $noMatchSkuPublishData,
            'product_id' => $productModeratedData['productData']['id']
        ];
    }

    /**
     * @param string $documentSeller
     * @return string
     * @throws LocalizedException
     */
    public function getFantasyNameSeller(string $documentSeller): string
    {
        $result = $this->sellerRepository->getByOmnikId($documentSeller);

        return $result->getFantasyName();
    }

    /**
     * @param int $categoryId
     * @param int $storeId
     * @return int[]
     */
    public function getIdsCategory(int $categoryId, int $storeId): array
    {
        try {
            $categoryData = $this->categoryRepository->get($categoryId);
            $idsCategoryData = $categoryData->getPathIds();
        } catch (NoSuchEntityException $e) {
            $idsCategoryData = [$this->getRootCategoryId($storeId)];
        }

        return $idsCategoryData;
    }

    /**
     * @param int $storeId
     * @return int
     */
    public function getRootCategoryId(int $storeId): int
    {
        $groups = $this->groupRepository->getList();
        $rootCategoryId = 0;

        foreach ($groups as $group) {
            if ($group->getDefaultStoreId() == $storeId) {
                $rootCategoryId = (int)$group->getRootCategoryId();
            }
        }

        return $rootCategoryId;
    }

    /**
     * @param $productModeratedData
     * @return string
     */
    public function getConfigurableSku($productModeratedData)
    {
        return 'C-' . $productModeratedData['skus'][0]['skuData']['sku'];
    }

    /**
     * @param $dataSku
     * @param $storeId
     * @return string
     */
    public function getSimpleSku($dataSku, $storeId)
    {
        return $storeId . "_" . $dataSku['skuData']['sku'] . '-' . $dataSku['tenant'];
    }

    /**
     * @param array $productModeratedData
     * @return bool
     */
    public function isConfigurableProductExists(array $productModeratedData): bool
    {
        try {
            $this->productRepository->get($this->getConfigurableSku($productModeratedData));
            $isExists = true;
        } catch (NoSuchEntityException $e) {
            $isExists = false;
        }

        return $isExists;
    }

    /**
     * @param array $productModeratedData
     * @param int $storeId
     * @return bool
     */
    public function hasNewSimpleProducts(array $productModeratedData, int $storeId): bool
    {
        if (!count($productModeratedData['skus'])) {
            return false;
        }

        foreach ($productModeratedData['skus'] as $productData) {
            if (!$this->getProductSimple($productData, $storeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $productData
     * @param $storeId
     * @return mixed
     */
    public function getProductSimple($productData, $storeId)
    {
        $simpleSku = $this->getSimpleSku($productData, $storeId);
        try {
            return $this->productRepository->get($simpleSku);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param $productModeratedData
     * @return array
     */
    public function getProductConfigurableParams($productModeratedData): array
    {
        $skuProduct = $this->getConfigurableSku($productModeratedData);
        try {
            $productConfigurable = $this->productRepository->get($skuProduct);
            return [
                'product_id_configurable' => (int)$productConfigurable->getId(),
                'product_id' => $productModeratedData['productData']['id'],
                'marketplace_id' => $skuProduct
            ];

        } catch (NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     * @param array $productModeratedData
     * @param int $storeId
     * @return bool
     */
    public function isNewProductConfigurable(array $productModeratedData, int $storeId): bool
    {
        if ($productModeratedData['attributes']) {
            foreach ($productModeratedData['attributes'] as $attribute) {
                if ($attribute['name'] == self::ATTRIBUTE_SKU) {
                    $skuProduct = $attribute['value'];

                    if ($storeId != AbstractIntegration::STORE_ID_DEFAULT) {
                        $skuProduct = $storeId . '_' . $attribute['value'];
                    }
                }
            }
        }

        if (empty($skuProduct)) {
            $skuProduct = 'C-' . $productModeratedData['skus'][0]['skuData']['sku'];

            if ($storeId != AbstractIntegration::STORE_ID_DEFAULT) {
                $skuProduct = $storeId . '_' . 'C-' . $productModeratedData['skus'][0]['skuData']['sku'];
            }
        }

        try {
            $this->productRepository->get($skuProduct);
            $isNew = false;

            if ($this->existMatch($productModeratedData)) {
                $isNew = true;
            }
        } catch (NoSuchEntityException $e) {
            $isNew = true;
        }

        return $isNew;
    }

    /**
     * @param array $productModeratedData
     * @return bool
     */
    public function existMatch(array $productModeratedData): bool
    {
        $qtySku = count($productModeratedData['skus']);
        $qtyMatch = 0;

        foreach ($productModeratedData['skus'] as $productData) {
            if (isset($productData['matchStatus']) && $productData['matchStatus'] == self::STATUS_MATCH) {
                $qtyMatch++;
            }
        }

        if ($qtySku == $qtyMatch) {
            return true;
        }

        return false;
    }

    /**
     * @param $product
     * @param $sku
     * @param $storeId
     * @return string|null
     */
    public function createUrlKey($product, $sku, $storeId)
    {
        $urlKey = $this->productUrlPathGenerator->getUrlKey($product);
        $isUnique = $this->checkUrlKeyDuplicates($sku, $urlKey, $storeId);
        if ($isUnique) {
            return $urlKey;
        } else {
            return $urlKey . '-' . time();
        }
    }

    /**
     * @param $sku
     * @param $urlKey
     * @param $storeId
     * @return bool
     */
    public function checkUrlKeyDuplicates($sku, $urlKey, $storeId)
    {
        $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);

        $sql = $connection->select()->from(
            ['url_rewrite' => $connection->getTableName('url_rewrite')], ['request_path', 'store_id']
        )->joinLeft(
            ['cpe' => $connection->getTableName('catalog_product_entity')], "cpe.entity_id = url_rewrite.entity_id"
        )
            ->where('request_path IN (?)', $urlKey)
            ->where('store_id IN (?)', $storeId)
            ->where('cpe.sku not in (?)', $sku);

        $urlKeyDuplicates = $connection->fetchAssoc($sql);

        if (!empty($urlKeyDuplicates)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $tenant
     * @return string
     */
    public function setEavSeller($tenant):string
    {
        $seller ='';

        try {
            $sellerModel = $this->sellerFactory->create()->load($tenant, 'omnik_id');
            $seller = $sellerModel->getData('fantasy_name');

            $option = $this->optionFactory->create();
            $option->setLabel($seller);
            $this->optionManagement->add(
                ProductAttributeInterface::ENTITY_TYPE_CODE,
                self::ATTRIBUTE_CODE_VARIANT_SELLER,
                $option
            );
            
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $seller;
    }
}
