<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model\Order\StatusQueue;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Integration\Sales\Approvation;
use Omnik\Core\Model\Order\StatusQueue\BackfillQueue;
use Omnik\Core\Model\Order\StatusQueue\Enqueue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a rede de segurança que recupera filhos que nunca foram enfileirados.
 *
 * Cenário real que originou a varredura: pai 000000089 aprovado, filho 000000090 recebeu
 * o PUT (virou "Pago" na Omnik) e filho 000000091 ficou em `processing` sem nenhuma linha
 * na fila — permaneceu "Novo" na Omnik indefinidamente.
 */
class BackfillQueueTest extends TestCase
{
    /** @var Enqueue&MockObject */
    private $enqueue;

    /** @var Approvation&MockObject */
    private $approvation;

    /** @var OrderRepositoryInterface&MockObject */
    private $orderRepository;

    /** @var AdapterInterface&MockObject */
    private $connection;

    private BackfillQueue $backfillQueue;

    protected function setUp(): void
    {
        $this->enqueue = $this->createMock(Enqueue::class);
        $this->approvation = $this->createMock(Approvation::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);

        $select = $this->createMock(Select::class);
        foreach (['from', 'join', 'joinLeft', 'where', 'order', 'limit'] as $method) {
            $select->method($method)->willReturnSelf();
        }

        $this->connection = $this->createMock(AdapterInterface::class);
        $this->connection->method('select')->willReturn($select);

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);
        $resourceConnection->method('getTableName')->willReturnArgument(0);

        $this->backfillQueue = new BackfillQueue(
            $resourceConnection,
            $this->orderRepository,
            $this->approvation,
            $this->enqueue,
            $this->createMock(Logger::class)
        );
    }

    /**
     * Filho órfão de um pai aprovado deve ser recuperado e enfileirado como aprovado,
     * preservando o contador de tentativas.
     */
    public function testOrphanChildOfApprovedParentIsEnqueued(): void
    {
        $this->givenCandidates([
            ['child_id' => 68, 'child_increment_id' => '000000091', 'parent_id' => 66],
        ]);

        $child = $this->makeOrder('000000091');
        $this->givenOrders([66 => $this->makeOrder('000000089'), 68 => $child]);

        $this->approvation->method('isApproved')->willReturn(true);
        $this->approvation->method('isNotApproved')->willReturn(false);

        $this->enqueue->expects($this->once())
            ->method('execute')
            ->with($child, true, true);

        $this->backfillQueue->execute();
    }

    /**
     * Pai cancelado: o filho é recuperado com is_approved = false.
     */
    public function testOrphanChildOfCanceledParentIsEnqueuedAsNotApproved(): void
    {
        $this->givenCandidates([
            ['child_id' => 68, 'child_increment_id' => '000000091', 'parent_id' => 66],
        ]);

        $child = $this->makeOrder('000000091');
        $this->givenOrders([66 => $this->makeOrder('000000089'), 68 => $child]);

        $this->approvation->method('isApproved')->willReturn(false);
        $this->approvation->method('isNotApproved')->willReturn(true);

        $this->enqueue->expects($this->once())
            ->method('execute')
            ->with($child, false, true);

        $this->backfillQueue->execute();
    }

    /**
     * Pai em estado indefinido (nem aprovado nem cancelado) não gera envio.
     */
    public function testChildOfUndecidedParentIsNotEnqueued(): void
    {
        $this->givenCandidates([
            ['child_id' => 68, 'child_increment_id' => '000000091', 'parent_id' => 66],
        ]);
        $this->givenOrders([66 => $this->makeOrder('000000089'), 68 => $this->makeOrder('000000091')]);

        $this->approvation->method('isApproved')->willReturn(false);
        $this->approvation->method('isNotApproved')->willReturn(false);

        $this->enqueue->expects($this->never())->method('execute');

        $this->backfillQueue->execute();
    }

    /**
     * A falha de um filho não pode impedir a recuperação dos demais.
     */
    public function testFailureOnOneChildDoesNotAbortTheRest(): void
    {
        $this->givenCandidates([
            ['child_id' => 68, 'child_increment_id' => '000000091', 'parent_id' => 66],
            ['child_id' => 64, 'child_increment_id' => '000000087', 'parent_id' => 61],
        ]);

        $healthy = $this->makeOrder('000000087');
        $this->orderRepository->method('get')->willReturnCallback(
            function (int $id) use ($healthy) {
                if ($id === 68) {
                    throw new \RuntimeException('pedido indisponível');
                }
                return $id === 64 ? $healthy : $this->makeOrder('parent');
            }
        );

        $this->approvation->method('isApproved')->willReturn(true);
        $this->approvation->method('isNotApproved')->willReturn(false);

        $this->enqueue->expects($this->once())
            ->method('execute')
            ->with($healthy, true, true);

        $this->backfillQueue->execute();
    }

    /**
     * Nada a recuperar: nenhuma consulta de pedido nem enfileiramento.
     */
    public function testNoCandidatesDoesNothing(): void
    {
        $this->givenCandidates([]);

        $this->orderRepository->expects($this->never())->method('get');
        $this->enqueue->expects($this->never())->method('execute');

        $this->backfillQueue->execute();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function givenCandidates(array $rows): void
    {
        $this->connection->method('fetchAll')->willReturn($rows);
    }

    /**
     * @param array<int, Order> $ordersById
     */
    private function givenOrders(array $ordersById): void
    {
        $this->orderRepository->method('get')->willReturnCallback(
            static fn (int $id) => $ordersById[$id] ?? null
        );
    }

    /**
     * @param string $incrementId
     * @return Order&MockObject
     */
    private function makeOrder(string $incrementId)
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIncrementId'])
            ->getMock();
        $order->method('getIncrementId')->willReturn($incrementId);

        return $order;
    }
}
