<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\HandleChildOrders;
use Omnik\Core\Model\Integration\Params;
use Omnik\Core\Model\Integration\Sales\Approvation;
use Omnik\Core\Model\Order\ChildOrderPayment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a propagação de status do pedido pai para os filhos.
 *
 * Regra central: só é enviado à Omnik o status de um filho que já existe lá, o que é
 * determinado pela flag persistida `has_integrated_omnik` — não mais por um GET HTTP,
 * cuja falha de transporte era indistinguível de "não existe" e pulava o envio.
 */
class HandleChildOrdersTest extends TestCase
{
    /** @var ChildOrderPayment&MockObject */
    private $childOrderPayment;

    /** @var Approvation&MockObject */
    private $approvation;

    /** @var OrderRepositoryInterface&MockObject */
    private $orderRepository;

    /** @var Order&MockObject */
    private $parentOrder;

    private HandleChildOrders $handleChildOrders;

    protected function setUp(): void
    {
        $this->childOrderPayment = $this->createMock(ChildOrderPayment::class);
        $this->approvation = $this->createMock(Approvation::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);

        $payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMethod'])
            ->getMock();
        $payment->method('getMethod')->willReturn('pagarme');

        $this->parentOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getPayment'])
            ->getMock();
        $this->parentOrder->method('load')->willReturnSelf();
        $this->parentOrder->method('getPayment')->willReturn($payment);

        $orderFactory = $this->createMock(OrderFactory::class);
        $orderFactory->method('create')->willReturn($this->parentOrder);

        $filterBuilder = $this->createMock(FilterBuilder::class);
        $filterBuilder->method('setField')->willReturnSelf();
        $filterBuilder->method('setConditionType')->willReturnSelf();
        $filterBuilder->method('setValue')->willReturnSelf();

        $filterGroup = $this->createMock(FilterGroup::class);
        $filterGroup->method('setFilters')->willReturnSelf();

        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->method('setFilterGroups')->willReturnSelf();
        $searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        $this->handleChildOrders = new HandleChildOrders(
            $searchCriteriaBuilder,
            $filterBuilder,
            $filterGroup,
            $this->orderRepository,
            $this->childOrderPayment,
            $this->approvation,
            $this->createMock(ProductRepositoryInterface::class),
            $orderFactory,
            $this->createMock(ConfigHelper::class),
            $this->createMock(Logger::class)
        );
    }

    /**
     * Filho já integrado na Omnik: pai aprovado deve faturar e enviar o status.
     */
    public function testIntegratedChildIsInvoicedAndStatusIsSent(): void
    {
        $child = $this->createChild(true);
        $this->givenChildren([$child]);
        $this->approvation->method('isApproved')->willReturn(true);
        $this->approvation->method('isNotApproved')->willReturn(false);

        $this->childOrderPayment->expects($this->once())->method('invoice')->with($child);
        $this->approvation->expects($this->once())->method('integrate')->with($child);

        $this->handleChildOrders->execute(61, 'processing', true);
    }

    /**
     * Filho ainda não integrado: nada é enviado à Omnik (não existe lá para receber status).
     * O faturamento no Magento continua acontecendo.
     */
    public function testNotIntegratedChildDoesNotSendStatus(): void
    {
        $child = $this->createChild(false);
        $this->givenChildren([$child]);
        $this->approvation->method('isApproved')->willReturn(true);
        $this->approvation->method('isNotApproved')->willReturn(false);

        $this->childOrderPayment->expects($this->once())->method('invoice')->with($child);
        $this->approvation->expects($this->never())->method('integrate');

        $this->handleChildOrders->execute(61, 'processing', true);
    }

    /**
     * A falha de um filho não pode abortar o processamento dos irmãos.
     */
    public function testFailureOnOneChildDoesNotAbortSiblings(): void
    {
        $failing = $this->createChild(true, '000000085');
        $healthy = $this->createChild(true, '000000086');
        $this->givenChildren([$failing, $healthy]);

        $this->approvation->method('isApproved')->willReturn(true);
        $this->approvation->method('isNotApproved')->willReturn(false);

        $this->childOrderPayment->method('invoice')
            ->willReturnCallback(function ($order): bool {
                if ($order->getIncrementId() === '000000085') {
                    throw new \Exception('falha simulada no faturamento');
                }
                return true;
            });

        $integrated = [];
        $this->approvation->method('integrate')
            ->willReturnCallback(function ($order) use (&$integrated): void {
                $integrated[] = $order->getIncrementId();
            });

        $this->handleChildOrders->execute(61, 'processing', true);

        $this->assertSame(['000000086'], $integrated, 'O irmão saudável deve seguir sendo processado.');
    }

    /**
     * Pai cancelado: filho integrado é cancelado e o not-approved é enviado.
     */
    public function testCanceledParentCancelsChildAndSendsStatus(): void
    {
        $child = $this->createChild(true);
        $this->givenChildren([$child]);
        $this->approvation->method('isApproved')->willReturn(false);
        $this->approvation->method('isNotApproved')->willReturn(true);

        $this->childOrderPayment->expects($this->once())->method('cancel')->with($child);
        $this->approvation->expects($this->once())->method('integrate')->with($child);

        $this->handleChildOrders->execute(61, 'canceled', true);
    }

    /**
     * @param Order[] $children
     */
    private function givenChildren(array $children): void
    {
        $searchResult = $this->createMock(OrderSearchResultInterface::class);
        $searchResult->method('getItems')->willReturn($children);
        $this->orderRepository->method('getList')->willReturn($searchResult);
    }

    /**
     * @param bool $isIntegrated
     * @param string $incrementId
     * @return Order&MockObject
     */
    private function createChild(bool $isIntegrated, string $incrementId = '000000085')
    {
        $child = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getIncrementId'])
            ->getMock();

        $child->method('getData')->willReturnCallback(
            static fn ($key) => $key === SplitOrderInterface::SPLIT_ORDER_HAS_INTEGRATED
                ? ($isIntegrated ? 1 : 0)
                : null
        );
        $child->method('getIncrementId')->willReturn($incrementId);

        return $child;
    }
}
