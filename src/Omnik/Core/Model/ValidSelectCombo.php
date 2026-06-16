<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\ValidSelectComboInterface;
use Omnik\Core\Helper\Config as ConfigHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request;

class ValidSelectCombo implements ValidSelectComboInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepositoryInterface;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Request $request
     */
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $_configHelper;

    public function __construct(
        ProductRepositoryInterface $productRepositoryInterface,
        Request $request,
        ConfigHelper $configHelper
    ) {
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->request = $request;
        $this->_configHelper = $configHelper;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(): bool
    {
        $productId = (int) $this->request->getBodyParams()['productId'];
        $sellerId = (int) $this->request->getBodyParams()['sellerId'];
        $swatchId = (int) $this->request->getBodyParams()['swatchId'];

        $product = $this->productRepositoryInterface->getById($productId);
        $products = $product->getTypeInstance()->getUsedProducts($product);

        $sellerAttr    = $this->_configHelper->getAttrVariantSeller();
        $embalagemAttr = $this->_configHelper->getAttrVariantEmbalagem();
        foreach ($products as $children) {
            $seller = (int)($children->getCustomAttribute($sellerAttr)?->getValue() ?? 0);
            $swatch = (int)($children->getCustomAttribute($embalagemAttr)?->getValue() ?? 0);
        }

        return true;
    }
}
