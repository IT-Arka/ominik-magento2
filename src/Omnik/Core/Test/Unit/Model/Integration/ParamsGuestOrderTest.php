<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddress;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Directory\Model\Region;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Omnik\Core\Api\RegionRepositoryInterface;
use Omnik\Core\Helper\Config as IntegrationHelper;
use Omnik\Core\Helper\SplitOrder\Data as SplitHelper;
use Omnik\Core\Helper\Telephone;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Params;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Um pedido de visitante (guest) não tem customer_id. O payload carregava o
 * cliente pelo repositório para obter o telefone, `getById(null)` lançava
 * NoSuchEntityException, o catch de createParameters devolvia "" e o pedido
 * era enviado à Omnik com corpo vazio. Tudo o que o payload precisa está no
 * próprio pedido: o telefone vem do endereço de entrega (ou de cobrança).
 *
 * @covers \Omnik\Core\Model\Integration\Params
 */
class ParamsGuestOrderTest extends TestCase
{
    private const TENANT = 'IAMN93331158034119';
    private const SKU_ID_OMNIK = 'OMK-SKU-001';

    private CustomerRepositoryInterface&MockObject $customerRepository;
    private Telephone&MockObject $telephone;
    private Params $params;

    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->telephone = $this->createMock(Telephone::class);

        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->method('getStreetIndexes')
            ->willReturn(['address' => 0, 'number' => 1, 'neighborhood' => 2, 'complement' => 3]);
        $integrationHelper->method('getTenantMarketplace')->willReturn('IAMN59508075000120');
        $integrationHelper->method('getAttrTenant')->willReturn('tenant');
        $integrationHelper->method('getAttrSkuId')->willReturn('sku_id_omnik');

        $product = $this->createMock(ProductInterface::class);
        $product->method('getCustomAttribute')->willReturnCallback(function (string $code) {
            $attribute = $this->createMock(AttributeInterface::class);
            $attribute->method('getValue')->willReturn($code === 'tenant' ? self::TENANT : self::SKU_ID_OMNIK);
            return $attribute;
        });
        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $productRepository->method('get')->willReturn($product);

        $regionRepository = $this->createMock(RegionRepositoryInterface::class);
        $regionRepository->method('getById')->willReturn($this->createMock(Region::class));

        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('date')->willReturnArgument(0);

        $splitHelper = $this->createMock(SplitHelper::class);
        $splitHelper->method('isOmnikShipping')->willReturn(false);

        $reflection = new \ReflectionClass(Params::class);
        $this->params = $reflection->newInstanceWithoutConstructor();

