<?php

namespace Omnik\Core\Controller\Order;

use Magento\Framework\App\Action;
use Magento\Framework\Registry;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Sales\Controller\Order\Reorder as ReorderCore;
use Magento\Sales\Controller\OrderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Helper\Reorder as ReorderHelper;
use Magento\Sales\Model\Reorder\Reorder as SalesReorder;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;


class Reorder extends ReorderCore implements OrderInterface
{
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @param Action\Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param Registry $registry
     * @param ReorderHelper|null $reorderHelper
     * @param SalesReorder|null $reorder
     * @param CheckoutSession|null $checkoutSession
     */
    public function __construct(
        Action\Context       $context,
        OrderLoaderInterface $orderLoader,
        Registry             $registry,
        CheckoutSession      $checkoutSession,
        ReorderHelper        $reorderHelper = null,
        SalesReorder         $reorder = null
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $orderLoader, $registry, $reorderHelper, $reorder, $checkoutSession);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     */
    public function execute()
    {
        if ($this->checkoutSession->getQuoteId()) {
            $quote = $this->checkoutSession->getQuote();
            $quoteItems = $quote->getAllVisibleItems();
            foreach ($quoteItems as $item) {
                $item->delete();
            }
        }
        return parent::execute();
    }
}
