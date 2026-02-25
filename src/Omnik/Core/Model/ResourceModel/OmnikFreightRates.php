<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel;

use Omnik\Core\Api\Data\OmnikFreightRatesInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class OmnikFreightRates
 */
class OmnikFreightRates extends AbstractDb
{
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init(OmnikFreightRatesInterface::TABLE_NAME, 'entity_id');
    }
}
