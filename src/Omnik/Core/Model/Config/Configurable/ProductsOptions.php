<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Config\Configurable;

use Omnik\Core\Helper\Config as ConfigHelper;
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
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * @param Attribute $attribute
     * @param Repository $repository
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurableProduct
     * @param Product $modelProduct
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Attribute                  $attribute,
        Repository                 $repository,
        ProductRepositoryInterface $productRepository,
        Configurable               $configurableProduct,
        Product                    $modelProduct,
        ConfigHelper               $configHelper
    ) {
        $this->attribute = $attribute;
        $this->repository = $repository;
        $this->productRepository = $productRepository;
        $this->configurableProduct = $configurableProduct;
        $this->modelProduct = $modelProduct;
        $this->configHelper = $configHelper;
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
        $variantSellerCode = $this->configHelper->getAttrVariantSeller();
        $seller = "";
        foreach ($superAttributes as $key => $superAttribute) {
            $model = $this->attribute->load($key);
            $code  = $model->getAttributeCode();

            if ($code == $variantSellerCode) {
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
        $variantSellerCode = $this->configHelper->getAttrVariantSeller();
        $seller  = "";
        $options = $this->repository->get($variantSellerCode)->getOptions();

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

            // Produto simples avulso (sem pai configurável) cadastrado com variant_seller:
            // getDescriptionOptionAttributes só resolve seller via super_attribute de
            // configurável, então retorna "" para esses itens e o split os descartava.
            // Filhos de configurável NÃO entram aqui — getSimpleItemsByVendor (frete)
            // depende deles permanecerem no grupo vazio.
            if ($seller === '' && $this->isStandaloneSimple($item)) {
                $seller = $this->resolveSellerFromProduct($item);
            }

            $itemsByVendor[$seller][] = $item;
        }
        return $itemsByVendor;
    }

    /**
     * Item simples vendido diretamente (não é filho de configurável nem tem filhos).
     *
     * @param Item $item
     * @return bool
     */
    private function isStandaloneSimple(Item $item): bool
    {
        return $item->getParentItemId() === null && !$item->getHasChildren();
    }

    /**
     * Resolve o seller lendo o atributo variant_seller direto do produto simples,
     * reaproveitando o mesmo mapeamento (código -> fantasy_name) usado no fluxo de
     * configurável. Retorna "" quando o produto não tem o atributo preenchido,
     * preservando o comportamento atual (item fica no grupo vazio).
     *
     * @param Item $item
     * @return string
     * @throws NoSuchEntityException
     */
    private function resolveSellerFromProduct(Item $item): string
    {
        $product    = $item->getProduct();
        $attrCode   = $this->configHelper->getAttrVariantSeller();
        $sellerCode = (int)($product->getCustomAttribute($attrCode)?->getValue() ?? 0);

        if ($sellerCode === 0) {
            return "";
        }

        return $this->getSellerFantasy($sellerCode);
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
