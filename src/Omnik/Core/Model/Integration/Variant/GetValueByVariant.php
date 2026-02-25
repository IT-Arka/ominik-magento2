<?php

namespace Omnik\Core\Model\Integration\Variant;

use Exception;
use Omnik\Core\Model\AbstractIntegration;

class GetValueByVariant extends AbstractIntegration
{
    /**
     * @param $variant
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function execute($variant)
    {
        $client = $this->getClient();

        return $client->getNewRequest(self::INTEGRATION_VARIANTS_PATH . "/" . $variant . "/values");
    }
}
