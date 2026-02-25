<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Service;

use Omnik\Core\Helper\Product\Data;
use Omnik\Core\Model\Management\CreateProduct;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class Product
{
    public const SOURCE_CODE_DEFAULT = 'default';
    public const ADMIN_STORE_ID = 0;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    private AttributeOptionInterfaceFactory $optionFactory;

    /**
     * @var AttributeOptionManagementInterface
     */
    private AttributeOptionManagementInterface $optionManagement;

    /**
     * @var SourceItemsSaveInterface
     */
    private SourceItemsSaveInterface $sourceItemsSaveInterface;

    /**
     * @var SourceItemInterfaceFactory
     */
    private SourceItemInterfaceFactory $sourceItemFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var Data
     */
    private Data $productHelper;

    /**
     * @var CreateProduct
     */
    private CreateProduct $createProduct;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param AttributeOptionManagementInterface $optionManagement
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Data $productHelper
     * @param CreateProduct $createProduct
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        AttributeOptionInterfaceFactory $optionFactory,
        AttributeOptionManagementInterface $optionManagement,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        SourceItemInterfaceFactory $sourceItemFactory,
        ProductRepositoryInterface $productRepository,
        Data $productHelper,
        CreateProduct $createProduct,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->optionFactory = $optionFactory;
        $this->optionManagement = $optionManagement;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        $this->createProduct = $createProduct;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string $optionLabel
     * @param string $attributeCode
     * @return void
     * @throws InputException
     * @throws StateException
     */
    public function addOptionToAttribute(string $optionLabel, string $attributeCode)
    {
        $option = $this->optionFactory->create();
        $option->setLabel($optionLabel);

        $this->optionManagement->add(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $attributeCode,
            $option
        );
    }

    /**
     * @param string $sku
     * @param int $qty
     * @return void
     * @throws InputException
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function updateQtyProductBySku(string $sku, int $qty)
    {
        $sourceItem = $this->sourceItemFactory->create();

        $sourceItem->setSourceCode(self::SOURCE_CODE_DEFAULT);
        $sourceItem->setSku($sku);
        $sourceItem->setQuantity($qty);

        $status = $qty > 0 ? $sourceItem::STATUS_IN_STOCK : $sourceItem::STATUS_OUT_OF_STOCK;
        $sourceItem->setStatus($status);

        $this->sourceItemsSaveInterface->execute([$sourceItem]);
    }

    /**
     * @param string $sku
     * @param float $fromPrice
     * @param float $price
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     * @throws NoSuchEntityException
     */
    public function updatePriceProductBySku(string $sku, float $fromPrice, float $price)
    {
        $product = $this->productRepository->get($sku);

        $product->setData('price', $price);
        $product->setData('special_price');

        if ($fromPrice > $price) {
            $product->setData('price', $fromPrice);
            $product->setData('special_price', $price);
        }

        $this->productRepository->save($product);
    }

    /**
     * @param $productData
     * @param $storeId
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function updateProduct($productData, $storeId)
    {
        $this->updateProductConfigurable($productData, $storeId);
        $this->updateProductSimple($productData, $storeId);
    }

    /**
     * @param $productData
     * @param $storeId
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function updateProductConfigurable($productData, $storeId)
    {
        $skuConfigurable = $productData['marketplaces'][0]['marketPlaceId'];

        $productConfigurable = $this->productRepository->get($skuConfigurable, true, self::ADMIN_STORE_ID);

        $productConfigurable->addData($this->getDataProductConfigurable($productData, $storeId));

        $this->productRepository->save($productConfigurable);
    }

    /**
     * @param $productData
     * @param $storeId
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function updateProductSimple($productData, $storeId)
    {
        $brandId = $this->getOptionIdBrand($productData['productData']['brand']);
        $idsCategory = $this->createProduct->getIdsCategory((int) $productData['categories'][0]['id'], $storeId);

        $supplierName = (isset($productData['supplierData']['name'])) ?
            $productData['supplierData']['name'] : '';
        $supplierDocument = (isset($productData['supplierData']['document'])) ?
            $productData['supplierData']['document'] : '';

        $fantasyName = $this->createProduct->getFantasyNameSeller($productData['tenant']);

        foreach ($productData['skus'] as $product) {
            $sku = $product['marketplaces'][0]['marketPlaceId'];

            $productSimple = $this->productRepository->get($sku, true, self::ADMIN_STORE_ID);

            $name = $product['skuData']['skuName'] . ' - ' . $product['skuData']['sku'] . ' - ' . $fantasyName;

            $dataProductSimple = [
                'name' => $name,
                'brand' => $brandId,
                'category_ids' => $idsCategory,
                'supplier_name' => $supplierName,
                'supplier_document' => $supplierDocument
            ];

            $productSimple->addData($dataProductSimple);

            $this->productRepository->save($productSimple);
        }
    }

    /**
     * @param $productData
     * @param $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getDataProductConfigurable($productData, $storeId): array
    {
        $data['name'] = $productData['productData']['productName'];
        $data['description'] = $productData['productData']['descriptionHTML'];
        $data['short_description'] = $productData['productData']['description'];
        $data['brand'] = $this->getOptionIdBrand($productData['productData']['brand']);

        $data['category_ids'] = $this->createProduct->getIdsCategory(
            (int) $productData['categories'][0]['id'],
            $storeId
        );

        $data['supplier_name'] = (isset($productData['supplierData']['name'])) ?
            $productData['supplierData']['name'] : '';
        $data['supplier_document'] = (isset($productData['supplierData']['document'])) ?
            $productData['supplierData']['document'] : '';

        $dataPackage = $this->getDataPackage($productData);
        $data['type_package'] = $dataPackage['type_package'];
        $data['size_package'] = $dataPackage['size_package'];

        return $data;
    }

    /**
     * @param $brandLabel
     * @return int
     * @throws NoSuchEntityException
     */
    public function getOptionIdBrand($brandLabel): int
    {
        return $this->productHelper->getOptionIdAttributeByLabel('brand', $brandLabel);
    }

    /**
     * @param $productData
     * @return string[]
     */
    public function getDataPackage($productData): array
    {
        $typePackage = '';
        $sizePackage = '';

        if (isset($productData['productData']['tags']) && strlen(trim($productData['productData']['tags'])) > 0) {
            $tags = explode(",", $productData['productData']['tags']);

            foreach ($tags as $tag) {
                [$attribute, $value] = explode(":", $tag);

                if (trim($attribute) == 'type_package') {
                    $typePackage = trim($value);
                }

                if (trim($attribute) == 'size_package') {
                    $sizePackage = trim($value);
                }
            }
        }

        return ['type_package' => $typePackage, 'size_package' => $sizePackage];
    }

    /**
     * @param string $skuIdOmnik
     * @return mixed|string
     */
    public function getSkuProduct(string $skuIdOmnik)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku_id_omnik', $skuIdOmnik)->create();
        $productList = $this->productRepository->getList($searchCriteria);

        if ($productList->getTotalCount() > 0) {
            $productData = current($productList->getItems())->getData();
            return $productData['sku'];
        }

        return null;
    }
}
