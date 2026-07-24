<?php

declare(strict_types=1);

namespace Omnik\Core\Plugin;

use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\HandleChildOrders;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Omnik\Core\Api\QuoteHandlerInterface;
use Omnik\Core\Api\SplitOrderInterface;

class SplitQuote
{
    /** @var CartRepositoryInterface */
    private CartRepositoryInterface $quoteRepository;

    /** @var QuoteFactory */
    private QuoteFactory $quoteFactory;

    /** @var QuoteHandlerInterface */
    private QuoteHandlerInterface $quoteHandler;

    /** @var OrderRepositoryInterface */
    private OrderRepositoryInterface $orderRepository;

    /** @var EventManager */
    private EventManager $eventManager;
    private HandleChildOrders $handleChildOrders;
    private Logger $_logger;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param QuoteHandlerInterface $quoteHandler
     * @param OrderRepositoryInterface $orderRepository
     * @param EventManager $eventManager
     * @param HandleChildOrders $handleChildOrders
     * @param Logger $logger
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteFactory $quoteFactory,
        QuoteHandlerInterface $quoteHandler,
        OrderRepositoryInterface $orderRepository,
        EventManager $eventManager,
        HandleChildOrders $handleChildOrders,
        Logger $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteHandler = $quoteHandler;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->handleChildOrders = $handleChildOrders;
        $this->_logger = $logger;
    }

    /**
     * @param QuoteManagement $subject
     * @param $result
     * @param $cartId
     * @param PaymentInterface|null $payment
     * @return mixed
     */
    public function afterPlaceOrder(
        QuoteManagement $subject,
        $result,
        $cartId,
        ?PaymentInterface $payment = null
    ) {
        if (!$result) {
            return $result;
        }

        $currentQuote = null;
        $childOrders = [];

        try {
            $currentQuote = $this->quoteRepository->get($cartId);
            $quotes = $this->getSplitedQuotes($currentQuote);
            $addresses = $this->quoteHandler->collectAddressesData($currentQuote);


            // Verificar se existem dois ou mais sellers
            if (count($quotes) < 2) {
                $orderId = $result;
                $order = $this->orderRepository->get($orderId);
                $this->eventManager->dispatch('omnik_omnik_submit_order', ['orders' => array($order)]);
                // Se houver apenas um ou nenhum seller, não realizar o split
                return $result;
            }
            
            if(!$payment) {
                $payment = $currentQuote->getPayment()->setMethod(\Omnik\Core\Model\SplitOrderPayment::METHOD);
            }

            foreach ($quotes as $seller => $items) {
                $split = $this->createSplitForQuote($currentQuote, $result);
                $split = $this->addItemsToQuote($items, $split);
                $this->quoteHandler->populateQuote($quotes, $split, $items, $addresses, $payment);

                $order = $subject->submit($split);
                if (!$order) {
                    continue;
                }

                $this->saveSplitData($order->getId(), SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD, $result);
                $childOrders[] = $order;
            }

            $this->eventManager->dispatch('omnik_omnik_submit_order', ['orders' => $childOrders]);

        } catch (\Exception $e) {
            $this->_logger->error(
                'Omnik SplitQuote (split step) failed: ' . $e->getMessage(),
                ['cart_id' => $cartId, 'order_id' => $result]
            );
        }

        try {
            $currentQuote->setIsActive(false);
            $currentQuote->setData(SplitOrderInterface::SPLIT_ORDER_TYPE, SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT);
            $this->saveQuote($currentQuote);
            $this->saveSplitData($result, SplitOrderInterface::SPLIT_ORDER_TYPE_PARENT);
            $parentOrder = $this->orderRepository->get($result);
            $this->updateChildStatus($parentOrder);
        } catch (\Exception $e) {
            $this->_logger->error(
                'Omnik SplitQuote (parent finalize) failed: ' . $e->getMessage(),
                ['cart_id' => $cartId, 'order_id' => $result]
            );
        }

        return $result;
    }

    /**
     * @param $currentQuote
     * @return array
     */
    private function getSplitedQuotes($currentQuote)
    {
        $quotes = $this->quoteHandler->normalizeQuotes($currentQuote);
        if (empty($quotes)) {
            return [];
        }

        $this->logDiscardedItems($currentQuote, $quotes);

        return array_filter(
            $quotes,
            fn ($key) => !empty($key),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Itens agrupados sob a chave vazia são descartados do split (não têm seller
     * resolvido). Historicamente isso era silencioso: um produto que não splitava
     * sumia sem rastro. Aqui o descarte é registrado em WARNING para diagnóstico.
     *
     * @param mixed $currentQuote
     * @param array $quotes
     * @return void
     */
    private function logDiscardedItems($currentQuote, array $quotes): void
    {
        if (empty($quotes[''])) {
            return;
        }

        $skus = [];
        foreach ($quotes[''] as $item) {
            $skus[] = (string)$item->getSku();
        }

        $this->_logger->warning(
            'Omnik SplitQuote: itens sem seller resolvido descartados do split: '
            . implode(', ', $skus),
            ['cart_id' => $currentQuote->getId()]
        );
    }

    /**
     * @param $currentQuote
     * @param $parentOrderId
     * @return \Magento\Quote\Model\Quote
     */
    private function createSplitForQuote($currentQuote, $parentOrderId)
    {
        $split = $this->quoteFactory->create();
        $split->setData(SplitOrderInterface::SPLIT_ORDER_TYPE, SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD);
        $split->setData(SplitOrderInterface::SPLIT_ORDER_PARENT_ID, $parentOrderId);
        $split->setSuperMode(true);
        $this->quoteHandler->setCustomerData($currentQuote, $split);
        $split->setData(SplitOrderInterface::SPLIT_ORDER_TYPE, SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD);
        $this->saveQuote($split);

        return $split;
    }

    /**
     * @param $items
     * @param $split
     * @return mixed
     */
    private function addItemsToQuote($items, $split)
    {
        foreach ($items as $item) {
            $item->setId(null);
            $split->addItem($item);
            if ($item->getProductType() === 'configurable') {
                foreach ($item->getChildren() as $child) {
                    $child->setId(null);
                    $split->addItem($child);
                }
            }
        }
        $this->saveQuote($split);

        return $split;
    }

    /**
     * @param CartInterface $quote
     * @return void
     */
    private function saveQuote(CartInterface $quote): void
    {
        $this->quoteRepository->save($quote);
    }

    /**
     * @param $orderId
     * @param $splitOrderType
     * @param $splitOrderParentId
     * @return void
     */
    private function saveSplitData($orderId, $splitOrderType, $splitOrderParentId = null): void
    {
        $order = $this->orderRepository->get($orderId);
        $order->setData(SplitOrderInterface::SPLIT_ORDER_TYPE, $splitOrderType);
        $order->setData(SplitOrderInterface::SPLIT_ORDER_PARENT_ID, $splitOrderParentId);
        $this->orderRepository->save($order);
    }

    /**
     * @param $parentOrder
     * @return void
     */
    private function updateChildStatus($parentOrder): void
    {
        $this->handleChildOrders->execute($parentOrder->getId(), $parentOrder->getStatus());
    }
}
