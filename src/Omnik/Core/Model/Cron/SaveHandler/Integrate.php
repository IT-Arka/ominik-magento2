<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler;

use Exception;
use Omnik\Core\Helper\ApiResponse;
use Omnik\Core\Model\Integration\Product\GetProductsModerated;
use Omnik\Core\Api\Data\NotifyProductModerationDataInterface;
use Omnik\Core\Api\ProductHandlerInterface;
use Omnik\Core\Helper\Product\Data;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\IntegrationProduct;
use Omnik\Core\Model\Management\CreateProduct;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Integrate implements ProductHandlerInterface
{
    /**
     * @var GetProductsModerated
     */
    private GetProductsModerated $getProductsModerated;

    /**
     * @var IntegrationProduct
     */
    private IntegrationProduct $integrationProduct;

    /**
     * @var NotifyProductModerationDataInterface
     */
    private NotifyProductModerationDataInterface $notifyProductModerationData;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Data
     */
    private Data $productHelper;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var CreateProduct
     */
    private CreateProduct $createProduct;

    /**
     * @var ProductRepositoryInterface
     */
    private productRepositoryInterface $productRepository;

    /**
     * @var Configurable $configurableType
     */
    protected $configurableType;

    /**
     * @var ApiResponse
     */
    private ApiResponse $apiResponse;

    /**
     * @param GetProductsModerated $getProductsModerated
     * @param IntegrationProduct $integrationProduct
     * @param NotifyProductModerationDataInterface $notifyProductModerationData
     * @param Logger $logger
     * @param Data $productHelper
     * @param Json $json
     * @param CreateProduct $createProduct
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurableType
     */
    public function __construct(
        GetProductsModerated                 $getProductsModerated,
        IntegrationProduct                   $integrationProduct,
        NotifyProductModerationDataInterface $notifyProductModerationData,
        Logger                               $logger,
        Data                                 $productHelper,
        Json                                 $json,
        CreateProduct                        $createProduct,
        ProductRepositoryInterface           $productRepository,
        Configurable                         $configurableType,
        ApiResponse                          $apiResponse
    ) {
        $this->getProductsModerated = $getProductsModerated;
        $this->integrationProduct = $integrationProduct;
        $this->notifyProductModerationData = $notifyProductModerationData;
        $this->logger = $logger;
        $this->productHelper = $productHelper;
        $this->json = $json;
        $this->createProduct = $createProduct;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
        $this->apiResponse = $apiResponse;
    }

    /**
     * @param array $registers
     * @return void
     * @throws Exception
     */
    public function execute(array $registers): void
    {
        if (!empty($registers)) {
            // Registers arrive already claimed (status RUNNING) by the atomic claim
            // in ProductCron, so no setIsRunning() is needed here anymore.
            $simpleSkuProducts = [];
            $configurableSku = '';
            foreach ($registers as $data) {
                $simpleProductData = "";
                $storeId = (int)$data['store_id'];
                $idNotify = (int)$data['entity_id'];
                $productModeratedData = $this->getProductsModerated->execute($data['resource_id'], $storeId);

                if ($this->handleTransientFailure($productModeratedData, $idNotify)) {
                    continue;
                }

                if ($this->isInvalid($productModeratedData, $data)) {
                    continue;
                }
                try {
                    if ($configurableExists =
                        $this->createProduct->isConfigurableProductExists($productModeratedData)) {

                        if (!$this->createProduct->hasNewSimpleProducts($productModeratedData, $storeId)) {
                            $this->notifyProductModerationData->changeStatusNotify($idNotify, NotifyProductModerationDataInterface::STATUS_INTEGRATED);
                            continue;
                        }
                        $splPrd = $this->getSimpleSkuProduct($productModeratedData, $storeId);
                        $simpleProductData = $this->getProductSimple($productModeratedData, $storeId);
                        

                    }
                    
                    $this->integrationProduct->integrate($productModeratedData, $storeId, $configurableExists);
                    $this->notifyProductModerationData->changeStatusNotify($idNotify, NotifyProductModerationDataInterface::STATUS_INTEGRATED);

                    $configurableSku = $this->createProduct->getConfigurableSku($productModeratedData);
                    $configurableProduct = $this->getConfigurableProductBySku($configurableSku);

                    if ($configurableProduct === null) {
                        throw new \RuntimeException(
                            'Configurable product not found after integration. SKU: ' . $configurableSku
                        );
                    }

                    $childProducts = $this->getChildProducts($configurableProduct->getId());

                    $childProductSend = [];
                    if ($childProducts) {
                        foreach ($childProducts as $childProduct) {
                            $childProductSend = [
                                "message" => "publicado",
                                "marketplaceId" => $childProduct['sku'],
                                "skuid" => $childProduct['sku']
                            ];
                        }
                    }

                    $simpleSkuProducts = [
                        "message" => "publicado",
                        "marketplaceId" => $configurableProduct->getSku(),
                        "skuId" => $childProductSend
                    ];

                    $productData = [
                        'productId' => $productModeratedData['productData']['id'],
                        'marketplaceId' => $configurableSku,
                        'result' => NotifyProductModerationDataInterface::STATUS_INTEGRATED,
                        'message' => 'PRODUCT PUBLISHED',
                        'skus' => $simpleSkuProducts
                    ];

                    $this->productHelper->publishResult($productData, $storeId);
                } catch (\Throwable $e) {
                    $error = "Product NOT INTEGRATED error: " . $e->getMessage() . ' - STORE: ' . $storeId .
                        ' - ' . $e->getFile() . ' - ' . $e->getLine();

                    $this->notifyProductModerationData->changeStatusNotify($idNotify, NotifyProductModerationDataInterface::STATUS_ERROR, $error);

                    $this->logger->error(
                        "Product NOT INTEGRATED error: " . $e->getMessage() . ' - STORE: ' . $storeId .
                        ' - ' . $e->getFile() . ' - ' . $e->getLine(),
                        $productModeratedData
                    );

                    $skuNotPublishData = [];

                    foreach ($productModeratedData['skus'] as $productData) {
                        $skuNotPublishData[] = [
                            'skuId' => $productData['skuData']['id']
                        ];
                    }

                    $notPublishProductData = [
                        'productId' => $productModeratedData['productData']['id'],
                        'result' => NotifyProductModerationDataInterface::RESULT_NOT_PUBLISHED,
                        'message' => $e->getMessage(),
                        'skus' => $skuNotPublishData
                    ];

                    $notPublishLog = $this->json->serialize($notPublishProductData);
                    $this->logger->info('PRODUCT NOT PUBLISHED: ' . $notPublishLog);

                    $this->productHelper->publishResult($notPublishProductData, $storeId);
                }
            }
        }
    }

    /**
     * Validate data
     *
     * @param $productModeratedData
     * @param $data
     * @return bool
     */
    public function isInvalid($productModeratedData, $data): bool
    {
        $idNotify = (int)$data['entity_id'];
        if (!isset($productModeratedData['productData']['id'])) {
            $this->notifyProductModerationData->changeStatusNotify(
                $idNotify, NotifyProductModerationDataInterface::STATUS_NOT_FOUND);
            return true;
        }

        if ($productModeratedData['productData']['id'] != $data['resource_id']) {
            $this->notifyProductModerationData->changeStatusNotify(
                $idNotify, NotifyProductModerationDataInterface::STATUS_DATA_INCONSISTENT);
            return true;
        }

        return false;
    }

    /**
     * Handle a transient transport failure from the moderation API.
     *
     * A {"fails":true} (or null) response means the Omnik API was unreachable or
     * errored — NOT that the product is absent. Marking it NOT_FOUND would retire a
     * valid product forever (the root cause of the production incident). Instead we
     * increment attempts and release the register back to the queue for retry, only
     * parking it in STATUS_ERROR once the attempt limit is exhausted.
     *
     * @param array|null $productModeratedData
     * @param int $idNotify
     * @return bool true when the response was a transient failure and was handled
     */
    private function handleTransientFailure($productModeratedData, int $idNotify): bool
    {
        $response = is_array($productModeratedData) ? $productModeratedData : null;
        if (!$this->apiResponse->isTransportFailure($response)) {
            return false;
        }

        $attempts = $this->notifyProductModerationData->changeAttempts($idNotify);

        $this->logger->error(
            'Product moderation API transient failure (fails:true) - notify_id: ' . $idNotify .
            ' - attempts: ' . $attempts,
            ['response' => $response]
        );

        if ($attempts >= NotifyProductModerationDataInterface::MAX_ATTEMPTS) {
            $this->notifyProductModerationData->changeStatusNotify(
                $idNotify,
                NotifyProductModerationDataInterface::STATUS_ERROR,
                'Omnik moderation API unreachable after ' . $attempts . ' attempts (fails:true)'
            );
        } else {
            // Return the register to the pending queue so a later cron retries it.
            $this->notifyProductModerationData->releaseClaim($idNotify);
        }

        return true;
    }

    /**
     * @param $productData
     * @param $storeId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getProductSimple($productData, $storeId)
    {
        $simpleSku = $this->getSimpleSku($productData, $storeId);
        try {
            return $this->productRepository->get($simpleSku);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param $dataSku
     * @param $storeId
     * @return string
     */
    public function getSimpleSku($dataSku, $storeId)
    {
        return $storeId . "_" . $dataSku['skuData']['sku'] . '-' . $dataSku['tenant'];
    }


    /**
     * @param  $productModeratedData
     * @param $storeId
     * @return mixed
     */
    private function getSimpleSkuProduct($productModeratedData, $storeId)
    {
        $skuSimpleProduct = [];
        foreach ($productModeratedData['skus'] as $productData) {
            $splSkuPrd = $this->getProductSimple($productData, $storeId);
            if ($splSkuPrd) {
                $skuSimpleProduct[] = $this->getSimpleSku($productData, $storeId);
            }
        }
        return $skuSimpleProduct;
    }

    /**
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getConfigurableProductBySku($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                return $product;
            }
        } catch (\Throwable $e) {
            $this->logger->error('ERROR: find sku ' . $sku . ' ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param mixed $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getChildProducts($productId)
    {
        $product = $this->productRepository->getById($productId);
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            return $this->configurableType->getUsedProducts($product);
        }
        return [];
    }
}
