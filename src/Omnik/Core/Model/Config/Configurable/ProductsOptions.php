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
use Magento\Catalog\Model\Product\Type as ProductType;

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
        if (empty($item->getChildren())) {
            return "";
        }

        $child = $item->getChildren()[0];

        // Caminho preferencial: resolve o seller pelo super_attribute do buyRequest.
        $product = $child->getProduct();
        $data = $product->getTypeInstance(true)->getOrderOptions($product);
        $superAttributes = $data['info_buyRequest']['super_attribute'] ?? null;

        if (is_array($superAttributes)) {
            $seller = $this->getDescription($superAttributes);
            if ($seller !== "") {
                return $seller;
            }
        }

        // Fallback: o buyRequest pode estar ausente/incompleto (produto adicionado por
        // caminho que não persistiu super_attribute) ou o produto do quote não vem com
        // os atributos EAV hidratados. A fonte de verdade é o variant_seller do produto
        // filho, recarregado pelo SKU. Sem isso, itens válidos caíam no grupo vazio e
        // não splitavam.
        return $this->resolveSellerBySku($child->getSku());
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
            // getDescriptionOptionAttributes só resolve seller via configurável, então
            // retorna "" para esses itens e o split os descartava.
            // Filhos de configurável NÃO entram aqui — getSimpleItemsByVendor (frete)
            // depende deles permanecerem no grupo vazio.
            if ($seller === '' && $this->isStandaloneSimple($item)) {
                $seller = $this->resolveSellerBySku((string)$item->getSku());
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
     * Resolve o seller de um produto pelo SKU, recarregando-o do repositório para
     * garantir que o atributo variant_seller (EAV) esteja hidratado — o produto que
     * o quote item carrega vem "leve", sem os custom attributes. Reaproveita o mesmo
     * mapeamento (código -> fantasy_name) do fluxo de configurável. Retorna "" quando
     * o produto não existe ou não tem o atributo preenchido.
     *
     * @param string $sku
     * @return string
     * @throws NoSuchEntityException
     */
    private function resolveSellerBySku(string $sku): string
    {
        if ($sku === '') {
            return "";
        }

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException) {
            return "";
        }

        $attrCode   = $this->configHelper->getAttrVariantSeller();
        $sellerCode = (int)($product->getCustomAttribute($attrCode)?->getValue() ?? 0);

        if ($sellerCode === 0) {
            return "";
        }

        return $this->getSellerFantasy($sellerCode);
    }

    /**
     * Retorna os itens simples agrupados por seller, para montar o payload de frete.
     *
     * Dois formatos de item chegam aqui e ambos precisam ser cotados:
     *
     * - **Filho de configurável**: `separeItemsByVendor` mantém o filho no grupo vazio e
     *   coloca o pai no grupo do seller. O filho é resolvido cruzando seu `parentItemId`
     *   com o item pai, pois é ele que carrega os dados físicos usados na cotação.
     * - **Simples avulso**: já sai de `separeItemsByVendor` sob o grupo do seller (via
     *   `variant_seller` do próprio produto) e é emitido diretamente. Antes esses itens
     *   eram descartados — só o cruzamento por `parentItemId` era considerado —, o que
     *   gerava payload vazio e fazia o carrier cair no preço fixo de contingência.
     *
     * @param array $items
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSimpleItemsByVendor(array $items): array
    {
        $itemsByVendor = $this->separeItemsByVendor($items);

        $sellerByParentItemId = [];
        foreach ($itemsByVendor as $seller => $sellerItems) {
            if ($seller === '') {
                continue;
            }
            foreach ($sellerItems as $sellerItem) {
                $sellerByParentItemId[$sellerItem->getItemId()] = $seller;
            }
        }

        $simpleItems = [];
        foreach ($itemsByVendor as $seller => $sellerItems) {
            foreach ($sellerItems as $item) {
                if ($item->getProductType() !== ProductType::TYPE_SIMPLE) {
                    continue;
                }

                $resolvedSeller = $seller !== ''
                    ? $seller
                    : ($sellerByParentItemId[$item->getParentItemId()] ?? null);

                if ($resolvedSeller === null) {
                    continue;
                }

                $simpleItems[$resolvedSeller][] = $item;
            }
        }

        return $simpleItems;
    }

}
