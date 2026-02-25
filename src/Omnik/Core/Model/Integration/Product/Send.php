<?php

namespace Omnik\Core\Model\Integration\Product;

use Exception;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class Send extends AbstractIntegration
{
    /**
     * @param string $params
     * @return array|bool[]|mixed|null
     * @throws Exception
     */
    public function execute(string $params)
    {
        $client = $this->getClient();

        $this->config->set(ConfigInterface::PARAM_URL, $this->getUrlCatalog());
        $client->setConfig($this->config);

        return $client->postRequest(self::INTEGRATION_PRODUCT_PATH, $params);
    }
}
