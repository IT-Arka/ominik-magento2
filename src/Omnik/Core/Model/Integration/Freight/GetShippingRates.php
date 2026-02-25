<?php

namespace Omnik\Core\Model\Integration\Freight;

use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class GetShippingRates extends AbstractIntegration
{
    /**
     * @param string $params
     * @return array|mixed|true[]|null
     * @throws \Exception
     */
    public function execute(string $params)
    {
        $client = $this->getClient();
        $client->getConfig()->set(ConfigInterface::PARAM_URL, $this->getUrlFreight());
        return $client->postRequest(self::PATH_FREIGHT_ALLSELLERS, $params);
    }
}
