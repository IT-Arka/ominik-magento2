<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Commercial;

use Exception;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class GetCommercial extends AbstractIntegration
{
    /**
     * @param string $sellerId
     * @param string $classificationCode
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(string $sellerId, string $classificationCode, int $storeId)
    {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $sellerId);
        $client->setConfig($this->config);

        return $client->getRequest(self::GET_COMMERCIAL_CLASSIFICATION . $classificationCode);
    }
}
