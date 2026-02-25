<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\ValidSelectComboInterface;

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
    public function __construct(
        ProductRepositoryInterface $productRepositoryInterface,
        Request $request
    ) {
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->request = $request;
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

        foreach ($products as $children) {
            $seller = (int) $children->getCustomAttribute("variant_seller")->getValue();
            $swatch = (int) $children->getCustomAttribute("variant_embalagem")->getValue();
        }

        return true;
    }
}
