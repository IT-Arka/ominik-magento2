<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Shopper;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class GetByDocument extends AbstractIntegration
{
    /**
     * @param string $document
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(string $document, int $storeId)
    {
        $client = $this->getClient($storeId);
        $client->setConfig($this->config);

        return $client->getRequest(self::PATH_GET_BY_DOCUMENT . $document);
    }

    /**
     * @param array $data
     * @param int $storeId
     * @return bool
     * @throws Exception
     */
    public function hasDocument(array $data, int $storeId): bool
    {
        $document = preg_replace("/\D/", "", $data['vat_tax_id']);
        $result = $this->execute($document, $storeId);

        if (isset($result['fails']) && $result['fails']) {
            return false;
        }

        return true;
    }
}
