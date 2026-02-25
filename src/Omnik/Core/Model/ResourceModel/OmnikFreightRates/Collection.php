<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\OmnikFreightRates;

use Omnik\Core\Model\OmnikFreightRates as OmnikFreightRatesModel;
use Omnik\Core\Model\ResourceModel\OmnikFreightRates as OmnikFreightRatesResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init(OmnikFreightRatesModel::class, OmnikFreightRatesResourceModel::class);
    }
}
