<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Plugin;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Omnik\Core\Api\QuoteHandlerInterface;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\HandleChildOrders;
use Omnik\Core\Plugin\SplitQuote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Stub de \Magento\Quote\Model\QuoteFactory, que o Magento gera em generated/ e por
 * isso não existe no source durante o teste unitário.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
 */
class QuoteFactoryStub
{
    /**
     * @param array $data
     * @return Quote|null
     */
    public function create(array $data = [])
    {
        return null;
    }
}

// O construtor do plugin exige o tipo concreto QuoteFactory; o alias registra o stub
// sob o nome real para o teste conseguir montar o mock.
if (!class_exists(\Magento\Quote\Model\QuoteFactory::class)) {
    class_alias(QuoteFactoryStub::class, \Magento\Quote\Model\QuoteFactory::class);
}

/**
 * Cobre o isolamento por seller no split.
 *
 * Antes, o try/catch envolvia o loop inteiro: o primeiro seller que falhasse
 * abortava a criação de TODOS os filhos e o dispatch para a Omnik nunca acontecia.
 *
 * @covers \Omnik\Core\Plugin\SplitQuote::afterPlaceOrder
 */
class SplitQuoteTest extends TestCase
{
    private const CART_ID = 55;
    private const PARENT_ORDER_ID = 1001;

    /** @var CartRepositoryInterface&MockObject */
    private CartRepositoryInterface $quoteRepository;
    /**
     * Mock de \Magento\Quote\Model\QuoteFactory (classe gerada, aliasada acima).
     *
     * @var MockObject
     */
    private MockObject $quoteFactory;
    /** @var QuoteHandlerInterface&MockObject */
    private QuoteHandlerInterface $quoteHandler;
    /** @var OrderRepositoryInterface&MockObject */
    private OrderRepositoryInterface $orderRepository;
    /** @var EventManager&MockObject */
    private EventManager $eventManager;
    /** @var HandleChildOrders&MockObject */
    private HandleChildOrders $handleChildOrders;
    /** @var Logger&MockObject */
    private Logger $logger;
    /** @var QuoteManagement&MockObject */
    private QuoteManagement $subject;

    /** @var SplitQuote */
    private SplitQuote $plugin;

    protected function setUp(): void
    {
        $this->quoteRepository   = $this->createMock(CartRepositoryInterface::class);
        $this->quoteFactory      = $this->createMock(\Magento\Quote\Model\QuoteFactory::class);
        $this->quoteHandler      = $this->createMock(QuoteHandlerInterface::class);
        $this->orderRepository   = $this->createMock(OrderRepositoryInterface::class);
        $this->eventManager      = $this->createMock(EventManager::class);
        $this->handleChildOrders = $this->createMock(HandleChildOrders::class);
        $this->logger            = $this->createMock(Logger::class);
        $this->subject           = $this->createMock(QuoteManagement::class);

        $this->plugin = new SplitQuote(
            $this->quoteRepository,
            $this->quoteFactory,
            $this->quoteHandler,
            $this->orderRepository,
            $this->eventManager,
            $this->handleChildOrders,
            $this->logger
        );
    }

    /**
     * Um seller que falha não pode impedir que os demais gerem pedido filho.
     */
    public function testFailingSellerDoesNotAbortRemainingSellers(): void
    {
        // Arrange: 3 sellers, o primeiro estoura no submit.
        $this->givenQuoteWithSellers(['seller_a', 'seller_b', 'seller_c']);

        $childB = $this->createOrderMock(2002);
        $childC = $this->createOrderMock(2003);

        $this->subject->method('submit')->willReturnOnConsecutiveCalls(
            $this->throwException(new \Exception('payment method not available')),
            $childB,
            $childC
        );

        $dispatched = $this->captureSubmitOrderDispatch();

        // Act
        $result = $this->plugin->afterPlaceOrder(
            $this->subject,
            self::PARENT_ORDER_ID,
            self::CART_ID,
            $this->createMock(PaymentInterface::class)
        );

        // Assert: os dois sellers saudáveis viraram filhos e foram despachados.
        $this->assertSame(self::PARENT_ORDER_ID, $result);
        $this->assertCount(2, $dispatched->orders, 'sellers saudáveis devem gerar filhos');
    }

