<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class NotifyOmnik extends AbstractDb
{

    public function _construct()
    {
        $this->_init(\Omnik\Core\Model\NotifyOmnik::TABLE_NAME, 'entity_id');
    }

}
