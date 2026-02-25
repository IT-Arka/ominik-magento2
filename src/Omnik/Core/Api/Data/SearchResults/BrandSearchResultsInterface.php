<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data\SearchResults;

use Omnik\Core\Api\Data\BrandInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface BrandSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return BrandInterface[]
     */
    public function getItems();

    /**
     * @param BrandInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
