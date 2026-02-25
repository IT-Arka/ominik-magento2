<?php

namespace Omnik\Core\Model\Integration\CustomerService;

use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class Subjects extends AbstractIntegration
{
    /**
     * @param string $sellerTenant
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws \Exception
     */
    public function execute(string $sellerTenant, int $storeId)
    {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $sellerTenant);
        $client->setConfig($this->config);
        return $client->getRequest(self::PATH_CUSTOMER_SERVICES_SUBJECTS);
    }
}
