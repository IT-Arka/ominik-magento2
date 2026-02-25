<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Order;

use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class SendStatus extends AbstractIntegration
{

    /**
     * @param array $params
     * @param string $sellerTenant
     * @param int $storeId
     * @param string $marketplaceid
     * @param $isApproved
     * @return array|mixed|true[]|null
     * @throws \Exception
     */
    public function execute(array $params, string $sellerTenant, int $storeId, string $marketplaceid, $isApproved)
    {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $sellerTenant);
        $client->setConfig($this->config);

        $path = $this->getPathEndpoint($marketplaceid, $isApproved);
        $additionalHeaders[ConfigInterface::PARAM_SELLER] = $sellerTenant;

        return $client->putRequest($path, $params, true, $additionalHeaders);
    }

    /**
     * @param string $marketplaceid
     * @param $isApproved
     * @return string
     */
    private function getPathEndpoint(string $marketplaceid, $isApproved)
    {
        $path = self::PATH_ORDERS_STATUS_NOTAPPROVED;
        if ($isApproved) {
            $path = self::PATH_ORDERS_STATUS_APPROVED;
        }

        return str_replace('{{marketplaceId}}', $marketplaceid, $path);
    }
}
