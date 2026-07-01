<?php

declare(strict_types=1);

namespace Omnik\Core\Helper\Quote;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Omnik\Core\Helper\Config as ConfigHelper;

class Data extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly Product $product,
        private readonly StoreManagerInterface $storeManager,
        private readonly ConfigHelper $configHelper
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
        $storeId      = (int)$this->storeManager->getStore()->getId();
        $tenantAttr   = $this->configHelper->getAttrTenant($storeId);
        $tenantValue  = (string)($product->getCustomAttribute($tenantAttr)?->getValue() ?? '');

        $height = (float)($product->getCustomAttribute('height')?->getValue() ?? 1);
        $length = (float)($product->getCustomAttribute('lenght')?->getValue() ?? 1);
        $width  = (float)($product->getCustomAttribute('width')?->getValue() ?? 1);
        $weight = (float)($product->getWeight() ?? 1);

        $stockItem = $product->getExtensionAttributes()->getStockItem();

        $sku = $this->extractSellerSku($product->getSku(), $tenantValue, $storeId);

        return [
            'skuId'        => $sku,
            'sellerTenant' => $tenantValue,
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
     * Extrai o SKU do seller (skuSeller da Omnik) a partir do SKU Magento.
     *
     * O SKU Magento é montado como `{storeId}_{skuSeller}-{document}`
     * (ver CreateProduct::getSimpleSku), onde `document` é o tenant sem o
     * prefixo de operador de 4 caracteres (ex.: IAMN/OMPX). A API de frete
     * espera apenas o `skuSeller`, sem o prefixo de loja nem o sufixo do tenant.
     *
     * @param string $magentoSku
     * @param string $tenantValue Valor completo do atributo tenant (com prefixo).
     * @param int $storeId
     * @return string
     */
    private function extractSellerSku(string $magentoSku, string $tenantValue, int $storeId): string
    {
        $sku = str_replace($storeId . "_", "", $magentoSku);

        $document = strlen($tenantValue) > 4 ? substr($tenantValue, 4) : $tenantValue;
        if ($document !== '') {
            $sku = preg_replace('/-' . preg_quote($document, '/') . '$/', '', $sku);
        }

        return $sku;
    }

    /**
     * @param $simpleProduct
     * @return string
     */
    public function getSellerName($simpleProduct)
    {
        $attrCode   = $this->configHelper->getAttrVariantSeller();
        $optionText = '';
        $attr       = $simpleProduct->getResource()->getAttribute($attrCode);
        if ($attr && $attr->usesSource()) {
            $optionText = $attr->getSource()->getOptionText($simpleProduct->getData($attrCode));
        }

        return $optionText;
    }
}
