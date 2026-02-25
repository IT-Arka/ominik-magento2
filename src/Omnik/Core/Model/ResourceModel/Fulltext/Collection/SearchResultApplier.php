<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to https://www.omnik.com.br/ for more information.
 *
 * @Agency    Omnik Formação e Consultoria, Inc. (http://www.omnik.com.br)
 * @author    Danilo Cavalcanti <danilo.moura@omnik.com.br>
 */

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\Fulltext\Collection;

use Magento\CatalogInventory\Model\ResourceModel\StockStatusFilterInterface;
use Magento\CatalogInventory\Model\StockStatusApplierInterface;
use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplier as MagentoSearchResultApplier;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\EntityManager\MetadataPool;

class SearchResultApplier extends MagentoSearchResultApplier
{
    /**
     * @var int
     */
    private int $size;

    /**
     * @var int
     */
    private int $currentPage;

    /**
     * @var SearchResultInterface
     */
    private SearchResultInterface  $searchResult;

    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * @param Collection $collection
     * @param SearchResultInterface $searchResult
     * @param int $size
     * @param int $currentPage
     * @param ScopeConfigInterface|null $scopeConfig
     * @param MetadataPool|null $metadataPool
     * @param StockStatusFilterInterface|null $stockStatusFilter
     * @param StockStatusApplierInterface|null $stockStatusApplier
     */
    public function __construct(
        Collection $collection,
        SearchResultInterface $searchResult,
        int $size,
        int $currentPage,
        ?ScopeConfigInterface $scopeConfig = null,
        ?MetadataPool $metadataPool = null,
        ?StockStatusFilterInterface $stockStatusFilter = null,
        ?StockStatusApplierInterface $stockStatusApplier = null
    ) {
        $this->searchResult = $searchResult;
        $this->collection = $collection;
        $this->size = $size;
        $this->currentPage = $currentPage;

        parent::__construct(
            $collection,
            $searchResult,
            $size,
            $currentPage,
            $scopeConfig,
            $metadataPool,
            $stockStatusFilter,
            $stockStatusApplier
        );
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        if (empty($this->searchResult->getItems())) {
            $this->collection->getSelect()->where('NULL');
            return;
        }

        $ids = [];

        $items = $this->sliceItems($this->searchResult->getItems(), $this->size, $this->currentPage);
        foreach ($items as $item) {
            $ids[] = (int)$item->getId();
        }

        $orderList = implode(',', $ids);
        $this->collection->getSelect()
            ->where('e.entity_id IN (?)', $ids)
            ->reset(\Magento\Framework\DB\Select::ORDER)
            ->order(new \Zend_Db_Expr("FIELD(e.entity_id,$orderList)"));
    }

    /**
     * Slice current items
     *
     * @param array $items
     * @param int $size
     * @param int $currentPage
     * @return array
     */
    private function sliceItems(array $items, int $size, int $currentPage): array
    {
        if ($size !== 0) {
            // Check that current page is in a range of allowed page numbers, based on items count and items per page,
            // than calculate offset for slicing items array.
            $itemsCount = count($items);
            $maxAllowedPageNumber = ceil($itemsCount/$size);
            if ($currentPage < 1) {
                $currentPage = 1;
            }
            if ($currentPage > $maxAllowedPageNumber) {
                $currentPage = $maxAllowedPageNumber;
            }

            $offset = $this->getOffset($currentPage, $size);
            $items = array_slice($items, $offset, $size);
        }

        return $items;
    }

    /**
     * Get offset for given page.
     *
     * @param int|float $pageNumber
     * @param int $pageSize
     * @return int
     */
    private function getOffset(int|float $pageNumber, int $pageSize): int
    {
        $pageNumber = (int) $pageNumber;
        return ($pageNumber - 1) * $pageSize;
    }
}
