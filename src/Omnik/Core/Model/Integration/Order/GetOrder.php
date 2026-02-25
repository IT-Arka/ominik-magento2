<?php

namespace Omnik\Core\Model\Integration\Order;

use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class GetOrder extends AbstractIntegration
{
    /**
     * @param string $sellerTenant
     * @param int $storeId
     * @param string $marketplaceid
     * @return array|bool[]|mixed|string|null
     * @throws \Exception
     */
    public function execute(string $sellerTenant, int $storeId, string $marketplaceid)
    {
        $path = str_replace('{{marketplaceId}}', $marketplaceid, self::PATH_ORDERS_MARKETPLACE);

        $client = $this->getClient($storeId);
        // $client->setConfig($this->config);
        // $this->config->set(ConfigInterface::PARAM_SELLER, $sellerTenant);

        $additionalHeaders[ConfigInterface::PARAM_SELLER] = $sellerTenant;

        return $client->getNewRequest($path, true, $additionalHeaders);
    }
}
