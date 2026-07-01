<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Consumer\Stock;

use Exception;
use Omnik\Core\Model\Service\Product;
use Omnik\Core\Api\Data\Stock\StockQueueInterface;
use Omnik\Core\Logger\Logger;
use Magento\Framework\Exception\NoSuchEntityException;

class UpdateStock
{
    /**
     * @var Product
     */
    private Product $productService;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param Product $productService
     * @param Logger $logger
     */
    public function __construct(
        Product $productService,
        Logger $logger
    ) {
        $this->productService = $productService;
        $this->logger = $logger;
    }

    /**
     * @param StockQueueInterface $stockQueue
     * @return void
     */
    public function processMessage(StockQueueInterface $stockQueue)
    {
        try {
            $sku = $this->productService->getSkuProduct($stockQueue->getResourceId());

            if (is_null($sku)) {
                // Permanent failure: the product genuinely does not exist. Log and ack
                // (discard) — requeueing would loop forever on a message that can't succeed.
                throw new NoSuchEntityException(
                    __('Product not found: SKU_ID_OMNIK - %1', $stockQueue->getResourceId())
                );
            }

            $this->productService->updateQtyProductBySku($sku, $stockQueue->getStock());
        } catch (NoSuchEntityException $e) {
            $this->logger->error('UPDATE/STOCK PERMANENT ERROR (discarded): ' . $e->getMessage());
        } catch (Exception $e) {
            // Transient failure (DB lock, save error, connection): rethrow so the queue
            // framework requeues the message instead of silently dropping the stock update.
            $this->logger->error('UPDATE/STOCK TRANSIENT ERROR (requeued): ' . $e->getMessage());
            throw $e;
        }
    }
}
