<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\ValidSwatchInterface;
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
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Request $request
    ) {
        $this->productRepository = $productRepository;
        $this->request = $request;
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

        foreach ($products as $product) {
            $seller = (int) $product->getCustomAttribute("variant_seller")->getValue();
            $swatch = (int) $product->getCustomAttribute("variant_embalagem")->getValue();
        }

        return false;
    }

}
