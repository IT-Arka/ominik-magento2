<?php

namespace Omnik\Core\Model\Integration\Brand;

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
    public function execute(int $offset = self::OFFSET_VALUE_DEFAULT, int $limit = self::LIMIT_VALUE_DEFAULT): mixed
    {
        $client = $this->getClient();

        return $client->getNewRequest(self::INTEGRATION_BRAND_PATH . "?offset=" . $offset . "&limit=" . $limit);
    }
}
