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

    private ProductsOptions $productsOptions;

    protected function setUp(): void
    {
        $this->repository   = $this->createMock(Repository::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);

        $this->configHelper->method('getAttrVariantSeller')->willReturn(self::VARIANT_SELLER_ATTR);

        // getSellerFantasy() faz repository->get(attr)->getOptions(); mapeamos
        // SELLER_CODE -> SELLER_LABEL para validar a resolução do seller.
        $option = $this->createMock(AttributeOptionInterface::class);
        $option->method('getValue')->willReturn((string)self::SELLER_CODE);
        $option->method('getLabel')->willReturn(self::SELLER_LABEL);

        $option2 = $this->createMock(AttributeOptionInterface::class);
        $option2->method('getValue')->willReturn((string)self::SELLER_CODE_2);
        $option2->method('getLabel')->willReturn(self::SELLER_LABEL_2);

        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getOptions')->willReturn([$option, $option2]);
        $this->repository->method('get')->willReturn($attribute);

        $this->productsOptions = new ProductsOptions(
            $this->createMock(Attribute::class),
            $this->repository,
            $this->createMock(ProductRepositoryInterface::class),
            $this->createMock(Configurable::class),
            $this->createMock(Product::class),
            $this->configHelper
        );
    }

    /**
     * Caso 2 (NOVO): simples avulso com variant_seller resolve o seller pelo produto.
     */
    public function testStandaloneSimpleWithVariantSellerIsGroupedBySeller(): void
    {
        $item = $this->makeStandaloneSimple(self::SELLER_CODE);

        $result = $this->productsOptions->separeItemsByVendor([$item]);

        $this->assertArrayHasKey(self::SELLER_LABEL, $result);
        $this->assertArrayNotHasKey('', $result);
        $this->assertSame([$item], $result[self::SELLER_LABEL]);
    }

    /**
     * Caso 3: simples avulso SEM variant_seller permanece no grupo vazio (comportamento atual).
     */
    public function testStandaloneSimpleWithoutVariantSellerStaysInEmptyGroup(): void
    {
        $item = $this->makeStandaloneSimple(0);

        $result = $this->productsOptions->separeItemsByVendor([$item]);

        $this->assertArrayHasKey('', $result);
        $this->assertArrayNotHasKey(self::SELLER_LABEL, $result);
    }

    /**
     * Caso 4 (CRÍTICO): filho de configurável NUNCA entra no fallback — permanece no
     * grupo vazio para que getSimpleItemsByVendor (frete) continue funcionando.
     */
    public function testConfigurableChildNeverEntersFallback(): void
    {
        // Filho tem parent_item_id preenchido: mesmo com variant_seller no produto,
        // não deve ser resolvido pelo fallback.
        $item = $this->makeItem(
            hasChildren: false,
            parentItemId: 130,
            sellerCode: self::SELLER_CODE
        );

        $result = $this->productsOptions->separeItemsByVendor([$item]);

        $this->assertArrayHasKey('', $result);
        $this->assertArrayNotHasKey(self::SELLER_LABEL, $result);
        $this->assertSame([$item], $result['']);
    }

    /**
     * Item que já tem filhos (configurável no carrinho) não é tratado como avulso;
     * não deve chamar o fallback (getChildren vazio no mock => "" sem crash).
     */
    public function testItemWithChildrenIsNotTreatedAsStandalone(): void
    {
        $item = $this->makeItem(
            hasChildren: true,
            parentItemId: null,
            sellerCode: self::SELLER_CODE
        );
        // getChildren() vazio => getDescriptionOptionAttributes retorna "".
        $item->method('getChildren')->willReturn([]);

        $result = $this->productsOptions->separeItemsByVendor([$item]);

        $this->assertArrayHasKey('', $result);
    }

    /**
     * Caso 5: carrinho misto com dois simples avulsos de sellers distintos gera 2 grupos.
     */
    public function testMixedCartProducesTwoSellerGroups(): void
    {
        $sellerA = $this->makeStandaloneSimple(self::SELLER_CODE);
        $sellerB = $this->makeStandaloneSimple(self::SELLER_CODE_2);

        $result = $this->productsOptions->separeItemsByVendor([$sellerA, $sellerB]);

        $this->assertArrayNotHasKey('', $result);
        $this->assertCount(2, $result);
        $this->assertSame([$sellerA], $result[self::SELLER_LABEL]);
        $this->assertSame([$sellerB], $result[self::SELLER_LABEL_2]);
    }

    /**
     * @param int $sellerCode variant_seller do produto (0 = ausente)
     * @return Item&MockObject
     */
    private function makeStandaloneSimple(int $sellerCode): Item
    {
        return $this->makeItem(hasChildren: false, parentItemId: null, sellerCode: $sellerCode);
    }

    /**
     * @return Item&MockObject
     */
    private function makeItem(bool $hasChildren, ?int $parentItemId, int $sellerCode): Item
    {
        // getChildren/getProduct são métodos reais; getParentItemId/getHasChildren
        // são mágicos (via __call/DataObject), logo precisam de addMethods().
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren', 'getProduct'])
            ->addMethods(['getParentItemId', 'getHasChildren'])
            ->getMock();

        $item->method('getChildren')->willReturn([]);
        $item->method('getParentItemId')->willReturn($parentItemId);
        $item->method('getHasChildren')->willReturn($hasChildren);

        $attribute = null;
        if ($sellerCode !== 0) {
            $attribute = $this->createMock(AttributeInterface::class);
            $attribute->method('getValue')->willReturn((string)$sellerCode);
        }

        $product = $this->createMock(ProductInterface::class);
        $product->method('getCustomAttribute')
            ->with(self::VARIANT_SELLER_ATTR)
            ->willReturn($attribute);

        $item->method('getProduct')->willReturn($product);

        return $item;
    }
}
