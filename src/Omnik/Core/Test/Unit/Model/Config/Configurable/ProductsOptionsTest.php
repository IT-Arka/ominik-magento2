<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model\Config\Configurable;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Model\Config\Configurable\ProductsOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Omnik\Core\Model\Config\Configurable\ProductsOptions
 */
class ProductsOptionsTest extends TestCase
{
    private const VARIANT_SELLER_ATTR = 'variant_seller';
    private const SELLER_CODE = 6;
    private const SELLER_LABEL = 'Cada Coisa UD';
    private const SELLER_CODE_2 = 14;
    private const SELLER_LABEL_2 = 'Teste de Seller V3';

    /** @var Repository&MockObject */
    private Repository $repository;
    /** @var ConfigHelper&MockObject */
    private ConfigHelper $configHelper;
    /** @var ProductRepositoryInterface&MockObject */
    private ProductRepositoryInterface $productRepository;

    /**
     * Mapa sku => variant_seller code, alimentado por cada teste. O productRepository
     * resolve o produto por SKU (fonte de verdade), refletindo o comportamento real:
     * o produto do quote item vem sem os atributos EAV hidratados.
     *
     * @var array<string,int>
     */
    private array $skuSellerMap = [];

    /** @var array<string,string> map code => label das options de variant_seller */
    private array $optionLabels = [];

    private ProductsOptions $productsOptions;

    protected function setUp(): void
    {
        $this->repository        = $this->createMock(Repository::class);
        $this->configHelper      = $this->createMock(ConfigHelper::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);

        $this->configHelper->method('getAttrVariantSeller')->willReturn(self::VARIANT_SELLER_ATTR);

        $this->optionLabels = [
            (string)self::SELLER_CODE   => self::SELLER_LABEL,
            (string)self::SELLER_CODE_2 => self::SELLER_LABEL_2,
        ];

        // getSellerFantasy() faz repository->get(attr)->getOptions().
        $options = [];
        foreach ($this->optionLabels as $value => $label) {
            $option = $this->createMock(AttributeOptionInterface::class);
            $option->method('getValue')->willReturn($value);
            $option->method('getLabel')->willReturn($label);
            $options[] = $option;
        }
        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getOptions')->willReturn($options);
        $this->repository->method('get')->willReturn($attribute);

        // productRepository->get($sku) resolve o produto conforme o skuSellerMap.
        $this->productRepository->method('get')->willReturnCallback(
            function (string $sku) {
                if (!array_key_exists($sku, $this->skuSellerMap)) {
                    throw new NoSuchEntityException(__('no product %1', $sku));
                }
                $code = $this->skuSellerMap[$sku];
                $attr = null;
                if ($code !== 0) {
                    $attr = $this->createMock(AttributeInterface::class);
                    $attr->method('getValue')->willReturn((string)$code);
                }
                $product = $this->createMock(ProductInterface::class);
                $product->method('getCustomAttribute')->with(self::VARIANT_SELLER_ATTR)->willReturn($attr);
                return $product;
            }
        );

        $this->productsOptions = new ProductsOptions(
            $this->createMock(Attribute::class),
            $this->repository,
            $this->productRepository,
            $this->createMock(Configurable::class),
            $this->createMock(Product::class),
            $this->configHelper
        );
    }

    /**
     * Caso 2 (NOVO): simples avulso com variant_seller resolve o seller pelo produto (por SKU).
     */
    public function testStandaloneSimpleWithVariantSellerIsGroupedBySeller(): void
    {
        $item = $this->makeStandaloneSimple('sku-vela', self::SELLER_CODE);

        $result = $this->productsOptions->separeItemsByVendor([$item]);

        $this->assertArrayHasKey(self::SELLER_LABEL, $result);
        $this->assertArrayNotHasKey('', $result);
        $this->assertSame([$item], $result[self::SELLER_LABEL]);
    }

    /**
     * Caso 3: simples avulso SEM variant_seller permanece no grupo vazio.
     */
    public function testStandaloneSimpleWithoutVariantSellerStaysInEmptyGroup(): void
    {
        $item = $this->makeStandaloneSimple('sku-sem-seller', 0);

        $result = $this->productsOptions->separeItemsByVendor([$item]);

        $this->assertArrayHasKey('', $result);
        $this->assertArrayNotHasKey(self::SELLER_LABEL, $result);
    }

