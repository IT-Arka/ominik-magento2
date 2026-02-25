<?php

namespace Omnik\Core\Model\Integration\Seller;

use Exception;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class ZipcodeRange extends AbstractIntegration
{
    /**
     * @param string $sellerId
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(string $sellerId, int $storeId)
    {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $sellerId);
        $client->setConfig($this->config);

        return $client->getRequest(self::INTEGRATION_SELLER_ZIPCODE_PATH);
    }
}
