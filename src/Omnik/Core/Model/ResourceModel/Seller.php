<?php
declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Omnik\Core\Api\Data\SellerInterface;

class Seller extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(SellerInterface::TABLE_NAME, SellerInterface::ID);
    }
}
