<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\ValidSwatchInterface;
use Omnik\Core\Helper\Config as ConfigHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request;

class ValidSwatch implements ValidSwatchInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param Request $request
     */
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $_configHelper;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        Request $request,
        ConfigHelper $configHelper
    ) {
        $this->productRepository = $productRepository;
        $this->request = $request;
        $this->_configHelper = $configHelper;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(): bool
    {
        $productId = (int) $this->request->getBodyParams()['productId'];
        $sellerId = (int) $this->request->getBodyParams()['sellerId'];
        $swatchId = (int) $this->request->getBodyParams()['swatchId'];

        $product = $this->productRepository->getById($productId);
        $products = $product->getTypeInstance()->getUsedProducts($product);

        $sellerAttr   = $this->_configHelper->getAttrVariantSeller();
        $embalagemAttr = $this->_configHelper->getAttrVariantEmbalagem();
        foreach ($products as $product) {
            $seller = (int)($product->getCustomAttribute($sellerAttr)?->getValue() ?? 0);
            $swatch = (int)($product->getCustomAttribute($embalagemAttr)?->getValue() ?? 0);
        }

        return false;
    }

}
