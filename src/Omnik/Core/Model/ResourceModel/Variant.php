<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel;

use Omnik\Core\Api\Data\VariantInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Variant extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(VariantInterface::TABLE_NAME, VariantInterface::ENTITY_ID);
    }
}
