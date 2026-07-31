<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Omnik\Core\Model\Config\Configurable\ProductsOptions;
use Omnik\Core\Model\QuoteHandler;
use Omnik\Core\Model\ShippingAmount;
use Omnik\Core\Model\SplitOrderPayment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a regra de pagamento dos pedidos filho do split.
 *
 * Regra: o filho é SEMPRE offline (splitorder). A cobrança acontece uma única vez,
 * no pedido pai. O método do pai nunca pode vazar para o filho — nem no checkout
 * headless (GraphQL, que passa $payment) nem no Luma (que não passa).
 *
 * @covers \Omnik\Core\Model\QuoteHandler::setPaymentMethod
 */
class QuoteHandlerPaymentTest extends TestCase
{
    private const PARENT_METHOD = 'pagarme_creditcard';

    /** @var CheckoutSession&MockObject */
    private CheckoutSession $checkoutSession;
    /** @var ProductsOptions&MockObject */
    private ProductsOptions $productsOptions;
    /** @var ShippingAmount&MockObject */
    private ShippingAmount $shippingAmount;

    /** @var QuoteHandler */
    private QuoteHandler $quoteHandler;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->productsOptions = $this->createMock(ProductsOptions::class);
        $this->shippingAmount  = $this->createMock(ShippingAmount::class);

        $this->quoteHandler = new QuoteHandler(
            $this->checkoutSession,
            $this->productsOptions,
            $this->shippingAmount
        );
    }

    /**
     * Fluxo headless (PWA/GraphQL): o placeOrder recebe o objeto de pagamento
     * preenchido. Era este o caso que quebrava — o filho nascia com
     * pagarme_creditcard e o gateway recusava ("método não está disponível").
     */
    public function testChildAlwaysUsesSplitOrderMethodWhenPaymentIsProvided(): void
    {
        // Arrange
        $payment = $this->createMock(PaymentInterface::class);
        $quotePayment = $this->createQuotePaymentMock();
        $split = $this->createSplitMock($quotePayment);

        // Assert: o método aplicado é splitorder, nunca o do pai.
        $quotePayment->expects($this->once())
            ->method('setMethod')
            ->with(SplitOrderPayment::METHOD);

        // Act
        $result = $this->quoteHandler->setPaymentMethod($split, self::PARENT_METHOD, $payment);

        // Assert
        $this->assertSame($this->quoteHandler, $result);
    }

    /**
     * Fluxo Luma: o placeOrder é chamado sem o segundo parâmetro. O comportamento
     * observável do filho tem de ser idêntico ao headless — este teste é a garantia
     * de não-regressão para os clientes que rodam o módulo no Luma.
     */
    public function testChildAlwaysUsesSplitOrderMethodWhenPaymentIsNull(): void
    {
        // Arrange
        $quotePayment = $this->createQuotePaymentMock();
        $split = $this->createSplitMock($quotePayment);

        // Assert
        $quotePayment->expects($this->once())
            ->method('setMethod')
            ->with(SplitOrderPayment::METHOD);

        // Act
        $result = $this->quoteHandler->setPaymentMethod($split, self::PARENT_METHOD, null);

        // Assert
        $this->assertSame($this->quoteHandler, $result);
    }

    /**
     * Os dados do cartão do pai não podem ser importados para o quote filho: é o
     * importData que dispara a validação isAvailable() do gateway contra um quote
     * recém-criado, e é também o que criaria risco de dupla cobrança.
     */
    public function testChildNeverImportsParentPaymentData(): void
    {
        // Arrange: getData não existe em PaymentInterface (vem do DataObject),
        // por isso precisa de addMethods() em vez de onlyMethods().
        $payment = $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMockForAbstractClass();
        $payment->expects($this->never())->method('getData');

        $quotePayment = $this->createQuotePaymentMock();
        $quotePayment->expects($this->never())->method('importData');
        $quotePayment->expects($this->never())->method('setQuote');

        $split = $this->createSplitMock($quotePayment);

        // Act
        $this->quoteHandler->setPaymentMethod($split, self::PARENT_METHOD, $payment);
    }

    /**
     * Blindagem contra regressão: qualquer método de gateway informado como método do
     * pai continua sendo ignorado para o filho.
     *
     * @dataProvider parentPaymentMethodProvider
     */
    public function testParentMethodIsNeverPropagatedToChild(string $parentMethod): void
    {
        // Arrange
        $quotePayment = $this->createQuotePaymentMock();
        $split = $this->createSplitMock($quotePayment);

        // Assert
        $quotePayment->expects($this->once())
            ->method('setMethod')
            ->with($this->identicalTo(SplitOrderPayment::METHOD));

        // Act
        $this->quoteHandler->setPaymentMethod($split, $parentMethod, null);
    }

    /**
     * @return array<string,array{0:string}>
     */
    public static function parentPaymentMethodProvider(): array
    {
        return [
            'pagarme cartao'  => ['pagarme_creditcard'],
            'pagarme boleto'  => ['pagarme_billet'],
            'pagarme pix'     => ['pagarme_pix'],
            'checkmo offline' => ['checkmo'],
        ];
    }

    /**
     * @return QuotePayment&MockObject
     */
    private function createQuotePaymentMock(): QuotePayment
    {
        return $this->getMockBuilder(QuotePayment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setMethod', 'setQuote', 'importData'])
            ->getMock();
    }

    /**
     * @param QuotePayment&MockObject $quotePayment
     * @return Quote&MockObject
     */
    private function createSplitMock(QuotePayment $quotePayment): Quote
    {
        $split = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPayment'])
            ->getMock();
        $split->method('getPayment')->willReturn($quotePayment);

        return $split;
    }
}
