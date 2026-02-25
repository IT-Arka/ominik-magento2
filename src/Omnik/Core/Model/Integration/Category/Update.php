<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Category;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class Update extends AbstractIntegration
{
    /**
     * @param string $params
     * @param string $tenantMarketplace
     * @param int $marketplaceCategoryId
     * @param int $storeId
     * @return void
     * @throws Exception
     */
    public function execute(string $params, string $tenantMarketplace, int $marketplaceCategoryId, int $storeId)
    {
        $client = $this->getClient($storeId);
        // $client->setConfig($this->config);

        $path = self::PATH_UPDATE_CATEGORIES;
        $path = str_replace('{{marketplaceId}}', $tenantMarketplace, $path);
        $path = str_replace('{{marketplace_category_id}}', (string) $marketplaceCategoryId, $path);

        return $client->putRequest($path, $params);
    }
}