    /**
     * Caso 4 (CRÍTICO): filho de configurável NUNCA entra no fallback de avulso —
     * permanece no grupo vazio para que getSimpleItemsByVendor (frete) funcione.
     */
    public function testConfigurableChildNeverEntersStandaloneFallback(): void
    {
        // Filho tem parent_item_id: mesmo com variant_seller no produto, não deve
        // ser resolvido pelo fallback de avulso.
        $item = $this->makeItem('sku-filho', hasChildren: false, parentItemId: 130, sellerCode: self::SELLER_CODE);

        $result = $this->productsOptions->separeItemsByVendor([$item]);

        $this->assertArrayHasKey('', $result);
        $this->assertArrayNotHasKey(self::SELLER_LABEL, $result);
        $this->assertSame([$item], $result['']);
    }

    /**
     * Caso configurável (com filho): resolve o seller pelo SKU do filho quando o
     * buyRequest não traz super_attribute — reproduz o cart que quebrava com TypeError.
     */
    public function testConfigurableResolvesSellerByChildSkuWhenBuyRequestMissing(): void
    {
        $child = $this->makeChild('sku-config-child', self::SELLER_CODE_2);
        $parent = $this->makeConfigurableParent([$child]);

        $result = $this->productsOptions->separeItemsByVendor([$parent]);

        $this->assertArrayHasKey(self::SELLER_LABEL_2, $result);
        $this->assertSame([$parent], $result[self::SELLER_LABEL_2]);
    }

    /**
     * Caso 5: carrinho misto com dois avulsos de sellers distintos gera 2 grupos.
     */
    public function testMixedCartProducesTwoSellerGroups(): void
    {
        $sellerA = $this->makeStandaloneSimple('sku-a', self::SELLER_CODE);
        $sellerB = $this->makeStandaloneSimple('sku-b', self::SELLER_CODE_2);

        $result = $this->productsOptions->separeItemsByVendor([$sellerA, $sellerB]);

        $this->assertArrayNotHasKey('', $result);
        $this->assertCount(2, $result);
        $this->assertSame([$sellerA], $result[self::SELLER_LABEL]);
        $this->assertSame([$sellerB], $result[self::SELLER_LABEL_2]);
    }

    /**
     * @return Item&MockObject
     */
    private function makeStandaloneSimple(string $sku, int $sellerCode): Item
    {
        return $this->makeItem($sku, hasChildren: false, parentItemId: null, sellerCode: $sellerCode);
    }

    /**
     * Cria um item de quote. Registra o SKU no skuSellerMap para o productRepository resolver.
     *
     * @return Item&MockObject
     */
    private function makeItem(string $sku, bool $hasChildren, ?int $parentItemId, int $sellerCode): Item
    {
        $this->skuSellerMap[$sku] = $sellerCode;

        // getChildren/getProduct/getSku são reais; getParentItemId/getHasChildren
        // são mágicos (via __call/DataObject), logo precisam de addMethods().
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren', 'getSku'])
            ->addMethods(['getParentItemId', 'getHasChildren'])
            ->getMock();

        $item->method('getChildren')->willReturn([]);
        $item->method('getSku')->willReturn($sku);
        $item->method('getParentItemId')->willReturn($parentItemId);
        $item->method('getHasChildren')->willReturn($hasChildren);

        return $item;
    }

    /**
     * Cria um item filho (dentro de um configurável) com SKU e seller resolvíveis.
     *
     * @return Item&MockObject
     */
    private function makeChild(string $sku, int $sellerCode): Item
    {
        $this->skuSellerMap[$sku] = $sellerCode;

        $child = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getProduct'])
            ->getMock();
        $child->method('getSku')->willReturn($sku);

        // Produto "leve" do quote: getOrderOptions sem info_buyRequest, forçando o
        // fallback por SKU (o cenário que quebrava com TypeError).
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeInstance'])
            ->getMock();
        $typeInstance = $this->getMockBuilder(\Magento\Catalog\Model\Product\Type\AbstractType::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrderOptions'])
            ->getMockForAbstractClass();
        $typeInstance->method('getOrderOptions')->willReturn([]); // sem info_buyRequest
        $product->method('getTypeInstance')->willReturn($typeInstance);
        $child->method('getProduct')->willReturn($product);

        return $child;
    }

    /**
     * @param array<int,Item> $children
     * @return Item&MockObject
     */
    private function makeConfigurableParent(array $children): Item
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren'])
            ->addMethods(['getParentItemId', 'getHasChildren'])
            ->getMock();
        $item->method('getChildren')->willReturn($children);
        $item->method('getParentItemId')->willReturn(null);
        $item->method('getHasChildren')->willReturn(true);

        return $item;
    }
}
