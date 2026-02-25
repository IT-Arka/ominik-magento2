<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Config\Configurable;

use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as Attribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product;

class ProductsOptions
{
    public const CODE = 'variant_seller';

    /**
     * @var Attribute
     */
    private Attribute $attribute;

    /**
     * @var Repository
     */
    private Repository $repository;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var Configurable $configurableProduct
     */
    private Configurable $configurableProduct;

    /**
     * @var Product $modelProduct
     */
    private Product $modelProduct;

    /**
     * @param Attribute $attribute
     * @param Repository $repository
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurableProduct
     * @param Product $modelProduct
     */
    public function __construct(
        Attribute                  $attribute,
        Repository                 $repository,
        ProductRepositoryInterface $productRepository,
        Configurable               $configurableProduct,
        Product                    $modelProduct
    ){
        $this->attribute = $attribute;
        $this->repository = $repository;
        $this->productRepository = $productRepository;
        $this->configurableProduct = $configurableProduct;
        $this->modelProduct = $modelProduct;
    }

    /**
     * @param Item $item
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDescriptionOptionAttributes(Item $item): string
    {
        if (!empty($item->getChildren())) {
            $product = $item->getChildren()[0]->getProduct();
            $data = $product->getTypeInstance(true)->getOrderOptions($product);
            $superAttributes = $data['info_buyRequest']['super_attribute'];

            return $this->getDescription($superAttributes);
        }

        return "";
    }

    /**
     * @param array $superAttributes
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDescription(array $superAttributes): string
    {
        $seller = "";
        foreach ($superAttributes as $key => $superAttribute) {
            $model = $this->attribute->load($key);
            $code = $model->getAttributeCode();

            if ($code == self::CODE) {
                $options = $this->repository->get($code)->getOptions();

                foreach ($options as $option) {
                    if ($superAttribute == $option->getValue()) {
                        $seller = $option->getLabel();
                    }
                }
            }
        }
        return $seller;
    }

    /**
     * @param int $sellerCode
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSellerFantasy(int $sellerCode): string
    {
        $seller = "";
        $options = $this->repository->get(self::CODE)->getOptions();

        foreach ($options as $option) {
            if ($option->getValue() == $sellerCode) {
                $seller = $option->getLabel();
            }
        }


        return $seller;
    }

    /**
     * @param array $items
     * @return array
     * @throws NoSuchEntityException
     */
    public function separeItemsByVendor(array $items): array
    {
        $itemsByVendor = [];
        foreach ($items as $item) {
            $seller = $this->getDescriptionOptionAttributes($item);
            $itemsByVendor[$seller][] = $item;
        }
        return $itemsByVendor;
    }

    /**
     * @param array $items
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSimpleItemsByVendor(array $items): array
    {
        $itemsByVendor = $this->separeItemsByVendor($items);
        $configItems = [];
        foreach ($itemsByVendor as $key => $item) {
            if ($key != '') {
                foreach ($item as $configItem) {
                    $configItems[$configItem->getItemId()] = $key;
                }
            }
        }
        $simpleItems = [];
        foreach ($itemsByVendor as $key => $item) {
            if ($key == '') {
                foreach ($item as $simpleItem) {
                    if($simpleItem->getProductType() == 'simple'){
                        if(isset($configItems[$simpleItem->getParentItemId()])){
                            $simpleItems[$configItems[$simpleItem->getParentItemId()]][] = $simpleItem;
                        }
                    }
                }
            }
        }

        return $simpleItems;
    }

}
