<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data\SearchResults;

use Magento\Framework\Api\SearchResultsInterface;

interface OmnikFreightRatesResultsInterface extends SearchResultsInterface
{
    public function getItems();
    public function setItems(array $items);
}
