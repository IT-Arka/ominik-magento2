<?php

namespace Omnik\Core\Controller\Shipping;

use Omnik\Core\Helper\ProductPostcode;
use Omnik\Core\Model\Integration\Freight\GetShippingRates;
use Omnik\Core\Helper\Checkout\Data as CheckoutHelper;
use Omnik\Core\Helper\Catalog\Data as CatalogHelper;
use Omnik\Core\Helper\Quote\Data as QuoteHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Freight extends Action
{
    /**
     * @param Context $context
     * @param CheckoutHelper $checkoutHelper
     * @param CatalogHelper $catalogHelper
     * @param Configurable $configurable
     * @param LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param ProductPostcode $productPostcode
     * @param ResultFactory $result
     * @param GetShippingRates $getShippingRates
     * @param QuoteHelper $quoteHelper
     * @param Json $json
     */
    public function __construct(
        public readonly Context                     $context,
        private readonly CheckoutHelper             $checkoutHelper,
        private readonly CatalogHelper              $catalogHelper,
        private readonly Configurable               $configurable,
        private readonly LoggerInterface            $logger,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductPostcode            $productPostcode,
        private readonly ResultFactory              $result,
        private readonly GetShippingRates           $getShippingRates,
        private readonly QuoteHelper                $quoteHelper,
        private readonly Json                       $json
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|(Json&ResultInterface)|ResultInterface|void
     */
    public function execute()
    {
        try {
            $rawBody = $this->json->unserialize($this->getRequest()->getContent());
            $configProductId = $rawBody['product_id'] ?? '';
            $postcode = $rawBody['zipcode'] ?? '';
            $qty = $rawBody['qty'] ?? 1;

            if (empty($postcode)) {
                return $this->result->create(ResultFactory::TYPE_JSON)->setData(
                    [
                        'freight' => __('Zipcode not found'),
                        'box' => ''
                    ]
                );
            }

            if (empty($rawBody['productOptions'])) {
                return $this->result->create(ResultFactory::TYPE_JSON)->setData(
                    [
                        'freight' => __('Product attributes not found'),
                        'box' => ''
                    ]
                );
            }
            $optionValues = $this->checkoutHelper->getSuperAttributeValues($rawBody['productOptions']);

            $this->productPostcode->setProductPostcode($postcode);
            $configProduct = $this->productRepository->getById($configProductId);
            if (!$configProduct) {
                return $this->result->create(ResultFactory::TYPE_JSON)->setData(
                    [
                        'freight' => $freightBox ?? __('Freight not found'),
                        'box' => ''
                    ]
                );
            }

            $simpleProduct = $this->configurable->getProductByAttributes($optionValues, $configProduct);
            if (!$simpleProduct) {
                return $this->result->create(ResultFactory::TYPE_JSON)->setData(
                    [
                        'freight' => $freightBox ?? __('Product attributes not found'),
                        'box' => ''
                    ]
                );
            }

            $freightBox = $this->getFreightBox($simpleProduct, $postcode, $qty);
            $responseFreight = $this->getRequestFreight($simpleProduct, $postcode, $qty);

            if (isset($responseFreight['content'])) {
                usort($responseFreight['content'], function ($deliveryOptionA, $deliveryOptionB) {
                    return strcmp($deliveryOptionA['finalShippingCost'], $deliveryOptionB['finalShippingCost']);
                });
            }

            //$box = $this->catalogHelper->getBuybox($configProduct, $simpleProduct, $responseFreight);
            $box = '';

            return $this->result->create(ResultFactory::TYPE_JSON)->setData(
                [
                    'freight' => $freightBox ?? __('Freight not found'),
                    'box' => $box
                ]
            );
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * @param $simpleProduct
     * @param $postcode
     * @param $qty
     * @return string
     * @throws \Exception
     */
    public function getFreightBox($simpleProduct, $postcode, $qty)
    {
        $response = $this->getRequestFreight($simpleProduct, $postcode, $qty);
        $freight = '';
        foreach ($response['content'] as $key => $content) {
            $deliveryOptions = $content['deliveryOptions'];

            usort($deliveryOptions, function ($deliveryOptionA, $deliveryOptionB) {
                return strcmp($deliveryOptionA['finalShippingCost'], $deliveryOptionB['finalShippingCost']);
            });

            foreach ($deliveryOptions as $keyDelivery => $contentDelivery) {
                $freight .= '<li>' . $contentDelivery['description'] . '<br>'
                    . '<span class="days">' . $contentDelivery['deliveryTime'] . ' dias úteis |</span>'
                    . '<span class="price">R$ ' . $this->catalogHelper->formatCost($contentDelivery['finalShippingCost']) . '</span></li>';
            }
        }

        return $freight;
    }

    /**
     * @param $simpleProduct
     * @param $postcode
     * @param $qty
     * @return array|mixed|true[]|null
     * @throws NoSuchEntityException
     */
    public function getRequestFreight($simpleProduct, $postcode, $qty)
    {
        $requestData = $this->quoteHelper->getRequestDataByProduct($simpleProduct, $postcode, $qty);
        $response = $this->getShippingRates->execute($this->json->serialize($requestData));
        if (!empty($response) && isset($response['content']) && !empty($response['content'])) {
            return $response;
        }
        return null;
    }
}
