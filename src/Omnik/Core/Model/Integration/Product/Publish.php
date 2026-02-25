<?php

namespace Omnik\Core\Model\Integration\Product;

use Exception;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class Publish extends AbstractIntegration
{
    /**
     * @param array $params
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(array $params, int $storeId)
    {
        $client = $this->getClient($storeId);

        // $this->config->set(ConfigInterface::PARAM_URL, $this->getUrlCatalog($storeId));
        // $client->setConfig($this->config);

        return $client->putRequest(self::PATH_PRODUCT_PUBLISH, $params);
    }
}
