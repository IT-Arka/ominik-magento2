<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Omnik\Core\Api\ProductSellerInterface;
use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Order\SendStatusNew;
use Omnik\Core\Model\Integration\Params;
use Omnik\Core\Observer\IntegrationSalesOmnik;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Omnik\Core\Observer\IntegrationSalesOmnik
 */
class IntegrationSalesOmnikTest extends TestCase
{
    /** @var Params&MockObject */
    private Params $params;
    /** @var SendStatusNew&MockObject */
    private SendStatusNew $sendStatusNew;
    /** @var ProductRepositoryInterface&MockObject */
    private ProductRepositoryInterface $productRepository;
    /** @var Logger&MockObject */
    private Logger $salesLogger;
    /** @var ConfigHelper&MockObject */
    private ConfigHelper $configHelper;

    private IntegrationSalesOmnik $observer;

    protected function setUp(): void
    {
        $this->params            = $this->createMock(Params::class);
        $this->sendStatusNew     = $this->createMock(SendStatusNew::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->salesLogger       = $this->createMock(Logger::class);
        $this->configHelper      = $this->createMock(ConfigHelper::class);

        $this->configHelper->method('getAttrTenant')->willReturn('tenant');
        $this->params->method('createParameters')->willReturn('{}');

        $this->observer = new IntegrationSalesOmnik(
            $this->params,
            $this->sendStatusNew,
            $this->createMock(ProductSellerInterface::class),
            $this->productRepository,
            $this->salesLogger,
            $this->createMock(StoreManagerInterface::class),
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(SearchCriteriaBuilder::class),
            $this->configHelper
        );
    }

    private function dispatch(array $orders): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('orders')->willReturn($orders);

        $observerArg = $this->createMock(Observer::class);
        $observerArg->method('getEvent')->willReturn($event);

        $this->observer->execute($observerArg);
    }

    public function testEmptyOrdersIsANoop(): void
    {
        $this->sendStatusNew->expects($this->never())->method('execute');

        $this->dispatch([]);
    }

    /**
     * The core regression: a split dispatch where the FIRST child has a product
     * missing the tenant attribute (getTenant throws) must still integrate the
     * healthy sibling. A single try/catch around the whole loop regressed this.
     */
    public function testFailingChildDoesNotAbortHealthySibling(): void
    {
        // getHasIntegratedOmnik / setHasIntegratedOmnik / getSplitOrderParentId are
        // Magento magic getters (AbstractModel::__call), invisible to a plain mock —
        // they must be registered via addMethods().
        $badItem = $this->createMock(\Magento\Sales\Model\Order\Item::class);
        $badItem->method('getData')->willReturn(['sku' => 'BAD-SKU']);
        $badOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->addMethods(['getHasIntegratedOmnik', 'getSplitOrderParentId'])
            ->onlyMethods(['getStoreId', 'getIncrementId', 'getItems'])
            ->getMock();
        $badOrder->method('getHasIntegratedOmnik')->willReturn(0);
        $badOrder->method('getStoreId')->willReturn(1);
        $badOrder->method('getIncrementId')->willReturn('000000070-1');
        $badOrder->method('getSplitOrderParentId')->willReturn('55');
        $badOrder->method('getItems')->willReturn([$badItem]);

        $goodItem = $this->createMock(\Magento\Sales\Model\Order\Item::class);
        $goodItem->method('getData')->willReturn(['sku' => 'GOOD-SKU']);
        $goodOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->addMethods(['getHasIntegratedOmnik', 'setHasIntegratedOmnik'])
            ->onlyMethods(['getStoreId', 'getIncrementId', 'getItems', 'save'])
            ->getMock();
        $goodOrder->method('getHasIntegratedOmnik')->willReturn(0);
        $goodOrder->method('getStoreId')->willReturn(1);
        $goodOrder->method('getIncrementId')->willReturn('000000070-2');
        $goodOrder->method('getItems')->willReturn([$goodItem]);
        $goodOrder->method('setHasIntegratedOmnik')->willReturnSelf();
        $goodOrder->method('save')->willReturnSelf();

        // Product resolver: BAD-SKU -> tenant missing (throws); GOOD-SKU -> valid.
        $noTenantProduct = $this->createMock(ProductInterface::class);
        $noTenantProduct->method('getCustomAttribute')->with('tenant')->willReturn(null);

        $tenantAttr = $this->createMock(AttributeInterface::class);
        $tenantAttr->method('getValue')->willReturn('seller-tenant-xyz');
        $validProduct = $this->createMock(ProductInterface::class);
        $validProduct->method('getCustomAttribute')->with('tenant')->willReturn($tenantAttr);

        $this->productRepository->method('get')->willReturnCallback(
            function (string $sku) use ($noTenantProduct, $validProduct) {
                return $sku === 'GOOD-SKU' ? $validProduct : $noTenantProduct;
            }
        );

        // The healthy sibling must reach sendStatusNew with the resolved tenant.
        $this->sendStatusNew->expects($this->once())
            ->method('execute')
            ->with('{}', 'seller-tenant-xyz', 1)
            ->willReturn(['orderData' => ['id' => 'omnik-order-2']]);

        // The failing child must be logged (not swallowed silently).
        $this->salesLogger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('000000070-1'));

        $this->dispatch([$badOrder, $goodOrder]);
    }
}
