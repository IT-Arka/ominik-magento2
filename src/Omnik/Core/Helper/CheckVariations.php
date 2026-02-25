<?php

namespace Omnik\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\Context;

class CheckVariations extends AbstractHelper
{
    /**
     * @var Product
     */
    private Product $product;

    /**
     * @param Product $product
     * @param Context $context
     */
    public function __construct(
        Product $product,
        Context $context
    ) {
        $this->product = $product;
        parent::__construct($context);
    }


    public function getNumberVariations($productId)
    {
        $configProduct = $this->product->load($productId);
        $children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);

        return count($children) >  2 ? true : false ;
    }
}



