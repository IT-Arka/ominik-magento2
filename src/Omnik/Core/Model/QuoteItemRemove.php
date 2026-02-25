<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\Data\QuoteItemRemoveInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Omnik\Core\Logger\Logger;

class QuoteItemRemove implements QuoteItemRemoveInterface
{
     /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * Constructor
     *
     * @param CartRepositoryInterface $cartRepository
     * @param Logger $logger
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        Logger $logger
    ) {
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
    }

    /**
     * Remove an item from the quote
     *
     * @param int $cartId
     * @param int $itemId
     * @return void
     * @throws NoSuchEntityException
     */
    public function removeItemFromQuote($cartId, $itemId):void
    {
        try {
            // Load the quote
            $quote = $this->cartRepository->get($cartId);

            // Get the quote items
            $items = $quote->getAllVisibleItems();

            // Remove the specific item
            foreach ($items as $key => $item) {
                if ($item->getItemId() == $itemId) {
                    unset($items[$key]);
                    break;
                }
            }

            // Set the updated items
            $quote->setItems($items);

            // Save the quote
            $this->cartRepository->save($quote);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Erro ao remover item do quote: quote_id {$quote->getId()} erro: ' . $e->getMessage());
        }
    }

}