        $this->setPrivate('customerRepository', $this->customerRepository);
        $this->setPrivate('productRepositoryInterface', $productRepository);
        $this->setPrivate('json', new Json());
        $this->setPrivate('regionRepositoryInterface', $regionRepository);
        $this->setPrivate('telephone', $this->telephone);
        $this->setPrivate('integrationHelper', $integrationHelper);
        $this->setPrivate('logger', $this->createMock(Logger::class));
        $this->setPrivate('timezone', $timezone);
        $this->setPrivate('splitHelper', $splitHelper);
    }

    public function testGuestOrderBuildsPayloadWithShippingAddressTelephone(): void
    {
        // Arrange
        $order = $this->makeOrder(customerId: null, shippingTelephone: '11999999999', billingTelephone: '11777777777');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->telephone->expects($this->once())
            ->method('getTelephoneFormattedIntegration')
            ->with('11999999999')
            ->willReturn(['ddd' => '11', 'number' => '999999999']);

        // Act
        $json = $this->params->createParameters($order);

        // Assert — payload completo, telefone do endereço de entrega.
        $this->assertNotSame('', $json);
        $payload = json_decode($json, true);
        $this->assertSame(self::TENANT, $payload['tenant']);
        $this->assertSame('000000123', $payload['marketplaceData']['marketPlaceId']);
        $this->assertSame('Guest Buyer', $payload['customerData']['name']);
        $this->assertSame('11', $payload['customerData']['phones'][0]['ddd']);
        $this->assertSame('999999999', $payload['customerData']['phones'][0]['number']);
        $this->assertSame(self::SKU_ID_OMNIK, $payload['items'][0]['skuData']['id']);
    }

    public function testGuestOrderFallsBackToBillingTelephoneWhenShippingHasNone(): void
    {
        // Arrange
        $order = $this->makeOrder(customerId: null, shippingTelephone: '', billingTelephone: '11777777777');
        $this->telephone->expects($this->once())
            ->method('getTelephoneFormattedIntegration')
            ->with('11777777777')
            ->willReturn(['ddd' => '11', 'number' => '777777777']);

        // Act
        $json = $this->params->createParameters($order);

        // Assert
        $this->assertNotSame('', $json);
        $this->assertSame('777777777', json_decode($json, true)['customerData']['phones'][0]['number']);
    }

    public function testRegisteredCustomerStillUsesCustomerAddressTelephone(): void
    {
        // Arrange — cliente logado mantém o comportamento anterior.
        $order = $this->makeOrder(customerId: 42, shippingTelephone: '11999999999', billingTelephone: '11777777777');

        $customerAddress = $this->createMock(CustomerAddress::class);
        $customerAddress->method('getTelephone')->willReturn('11888888888');
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getAddresses')->willReturn([$customerAddress]);
        $this->customerRepository->expects($this->once())->method('getById')->with(42)->willReturn($customer);

        $this->telephone->expects($this->once())
            ->method('getTelephoneFormattedIntegration')
            ->with('11888888888')
            ->willReturn(['ddd' => '11', 'number' => '888888888']);

        // Act
        $json = $this->params->createParameters($order);

        // Assert
        $this->assertNotSame('', $json);
        $this->assertSame('888888888', json_decode($json, true)['customerData']['phones'][0]['number']);
    }

    /**
     * @return Order&MockObject
     */
    private function makeOrder(?int $customerId, string $shippingTelephone, string $billingTelephone): Order
    {
        $shipping = $this->makeAddress('shipping', $shippingTelephone);
        $billing = $this->makeAddress('billing', $billingTelephone);

        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn('SKU-OMNIK-001');
        $item->method('getPrice')->willReturn(90.0);
        $item->method('getDiscountAmount')->willReturn(0.0);
        $item->method('getQtyOrdered')->willReturn(1.0);

        $method = $this->createMock(MethodInterface::class);
        $method->method('getCode')->willReturn('pagarme_creditcard');
        $payment = $this->createMock(Payment::class);
        $payment->method('getMethodInstance')->willReturn($method);

        $methodObject = new \Magento\Framework\DataObject(['method' => 'flatrate']);

        $order = $this->createMock(Order::class);
        $order->method('getStoreId')->willReturn(1);
        $order->method('getCustomerId')->willReturn($customerId);
        $order->method('getCustomerName')->willReturn('Guest Buyer');
        $order->method('getCustomerEmail')->willReturn('guest@example.com');
        $order->method('getIncrementId')->willReturn('000000123');
        $order->method('getCreatedAt')->willReturn('2026-09-02 12:00:00');
        $order->method('getUpdatedAt')->willReturn('2026-09-02 12:00:00');
        $order->method('getShippingAddress')->willReturn($shipping);
        $order->method('getBillingAddress')->willReturn($billing);
        $order->method('getAddresses')->willReturn([$shipping, $billing]);
        $order->method('getShippingMethod')->willReturnCallback(
            fn ($asObject = false) => $asObject ? $methodObject : 'flatrate_flatrate'
        );
        $order->method('getShippingDescription')->willReturn('Flat Rate');
        $order->method('getItems')->willReturn([$item]);
        $order->method('getAllVisibleItems')->willReturn([$item]);
        $order->method('getPayment')->willReturn($payment);
        $order->method('getGrandTotal')->willReturn(100.0);
        $order->method('getSubtotal')->willReturn(90.0);
        $order->method('getDiscountAmount')->willReturn(0.0);
        $order->method('getShippingAmount')->willReturn(10.0);
        $order->method('getQuoteId')->willReturn(4321);

        return $order;
    }

    private function makeAddress(string $type, string $telephone): Address
    {
        $address = $this->createMock(Address::class);
        $address->method('getAddressType')->willReturn($type);
        $address->method('getStreet')->willReturn(['Rua das Flores', '10', 'Centro', 'ap 1']);
        $address->method('getPostcode')->willReturn('01234-567');
        $address->method('getCity')->willReturn('São Paulo');
        $address->method('getRegionId')->willReturn(30);
        $address->method('getTelephone')->willReturn($telephone);
        $address->method('getVatId')->willReturn('12345678901');

        return $address;
    }

    private function setPrivate(string $property, object $value): void
    {
        $ref = new \ReflectionProperty(Params::class, $property);
        $ref->setAccessible(true);
        $ref->setValue($this->params, $value);
    }
}
