<?php

declare(strict_types=1);

namespace Omnik\Core\Helper\Quote;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;


class Data extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly Product $product,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * @param Product $product
     * @param string $postcode
     * @param int $qty
     * @return array
     * @throws NoSuchEntityException
     */
    public function getRequestDataByProduct(Product $product, string $postcode, int $qty): array
    {
        return array(
            'destinationZipCode' => $postcode,
            'products' => [$this->getProductsInfo($product, $qty)]
        );
    }

    /**
     * @param array $itemsBySeller
     * @param string $postcode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getRequestDataByItems(array $itemsBySeller, string $postcode): array
    {
        $data = [];
        foreach ($itemsBySeller as $seller) {
            foreach ($seller as $item) {
                $data[] = $this->getProductsInfo($this->product->load($item->getProduct()->getId()), (int)$item->getQty());
            }
        }

        return array(
            'destinationZipCode' => $postcode,
            'products' => $data
        );
    }

    /**
     * @param Product $product
     * @param int $qty
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductsInfo(Product $product, int $qty): array
    {
        $storeId = $this->storeManager->getStore()->getId();
        $tenant = str_replace("OMPX", "", $product->getCustomAttribute('tenant')?->getValue());

        $height = (float)$product->getCustomAttribute('height')?->getValue() ?? 1;
        $length = (float)$product->getCustomAttribute('lenght')?->getValue() ?? 1;
        $width = (float)$product->getCustomAttribute('width')?->getValue() ?? 1;
        $weight = (float)$product->getWeight() ?? 1;

        $stockItem = $product->getExtensionAttributes()->getStockItem();

        $sku = str_replace("-" . $tenant, "", str_replace($storeId . "_", "", $product->getSku()));

        return [
            'skuId' => $sku,
            'sellerTenant' => $product->getCustomAttribute('tenant')?->getValue(),
            'stock' => $stockItem->getQty(),
            'quantity' => $qty,
            'height' => $height,
            'length' => $length,
            'width' => $width,
            'weight' => $weight,
            'cost' => $product->getFinalPrice($qty)
        ];
    }

    /**
     * @param $simpleProduct
     * @return string
     */
    public function getSellerName($simpleProduct)
    {
        $optionText = '';
        $attr = $simpleProduct->getResource()->getAttribute('variant_seller');
        if ($attr->usesSource()) {
            $optionText = $attr->getSource()->getOptionText($simpleProduct->getVariantSeller());
        }

        return $optionText;
    }
}
