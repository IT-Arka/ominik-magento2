<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Order;

use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class SendStatusNew extends AbstractIntegration
{

    /**
     * @param string $params
     * @param string $sellerTenant
     * @param int $storeId
     * @return array|mixed|true[]|null
     * @throws \Exception
     */
    public function execute(string $params, string $sellerTenant, int $storeId)
    {
        $client = $this->getClient($storeId);
        // O Client e o seu Config são compartilhados por todas as integrações;
        // a cotação de frete do mesmo request deixa a URL de frete gravada ali.
        // O pedido precisa sempre partir da URL de pedidos (HUBSAN no sandbox).
        $client->getConfig()->set(ConfigInterface::PARAM_URL, $this->getUrl($storeId));

        return $client->postRequest(self::PATH_ORDERS_STATUS_NEW, $params, true);
    }
}
