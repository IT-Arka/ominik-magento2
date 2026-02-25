<?php

namespace Omnik\Core\Model\Integration\Product;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class GetProductsModerated extends AbstractIntegration
{
    /**
     * @param string $productId
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(string $productId, int $storeId)
    {
        $client = $this->getClient($storeId);
        // $client->setConfig($this->config);

        return $client->getNewRequest(self::PATH_PRODUCT_MODERATED . $productId);
    }
}