    /**
     * A falha de um seller precisa ser rastreável, com o seller identificado.
     */
    public function testFailingSellerIsLoggedWithSellerContext(): void
    {
        // Arrange
        $this->givenQuoteWithSellers(['seller_a', 'seller_b']);

        $this->subject->method('submit')->willReturnOnConsecutiveCalls(
            $this->throwException(new \Exception('boom')),
            $this->createOrderMock(2002)
        );

        // Assert
        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with(
                $this->stringContains('seller_a'),
                $this->arrayHasKey('seller')
            );

        // Act
        $this->plugin->afterPlaceOrder(
            $this->subject,
            self::PARENT_ORDER_ID,
            self::CART_ID,
            null
        );
    }

    /**
     * Se todos os sellers falharem, não faz sentido despachar uma lista vazia para a
     * Omnik — o caso precisa ser registrado como erro.
     */
    public function testNoDispatchWhenEveryChildFails(): void
    {
        // Arrange
        $this->givenQuoteWithSellers(['seller_a', 'seller_b']);
        $this->subject->method('submit')->willThrowException(new \Exception('boom'));

        // Assert: nenhum dispatch de submit_order com filhos.
        $this->eventManager->expects($this->never())
            ->method('dispatch')
            ->with('omnik_omnik_submit_order', $this->anything());

        // Act
        $this->plugin->afterPlaceOrder(
            $this->subject,
            self::PARENT_ORDER_ID,
            self::CART_ID,
            null
        );
    }

    /**
     * Carrinho de seller único não deve splitar (não-regressão).
     */
    public function testSingleSellerDoesNotSplit(): void
    {
        // Arrange
        $this->givenQuoteWithSellers(['seller_a']);

        // Assert: nenhum filho é submetido.
        $this->subject->expects($this->never())->method('submit');

        // Act
        $result = $this->plugin->afterPlaceOrder(
            $this->subject,
            self::PARENT_ORDER_ID,
            self::CART_ID,
            null
        );

        // Assert
        $this->assertSame(self::PARENT_ORDER_ID, $result);
    }

    /**
     * Monta o quote pai com N sellers já normalizados pelo QuoteHandler.
     *
     * @param string[] $sellers
     */
    private function givenQuoteWithSellers(array $sellers): void
    {
        $quotes = [];
        foreach ($sellers as $seller) {
            $item = $this->getMockBuilder(Quote\Item::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getSku', 'getProductType', 'setId'])
                ->getMock();
            $item->method('getSku')->willReturn('SKU-' . $seller);
            $item->method('getProductType')->willReturn('simple');
            $quotes[$seller] = [$item];
        }

        $currentQuote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'setIsActive', 'setData', 'getPayment'])
            ->getMock();
        $currentQuote->method('getId')->willReturn(self::CART_ID);

        $this->quoteRepository->method('get')->willReturn($currentQuote);
        // saveSplitData()/updateChildStatus() recarregam o pedido pelo repositório.
        $this->orderRepository->method('get')->willReturn($this->createOrderMock(self::PARENT_ORDER_ID));
        $this->quoteHandler->method('normalizeQuotes')->willReturn($quotes);
        $this->quoteHandler->method('collectAddressesData')->willReturn([
            'payment'  => 'pagarme_creditcard',
            'billing'  => [],
            'shipping' => [],
        ]);

        // setSuperMode é setter mágico do DataObject -> addMethods(); os demais são reais.
        $splitQuote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'addItem', 'getId'])
            ->addMethods(['setSuperMode'])
            ->getMock();
        $this->quoteFactory->method('create')->willReturn($splitQuote);
    }

    /**
     * Captura os pedidos enviados no dispatch de omnik_omnik_submit_order.
     */
    private function captureSubmitOrderDispatch(): object
    {
        $captured = new class {
            /** @var array<int,mixed> */
            public array $orders = [];
        };

        $this->eventManager->method('dispatch')->willReturnCallback(
            function (string $eventName, array $data = []) use ($captured) {
                if ($eventName === 'omnik_omnik_submit_order') {
                    $captured->orders = $data['orders'] ?? [];
                }
                return null;
            }
        );

        return $captured;
    }

    /**
     * @return Order&MockObject
     */
    private function createOrderMock(int $id): Order
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getStatus', 'setData'])
            ->getMock();
        $order->method('getId')->willReturn($id);
        $order->method('getStatus')->willReturn('pending');

        return $order;
    }
}
