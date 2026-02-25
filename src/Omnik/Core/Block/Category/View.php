<?php

namespace Omnik\Core\Block\Category;

use Magento\Catalog\Block\Category\View as ViewCore;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Registry;
use Magento\Catalog\Helper\Category;


class View extends ViewCore
{
    /**
     * @param Context $context
     * @param Resolver $layerResolver
     * @param Registry $registry
     * @param Category $categoryHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context                               $context,
        Resolver                              $layerResolver,
        Registry                              $registry,
        Category                              $categoryHelper,
        private readonly ScopeConfigInterface $scopeConfig,
        array                                 $data = []
    ) {
        parent::__construct($context, $layerResolver, $registry, $categoryHelper, $data);
    }

    /**
     * @return bool|int|null
     */
    protected function getCacheLifetime()
    {
        if ($this->scopeConfig->isSetFlag('omnik_cache/category_page/enable')) {
            return null;
        }
        return parent::getCacheLifetime();
    }

}
