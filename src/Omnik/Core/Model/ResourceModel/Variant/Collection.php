<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\Variant;

use Omnik\Core\Model\Variant as VariantModel;
use Omnik\Core\Model\ResourceModel\Variant as VariantResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(VariantModel::class, VariantResourceModel::class);
    }
}
