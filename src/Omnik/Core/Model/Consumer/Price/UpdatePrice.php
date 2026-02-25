<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Consumer\Price;

use Exception;
use Omnik\Core\Model\Service\Product;
use Omnik\Core\Api\Data\Price\PriceQueueInterface;
use Omnik\Core\Logger\Logger;

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
                $msg = 'Product not found: SKU_ID_OMNIK - ' . $priceQueue->getResourceId();
                throw new Exception($msg);
            }

            $this->productService->updatePriceProductBySku($sku, $priceQueue->getFromPrice(), $priceQueue->getPrice());
        } catch (Exception $e) {
            $this->logger->error('UPDATE/PRICE ERROR: ' . $e->getMessage());
        }
    }
}
