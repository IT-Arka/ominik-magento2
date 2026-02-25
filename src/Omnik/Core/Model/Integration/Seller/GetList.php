<?php

namespace Omnik\Core\Model\Integration\Seller;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class GetList extends AbstractIntegration
{
    /**
     * @param int $offset
     * @param int $limit
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(int $offset = self::OFFSET_VALUE_DEFAULT, int $limit = self::LIMIT_VALUE_DEFAULT)
    {
        $client = $this->getClient();
        $client->setConfig($this->config);

        return $client->getRequest(self::INTEGRATION_SELLER_PATH . "?offset=" . $offset . "&limit=" . $limit);
    }
}
