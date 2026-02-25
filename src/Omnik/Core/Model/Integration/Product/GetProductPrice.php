<?php

namespace Omnik\Core\Model\Integration\Product;

use Exception;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class GetProductPrice extends AbstractIntegration
{
    /**
     * @param string $resourceId
     * @param string $sellerId
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(string $resourceId, string $sellerId, int $storeId)
    {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $sellerId);
        $client->setConfig($this->config);

        return $client->getRequest(self::PATH_PRODUCT_SKUS . "/" . $resourceId . "/price");
    }
}
