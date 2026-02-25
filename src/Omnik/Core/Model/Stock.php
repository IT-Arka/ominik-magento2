<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Magento\CatalogInventory\Api\StockRegistryInterface;

class Stock
{

    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function getStockStatus(int $productId): bool
    {
        $stockItem = $this->stockRegistry->getStockItem($productId);
        $isInStock = $stockItem ? $stockItem->getIsInStock() : false;
        return $isInStock;
    }
}
