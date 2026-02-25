<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Vendor;

use Exception;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class VendorByCode extends AbstractIntegration
{
    /**
     * @param string $sellerId
     * @param string $code
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(string $sellerId, string $code, int $storeId)
    {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $sellerId);
        $client->setConfig($this->config);

        return $client->getRequest(self::PATH_VENDOR_BY_CODE . $code);
    }
}
