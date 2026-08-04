<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model\Integration\Sales;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Helper\Config as ConfigHelper;
use Omnik\Core\Helper\StatusMapping as StatusMappingHelper;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Params;
use Omnik\Core\Model\Integration\Sales\Approvation;
use Omnik\Core\Model\Order\StatusQueue\Enqueue;
use Omnik\Core\Model\Order\StatusQueue\IntegratedChildOrders;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a decisão de QUEM é enfileirado para o PUT de status na Omnik.
 *
 * Regra de negócio: quem existe na Omnik é quem recebeu o POST de pedido novo.
 *  - Split  -> os filhos (um por seller); o pai nunca é enviado à Omnik.
 *  - Sem split -> o próprio pedido.
 */
class ApprovationTest extends TestCase
{
    /** @var Enqueue&MockObject */
    private $enqueue;

    /** @var IntegratedChildOrders&MockObject */
    private $childOrders;

    /** @var StatusMappingHelper&MockObject */
    private $statusMapping;

    /** @var Logger&MockObject */
    private $logger;

    private Approvation $approvation;

    protected function setUp(): void
    {
        $this->enqueue = $this->createMock(Enqueue::class);
        $this->childOrders = $this->createMock(IntegratedChildOrders::class);
        $this->statusMapping = $this->createMock(StatusMappingHelper::class);
        $this->logger = $this->createMock(Logger::class);

        // Mapeamento desativado: usa o fallback (processing = aprovado, canceled = não aprovado).
        $this->statusMapping->method('isMapEnabled')->willReturn(false);

        $this->approvation = new Approvation(
            $this->createMock(Params::class),
            $this->enqueue,
            $this->createMock(ProductRepositoryInterface::class),
            $this->logger,
            $this->statusMapping,
            $this->createMock(ConfigHelper::class),
            $this->childOrders
        );
    }

    /**
     * Cenário split: pai aprovado deve enfileirar CADA filho integrado, nunca o pai.
     */
    public function testApprovedParentEnqueuesEachIntegratedChildInsteadOfParent(): void
    {
        $parent = $this->createOrder('parent', SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT, 61, '000000084');
        $childA = $this->createOrder('childA', SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD, 62, '000000085');
        $childB = $this->createOrder('childB', SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD, 63, '000000086');

        $this->childOrders->expects($this->once())
            ->method('getIntegratedChildren')
            ->with(61)
            ->willReturn([$childA, $childB]);

        $enqueued = [];
        $this->enqueue->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function ($order, $isApproved) use (&$enqueued): void {
                $enqueued[] = [$order->getIncrementId(), $isApproved];
            });

        $this->approvation->integrate($parent);

        $this->assertSame(
            [['000000085', true], ['000000086', true]],
            $enqueued,
            'Deve enfileirar os dois filhos como aprovados, e não o pedido pai.'
        );
    }

    /**
     * Cenário sem split: o próprio pedido foi integrado na Omnik, então é ele que vai à fila.
     */
    public function testApprovedSingleOrderEnqueuesItself(): void
    {
        $order = $this->createOrder('single', '', 70, '000000090');

        $this->childOrders->expects($this->never())->method('getIntegratedChildren');

        $this->enqueue->expects($this->once())
            ->method('execute')
            ->with($order, true);

        $this->approvation->integrate($order);
    }

    /**
     * Pai sem nenhum filho integrado: não pode enfileirar o pai (travaria no gate do
     * ProcessQueue até estourar as tentativas). Deve apenas logar o erro.
     */
    public function testApprovedParentWithoutIntegratedChildrenDoesNotEnqueueParent(): void
    {
        $parent = $this->createOrder('parent', SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT, 61, '000000084');

        $this->childOrders->method('getIntegratedChildren')->with(61)->willReturn([]);

        $this->enqueue->expects($this->never())->method('execute');
        $this->logger->expects($this->once())->method('error');

        $this->approvation->integrate($parent);
    }

    /**
     * Cancelamento do pai também deve seguir os filhos, com is_approved = false.
     */
    public function testCanceledParentEnqueuesChildrenAsNotApproved(): void
    {
        $parent = $this->createOrder('parent', SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT, 61, '000000084', 'canceled');
        $child = $this->createOrder('childA', SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD, 62, '000000085');

        $this->childOrders->method('getIntegratedChildren')->with(61)->willReturn([$child]);

        $this->enqueue->expects($this->once())
            ->method('execute')
            ->with($child, false);

        $this->approvation->integrate($parent);
    }

    /**
     * Status que não é aprovado nem cancelado não gera envio algum.
     */
    public function testPendingOrderIsNotEnqueued(): void
    {
        $order = $this->createOrder('single', '', 70, '000000090', 'pending');

        $this->enqueue->expects($this->never())->method('execute');

        $this->approvation->integrate($order);
    }

    /**
     * @param string $name
     * @param string $splitType
     * @param int $entityId
     * @param string $incrementId
     * @param string $status
     * @return Order&MockObject
     */
    private function createOrder(
        string $name,
        string $splitType,
        int $entityId,
        string $incrementId,
        string $status = 'processing'
    ) {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getEntityId', 'getIncrementId', 'getStatus', 'getStatusHistories'])
            ->getMock();

        $order->method('getData')
            ->willReturnCallback(
                static fn ($key) => $key === SplitOrderInterface::SPLIT_ORDER_TYPE ? $splitType : null
            );
        $order->method('getEntityId')->willReturn($entityId);
        $order->method('getIncrementId')->willReturn($incrementId);
        $order->method('getStatus')->willReturn($status);
        $order->method('getStatusHistories')->willReturn([]);

        return $order;
    }
}
