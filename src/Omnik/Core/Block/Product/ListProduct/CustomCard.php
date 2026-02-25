<?php declare(strict_types=1);
/** Copyright © Omnik. All rights reserved. */

namespace Omnik\Core\Block\Product\ListProduct;

use Exception;
use Omnik\Core\Helper\SuperAttributes;
//use Omnik\Core\Helper\SellerRule;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url\Helper\Data;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\WishlistFactory;
use Psr\Log\LoggerInterface;

/**
 * class created to centralize business rule
 */
class CustomCard extends ListProduct
{
    /**
     * @var string
     */
    protected $_template = 'Omnik_CustomCard::product/list/item.phtml';

    /**
     * @param Context $context
     * @param PostHelper $postDataHelper
     * @param Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Data $urlHelper
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param WishlistFactory $wishlistFactory
     * @param SessionFactory $customerSessionFactory
     * @param SuperAttributes $superAttributes
     * @param SellerRule $sellerRule
     */
    public function __construct(
        Context                                     $context,
        PostHelper                                  $postDataHelper,
        Resolver                                    $layerResolver,
        CategoryRepositoryInterface                 $categoryRepository,
        Data                                        $urlHelper,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface            $logger,
        private readonly WishlistFactory            $wishlistFactory,
        private readonly SessionFactory             $customerSessionFactory,
        private readonly SuperAttributes            $superAttributes,
//        private readonly SellerRule                 $sellerRule
    ) {
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper);
    }

    /**
     * @return string
     */
    public function getJsConfigJson(): string
    {
        return json_encode($this->getData('js_config'));
    }

    /**
     * @param string $sky
     * @return array
     */
    public function getValidators(array $stockData): array
    {
        return [
            'required-number' => true,
            'validate-greater-than-zero' => true,
            'validate-item-quantity' => [
                'minAllowed' => 0,
                'maxAllowed' => $stockData['max_qty'] ?? 5
            ]
        ];
    }

    /**
     * @param string $sku
     * @return array
     */
    public function getStockData($_product)
    {
        $stockData = ['max_qty' => 1, 'min_qty' => 1, 'step' => 1, 'default' => 1];

        try {
            $qty = (float)$_product->getData('qty_increments');
            $stockItem = $_product->getExtensionAttributes()->getStockItem();

            if (!$stockItem) {
                $_product->load($_product->getId());
                $stockItem = $_product->getExtensionAttributes()->getStockItem();
            }

            if ($qty == 0 && $stockItem) {
                $qty = (float)$stockItem->getData('qty_increments');
            }

            $stockData = [
                "default" => 1,
                'min_qty' => $_product->getData('min_sale_qty') ?? $stockItem->getData('min_sale_qty'),
                'max_qty' => $_product->getMaxSaleQty() ?? $stockItem->getMaxSaleQty(),
                'step' => number_format($qty > 0 ? $qty : 1, 2)
            ];

        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $stockData;
    }

    /**
     * @param string $sku
     * @return array
     */
    public function getStockDataLoadAttribute(string $sku): array
    {
        $stockData = ['max_qty' => 1, 'min_qty' => 1, 'step' => 1, 'default' => 1];

        try {
            $product = $this->productRepository->get($sku);
            $stockItem = $product->getExtensionAttributes()->getStockItem();
            $qty = (float)$stockItem->getData('qty_increments');

            if ($stockItem) {
                $stockData = [
                    "default" => 1,
                    'min_qty' => $stockItem->getData('min_sale_qty'),
                    'max_qty' => $stockItem->getMaxSaleQty(),
                    'step' => number_format($qty > 0 ? $qty : 1, 2)
                ];
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $stockData;
    }

    /**
     * @param string $sku
     * @return float
     */
    public function getWeightProduct(string $sku): float
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return (float)$product->getWeight() ?? 0;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getModelProduct(string $id): ProductInterface
    {
        return $this->productRepository->getById($id);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function isWeightSalable(string $id): bool
    {
        $product = $this->getModelProduct($id);
        return (bool)$product->getData('weight_salable');
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getWishistItem($productId)
    {
        $customerSession = $this->customerSessionFactory->create();
        if (!$customerSession->isLoggedIn()) {
            return null;
        }

        $wishlist = $this->wishlistFactory->create();
        $wish = $wishlist->loadByCustomerId($customerSession->getId());

        if ($wish->getItemsCount()) {
            /** @var Item $item */
            foreach ($wish->getItemCollection() as $item) {
                if ($item->getProductId() == $productId) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * @param $configProduct
     * @return mixed|null
     */
    public function getSimpleProductByCustomer($configProduct)
    {
        $sellerId = $this->sellerRule->execute();
        if (!$sellerId) {
            return $configProduct;
        }

        $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
        $value = 0;
        $product = null;
        $first = true;
        foreach ($_children as $child) {

            if ($child->getVariantSeller() == $sellerId) {
                if ($child->getSpecialPrice() > 0) {
                    if ($first) {
                        $first = false;
                        $value = $child->getSpecialPrice();
                        $product = $child;
                        continue;
                    }
                    if ($child->getSpecialPrice() > 0 && $child->getSpecialPrice() <= $value) {
                        $value = $child->getSpecialPrice();
                        $product = $child;
                    }
                }
            }

        }

        return $product ?? $configProduct;
    }

    /**
     * @param $product
     * @param $attribute
     * @return mixed
     */
    public function getAttributeId($product, $attribute)
    {
        return $product->getResource()->getAttribute($attribute)->getAttributeId();
    }

    /**
     * @param string $productConfigUrl
     * @param $simpleProduct
     * @param array $superAttributes
     * @return string
     */
    public function getUrlWithAttributes(string $productConfigUrl, $simpleProduct, array $superAttributes): string
    {
        if (empty($superAttributes)) {
            return $productConfigUrl;
        }

        $productUrl = $productConfigUrl . "?home_redirect=1#";

        $params = '';
        $i = 0;
        foreach ($superAttributes as $key => $superAttribute) {
            if ($i != 0) {
                $params .= '&';
            }
            $params .= $key . "=" . $simpleProduct->getData($superAttribute);
            $i++;
        }

        return $productUrl . $params;
    }

    /**
     * @param $product
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSuperAttributes($product)
    {
        return $this->superAttributes->getSuperAttributeData($product->getId());
    }
}
