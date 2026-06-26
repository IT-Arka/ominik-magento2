<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Consumer\Price;

use Exception;
use Omnik\Core\Model\Service\Product;
use Omnik\Core\Api\Data\Price\PriceQueueInterface;
use Omnik\Core\Logger\Logger;
use Magento\Framework\Exception\NoSuchEntityException;

class UpdatePrice
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
     * @param PriceQueueInterface $priceQueue
     * @return void
     */
    public function processMessage(PriceQueueInterface $priceQueue)
    {
        try {
            $sku = $this->productService->getSkuProduct($priceQueue->getResourceId());

            if (is_null($sku)) {
                // Permanent failure: the product genuinely does not exist. Log and ack
                // (discard) — requeueing would loop forever on a message that can't succeed.
                throw new NoSuchEntityException(
                    __('Product not found: SKU_ID_OMNIK - %1', $priceQueue->getResourceId())
                );
            }

            $this->productService->updatePriceProductBySku($sku, $priceQueue->getFromPrice(), $priceQueue->getPrice());
        } catch (NoSuchEntityException $e) {
            $this->logger->error('UPDATE/PRICE PERMANENT ERROR (discarded): ' . $e->getMessage());
        } catch (Exception $e) {
            // Transient failure (DB lock, save error, connection): rethrow so the queue
            // framework requeues the message instead of silently dropping the price update.
            $this->logger->error('UPDATE/PRICE TRANSIENT ERROR (requeued): ' . $e->getMessage());
            throw $e;
        }
    }
}
