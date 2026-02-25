<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\NotifyOmnik;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Omnik\Core\Model\ResourceModel\NotifyOmnik as NotifyOmnikResourceModel;
use Omnik\Core\Model\NotifyOmnik;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init(NotifyOmnik::class, NotifyOmnikResourceModel::class);
    }

}
