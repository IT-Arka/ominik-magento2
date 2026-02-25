<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\Brand;

use Omnik\Core\Model\Brand as BrandModel;
use Omnik\Core\Model\ResourceModel\Brand as BrandResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BrandModel::class, BrandResourceModel::class);
    }
}
