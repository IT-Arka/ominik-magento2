<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Shopper;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class Send extends AbstractIntegration
{
    /**
     * @param string $params
     * @param int $storeId
     * @return array|bool[]|mixed|null
     * @throws Exception
     */
    public function execute(string $params, int $storeId)
    {
        $client = $this->getClient($storeId);
        $client->setConfig($this->config);

        return $client->postRequest(self::PATH, $params);
    }
}
