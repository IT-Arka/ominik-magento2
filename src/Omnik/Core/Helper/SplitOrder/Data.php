<?php

declare(strict_types=1);

namespace Omnik\Core\Helper\SplitOrder;

use Omnik\Core\Model\Carrier\Method;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Model\Repositories\OmnikFreightRatesRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;

class Data extends AbstractHelper
{
    public const SHIPPING_OMNIK_PREFIX = 'omnik_';

    /**
     * @param OrderInterfaceFactory $order
     * @param ProductRepositoryInterface $productRepository
     * @param OmnikFreightRatesRepository $omnikFreightRatesRepository
     * @param Json $json
     * @param Context $context
     */
    public function __construct(
        private readonly OrderInterfaceFactory       $order,
        private readonly ProductRepositoryInterface  $productRepository,
        private readonly OmnikFreightRatesRepository $omnikFreightRatesRepository,
        private readonly Json                        $json,
        public readonly Context                      $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param Quote $quote
     * @return OrderInterface
     */
    public function getOrderParent(Quote $quote): OrderInterface
    {
        $parentOrderId = $quote->getData(SplitOrderInterface::SPLIT_ORDER_PARENT_ID);
        return $this->order->create()->load($parentOrderId);
    }

    /**
     * @param string $sku
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTenantByProductSku(string $sku): string
    {
        $tenant = '';
        $product = $this->productRepository->get($sku);
        if ($product->getId()) {
            $tenant = $product->getCustomAttribute('tenant')->getValue();
        }
        return $tenant;
    }

    /**
     * @param $shippingMethod
     * @param $tenant
     * @param $quoteId
     * @return array
     */
    public function getOmnikRateSelected($shippingMethod, $tenant, $quoteId): array
    {
        $selectedMethod = $this->getSplitMethodByTenant($shippingMethod, $tenant);

        $omnikRate = $this->omnikFreightRatesRepository->getFreightRate($quoteId, $selectedMethod, $tenant);
        if ($omnikRate->getTotalCount() == 0) {
            return [];
        }
        $currentItem = current($omnikRate->getItems());
        $rateBody = $currentItem->getBody();
        return $this->json->unserialize($rateBody);
    }

    /**
     * @param $shippingMethod
     * @param $tenant
     * @return string|void
     */
    public function getSplitMethodByTenant($shippingMethod, $tenant)
    {
        $shippingMethodClean = ltrim($shippingMethod, self::SHIPPING_OMNIK_PREFIX);
        $shippingMethods = explode('_', $shippingMethodClean);

        foreach ($shippingMethods as $method) {
            if (!str_contains($method, $tenant)) {
                continue;
            }
            $explodeMethod = explode('-', $method);
            return $explodeMethod[1];
        }

        return Method::CONTINGENCY_METHOD;
    }

    /**
     * @param string $shippingMethod
     * @param string $tenant
     * @return bool
     */
    public function isContigency(string $shippingMethod, string $tenant): bool
    {
        if ($this->getSplitMethodByTenant($shippingMethod, $tenant) == Method::CONTINGENCY_METHOD) {
            return true;
        }
        return false;
    }

    /**
     * @param string $shippingMethod
     * @return bool
     */
    public function isOmnikShipping(string $shippingMethod): bool
    {
        if (str_contains($shippingMethod, self::SHIPPING_OMNIK_PREFIX)) {
            return true;
        }
        return false;
    }

}
