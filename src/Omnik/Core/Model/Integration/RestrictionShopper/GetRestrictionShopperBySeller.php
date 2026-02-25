<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to https://www.omnik.com.br/ for more information.
 *
 * @Agency    Omnik Formação e Consultoria, Inc. (http://www.omnik.com.br)
 * @author    Danilo Cavalcanti <danilo.moura@omnik.com.br>
 */

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\RestrictionShopper;

use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\AbstractIntegration;

class GetRestrictionShopperBySeller extends AbstractIntegration
{
    /**
     * @param string $seller
     * @param int $storeId
     * @return array|bool[]|mixed|string|null
     * @throws \Exception
     */
    public function execute(string $seller, int $storeId)
    {
        $client = $this->getClient($storeId);
        $this->config->set(ConfigInterface::PARAM_SELLER, $seller);
        $client->setConfig($this->config);

        return $client->getRequest(self::PATH_GET_RESTRICTION_SHOPPER);
    }
}
