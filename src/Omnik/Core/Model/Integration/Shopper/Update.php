<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Shopper;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class Update extends AbstractIntegration
{
    /**
     * @param array $params
     * @param array $data
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(array $params, array $data, int $storeId)
    {
        $client = $this->getClient($storeId);
        $client->setConfig($this->config);
        $document = preg_replace("/\D/", "", $data['vat_tax_id']);

        return $client->putRequest(self::PATH_UPDATE_BY_DOCUMENT . $document, $params);
    }
}
