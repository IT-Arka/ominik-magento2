<?php
declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\Seller;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Omnik\Core\Model\Seller as Model;
use Omnik\Core\Model\ResourceModel\Seller as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
