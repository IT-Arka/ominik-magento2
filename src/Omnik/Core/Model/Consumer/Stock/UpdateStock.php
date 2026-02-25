<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Consumer\Stock;

use Exception;
use Omnik\Core\Model\Service\Product;
use Omnik\Core\Api\Data\Stock\StockQueueInterface;
use Omnik\Core\Logger\Logger;

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
                $msg = 'Product not found: SKU_ID_OMNIK - ' . $stockQueue->getResourceId();
                throw new Exception($msg);
            }

            $this->productService->updateQtyProductBySku($sku, $stockQueue->getStock());
        } catch (Exception $e) {
            $this->logger->error('UPDATE/STOCK ERROR: ' . $e->getMessage());
        }
    }
}
