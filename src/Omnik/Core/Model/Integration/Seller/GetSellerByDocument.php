<?php

namespace Omnik\Core\Model\Integration\Seller;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class GetSellerByDocument extends AbstractIntegration
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
        // $client->setConfig($this->config);

        return $client->getNewRequest(self::INTEGRATION_SELLER_PATH . "/" . $document);
    }
}
