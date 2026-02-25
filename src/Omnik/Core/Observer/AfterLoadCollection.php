<?php

namespace Omnik\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AfterLoadCollection
 *
 * @package Omnik\CustomCard\Observer
 */
class AfterLoadCollection implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $collection = $observer->getEvent()->getCollection();

        // if (empty($collection->getData())) {
        //     return $collection;
        // }

        $collection->getSelect()
            ->join(
                'cataloginventory_stock_item',
                'e.entity_id = cataloginventory_stock_item.product_id',
                ['min_sale_qty', 'qty_increments', 'max_sale_qty']
            );

        return $collection;
    }
}
