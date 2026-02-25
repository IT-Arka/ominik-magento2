<?php
declare(strict_types=1);

namespace Omnik\Core\Api\Data\SearchResults;

use Magento\Framework\Api\SearchResultsInterface;

interface SellerSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Omnik\Core\Api\Data\SellerInterface[]
     */
    public function getItems();

    /**
     * @param \Omnik\Core\Api\Data\SellerInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
