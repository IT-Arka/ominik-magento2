<?php

namespace Omnik\Core\Block;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogWidget\Block\Product\ProductsList as ProductsListCatalog;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class ProductsList extends Template
{
    /**
     * @var ProductsListCatalog
     */
    protected ProductsListCatalog $productsList;
    /**
     * @var ProductFactory
     */
    protected ProductFactory $productFactory;

    /**
     * @param ProductsListCatalog $productsList
     * @param ProductFactory $productFactory
     */
    public function __construct(ProductsListCatalog $productsList, ProductFactory $productFactory)
    {
        $this->productsList = $productsList;
        $this->productFactory = $productFactory;
    }

    /**
     * Sort products to out of stock last
     * @return mixed
     */
    public function getProductList($collection)
    {
        $collection->getSelect()
            ->join(
                'cataloginventory_stock_item',
                'e.entity_id = cataloginventory_stock_item.product_id',
                ['min_sale_qty', 'qty_increments', 'max_sale_qty']
            );

        $items = [];
        $last = [];
        foreach ($collection as $item) {
            if (!$item->getData('is_salable')) {
                $last[] = $item;
                continue;
            }
            $items[] = $item;
        }
        return array_merge($items, $last);
    }

    /**
     * @param $id
     * @return Product
     */
    public function getProduct($id): Product
    {
        return $this->productFactory->create()->load($id);
    }
}
