<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Magento\Framework\Api\SearchResults;
use Omnik\Core\Api\Data\NotifyOmnikSearchResultsInterface;

class NotifyOmnikSearchResults extends SearchResults implements NotifyOmnikSearchResultsInterface
{

}
