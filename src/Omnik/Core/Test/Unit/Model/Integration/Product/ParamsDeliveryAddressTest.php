<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model\Integration;

use Omnik\Core\Helper\SplitOrder\Data as SplitHelper;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Params;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a resolução de dados de entrega de um pedido ÚNICO (não-split) para
 * o payload da Omnik. O ponto crítico é enviar o `quotationId` e o
 * `deliveryMethodId` REAIS da cotação salva — e não o shipping_method
 * sintético do Magento (`omnik_<tenant>-<id>`) — que era a causa de a
 * Intelipost não integrar o pedido.
 *
 * @covers \Omnik\Core\Model\Integration\Params
 */
class ParamsDeliveryAddressTest extends TestCase
{
    private const SHIPPING_METHOD = 'omnik_IAMN93331158034119-1';
    private const TENANT = 'IAMN93331158034119';

    private SplitHelper&MockObject $splitHelper;
    private Logger&MockObject $logger;
    private Params $params;

    protected function setUp(): void
    {
        $this->splitHelper = $this->createMock(SplitHelper::class);
        $this->logger = $this->createMock(Logger::class);

        // Params tem muitas dependências irrelevantes a getDeliveryAddress;
        // instanciamos sem o construtor e injetamos só o que o método usa.
        $reflection = new \ReflectionClass(Params::class);
        $this->params = $reflection->newInstanceWithoutConstructor();

        $this->setPrivate('splitHelper', $this->splitHelper);
        $this->setPrivate('logger', $this->logger);
    }

    public function testReturnsRealOmnikRateWhenAvailable(): void
    {
        // Arrange
        $order = $this->makeOrder();
        $this->splitHelper->method('getTenantByProductSku')->willReturn(self::TENANT);
        $this->splitHelper->method('isOmnikShipping')->with(self::SHIPPING_METHOD)->willReturn(true);
        $this->splitHelper->method('isContigency')->willReturn(false);
        $this->splitHelper->method('getOmnikRateSelected')->willReturn([
            'deliveryMethodId' => '1',
            'description' => 'Correios PAC - Entrega: até 8 dias úteis',
            'deliveryEstimateBusinessDays' => 8,
            'quotationId' => 'a1b2c3-real-quotation-id',
        ]);

        // Act
        $result = $this->invokeGetDeliveryAddress($order);

        // Assert — valores REAIS da cotação, não o shipping_method do Magento.
        $this->assertSame('1', $result['deliveryMethodId']);
        $this->assertSame('a1b2c3-real-quotation-id', $result['quotationId']);
        $this->assertSame(8, $result['deliveryEstimateBusinessDays']);
        $this->assertNotSame(self::SHIPPING_METHOD, $result['quotationId']);
    }

    public function testFallsBackToMagentoMethodWhenRateNotFound(): void
    {
        // Arrange
        $order = $this->makeOrder();
        $this->splitHelper->method('getTenantByProductSku')->willReturn(self::TENANT);
        $this->splitHelper->method('isOmnikShipping')->willReturn(true);
        $this->splitHelper->method('isContigency')->willReturn(false);
        $this->splitHelper->method('getOmnikRateSelected')->willReturn([]);

        // Act
        $result = $this->invokeGetDeliveryAddress($order);

        // Assert — sem rate salva, preserva o comportamento anterior.
        $this->assertSame('IAMN93331158034119-1', $result['deliveryMethodId']);
        $this->assertSame(self::SHIPPING_METHOD, $result['quotationId']);
        $this->assertSame('', $result['deliveryEstimateBusinessDays']);
    }

    public function testFallsBackForContingencyShipping(): void
    {
        // Arrange
        $order = $this->makeOrder();
        $this->splitHelper->method('getTenantByProductSku')->willReturn(self::TENANT);
        $this->splitHelper->method('isOmnikShipping')->willReturn(true);
        $this->splitHelper->method('isContigency')->willReturn(true);
        $this->splitHelper->expects($this->never())->method('getOmnikRateSelected');

        // Act
        $result = $this->invokeGetDeliveryAddress($order);

        // Assert
        $this->assertSame(self::SHIPPING_METHOD, $result['quotationId']);
        $this->assertSame('IAMN93331158034119-1', $result['deliveryMethodId']);
    }

    public function testFallsBackForNonOmnikShipping(): void
    {
        // Arrange
        $order = $this->makeOrder('flatrate_flatrate', 'flatrate');
        $this->splitHelper->method('getTenantByProductSku')->willReturn(self::TENANT);
        $this->splitHelper->method('isOmnikShipping')->willReturn(false);
        $this->splitHelper->expects($this->never())->method('getOmnikRateSelected');

        // Act
        $result = $this->invokeGetDeliveryAddress($order);

        // Assert
        $this->assertSame('flatrate_flatrate', $result['quotationId']);
        $this->assertSame('flatrate', $result['deliveryMethodId']);
    }

    /**
     * @param Order&MockObject $order
     * @return array
     */
    private function invokeGetDeliveryAddress(Order $order): array
    {
        $method = new \ReflectionMethod(Params::class, 'getDeliveryAddress');
        $method->setAccessible(true);

        return $method->invoke($this->params, $order);
    }

    /**
     * @return Order&MockObject
     */
    private function makeOrder(
        string $shippingMethod = self::SHIPPING_METHOD,
        string $methodCode = 'IAMN93331158034119-1'
    ): Order {
        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn('SKU-OMNIK-001');

        $methodObject = new \Magento\Framework\DataObject(['method' => $methodCode]);

        $order = $this->createMock(Order::class);
        // getShippingMethod() sem argumento -> string; com (true) -> DataObject.
        $order->method('getShippingMethod')->willReturnCallback(
            fn ($asObject = false) => $asObject ? $methodObject : $shippingMethod
        );
        $order->method('getShippingDescription')->willReturn('Correios PAC - Entrega: até 8 dias úteis');
        $order->method('getItems')->willReturn([$item]);
        $order->method('getQuoteId')->willReturn(4321);

        return $order;
    }

    private function setPrivate(string $property, object $value): void
    {
        $ref = new \ReflectionProperty(Params::class, $property);
        $ref->setAccessible(true);
        $ref->setValue($this->params, $value);
    }
}
