<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Vendor;

use Exception;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class TeamVendorList extends AbstractIntegration
{
    /**
     * @param string $sellerId
     * @param int $storeId
     * @param int $offset
     * @param int $limit
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(
        string $sellerId,
        int $storeId,
        int $offset = self::OFFSET_VALUE_DEFAULT,
        int $limit = self::LIMIT_VALUE_DEFAULT
    ) {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $sellerId);
        $client->setConfig($this->config);

        return $client->getRequest(self::PATH_VENDOR_TEAM_LIST . "?offset=" . $offset . "&limit=" . $limit);
    }
}
