<?php

namespace Omnik\Core\Model\Integration\Product;

use Exception;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class Publish extends AbstractIntegration
{
    /**
     * @param array $params
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute(array $params, int $storeId)
    {
        $client = $this->getClient($storeId);

        // O publishResult usa a URL de catálogo (https://api.omnik.io/v1/catalog), não a base
        // /HUB. Sem isto a URL virava .../HUB/v1/catalog/products/publishResult → HTTP 503.
        //
        // O Client (e seu Config) é compartilhado por DI entre todos os endpoints, então
        // sobrescrever a URL permanentemente vazaria para as chamadas seguintes (GET moderated
        // virava /v1/catalog/v1/products/... → 404). Salvamos a URL atual, trocamos só para
        // esta requisição e RESTAURAMOS no finally.
        $config = $client->getConfig();
        $previousUrl = $config->get(ConfigInterface::PARAM_URL);
        $config->set(ConfigInterface::PARAM_URL, $this->getUrlCatalog($storeId));

        try {
            return $client->putRequest(self::PATH_PRODUCT_PUBLISH_CATALOG, $params);
        } finally {
            $config->set(ConfigInterface::PARAM_URL, $previousUrl);
        }
    }
}
