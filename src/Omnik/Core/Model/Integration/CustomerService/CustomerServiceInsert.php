<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\CustomerService;

use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class CustomerServiceInsert extends AbstractIntegration
{

    /**
     * @param string $params
     * @param string $sellerTenant
     * @param int $storeId
     * @return array|mixed|true[]|null
     * @throws \Exception
     */
    public function execute(string $params, string $sellerTenant, int $storeId)
    {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $sellerTenant);
        $client->setConfig($this->config);
        return $client->postRequest(self::PATH_CUSTOMER_SERVICES, $params, true);
    }
}
