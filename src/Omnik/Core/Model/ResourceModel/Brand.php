<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel;

use Omnik\Core\Api\Data\BrandInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Brand extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BrandInterface::TABLE_NAME, BrandInterface::ENTITY_ID);
    }
}
