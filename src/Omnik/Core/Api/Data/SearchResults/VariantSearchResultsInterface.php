<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data\SearchResults;

use Omnik\Core\Api\Data\VariantInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface VariantSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return VariantInterface[]
     */
    public function getItems();

    /**
     * @param VariantInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
