<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Category;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class Send extends AbstractIntegration
{
    /**
     * @param string $params
     * @param string $tenantMarketplace
     * @param int $storeId
     * @return void
     * @throws Exception
     */
    public function execute(string $params, string $tenantMarketplace, int $storeId)
    {
        $client = $this->getClient($storeId);
        // $client->setConfig($this->config);

        return $client->postRequest(self::PATH_SEND_CATEGORIES . $tenantMarketplace, $params);
    }
}
