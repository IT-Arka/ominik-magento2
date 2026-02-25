<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface NotifyOmnikSearchResultsInterface extends SearchResultsInterface
{

    /**
     * @return \Magento\Framework\Api\ExtensibleDataInterface[]
     */
    public function getItems();

    /**
     * @param array $items
     * @return NotifyOmnikSearchResultsInterface
     */
    public function setItems(array $items);
}
