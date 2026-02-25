<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Management;

use Omnik\Core\Helper\Product\Data;
use Omnik\Core\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Serialize\Serializer\Json;

class ProcessMatch
{
    public const STATUS_APPROVED = 'approved';
    public const ATTRIBUTE_CODE_VARIANT_SELLER = 'variant_seller';
    public const ATTRIBUTE_CODE_VARIANT_EMBALAGEM = 'variant_embalagem';
    public const ATTRIBUTE_CODE_VARIANT_COR = 'variant_color';
    public const ATTRIBUTE_CODE_VARIANT_TAMANHO = 'variant_tamanho';
    public const RESULT_PUBLISHED = 'published';
    public const POSITION_DEFAULT = 1;

    /**
     * @var array|string[]
     */
    private array $variantNameData = [
        'EMBALAGEM' => self::ATTRIBUTE_CODE_VARIANT_EMBALAGEM,
        'COR' => self::ATTRIBUTE_CODE_VARIANT_COR,
        'TAMANHO' => self::ATTRIBUTE_CODE_VARIANT_TAMANHO,
        'SELLER' => self::ATTRIBUTE_CODE_VARIANT_SELLER
    ];

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var Data
     */
    private Data $productHelper;

    /**
     * @var Factory
     */
    private Factory $optionsConfigurableFactory;

    /**
     * @var Configurable
     */
    private Configurable $productConfigurable;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param Data $productHelper
     * @param Factory $optionsConfigurableFactory
     * @param Configurable $productConfigurable
     * @param Json $json
     * @param Logger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Data                       $productHelper,
        Factory                    $optionsConfigurableFactory,
        Configurable               $productConfigurable,
        Json                       $json,
        Logger                     $logger
    ) {
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        $this->optionsConfigurableFactory = $optionsConfigurableFactory;
        $this->productConfigurable = $productConfigurable;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * @param array $response
     * @param int $storeId
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    public function actionProductMatch(array $response, int $storeId)
    {
        foreach ($response['simple_product_match'] as $productSimpleId => $skuMatch) {
            try {
                $skuData = $this->productRepository->get($skuMatch);
                $parentId = $this->productConfigurable->getParentIdsByChild($skuData->getId());

                if ($parentId) {
                    $product = $this->productRepository->getById($parentId[0]);

                    $extensionConfigurableAttributes = $product->getExtensionAttributes();
                    $associatedProductIds = $extensionConfigurableAttributes->getConfigurableProductLinks();
                    $associatedProductIds[] = $productSimpleId;
                    $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
                    $product->setExtensionAttributes($extensionConfigurableAttributes);

                    $this->productRepository->save($product);

                    $publishProductData = [
                        'productId' => $response['product_id'],
                        'marketplaceId' => $product->getSku(),
                        'result' => self::RESULT_PUBLISHED,
                        'skus' => $response['sku_publish_match']
                    ];

                    $publishLog = $this->json->serialize($publishProductData);
                    $this->logger->info('PUBLISH MATCH: ' . $publishLog);

                    $this->productHelper->publishResult($publishProductData, $storeId);
                }
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }
    }

    /**
     * @param array $responseConfigurable
     * @param array $attributeVariation
     * @param array $responseSimple
     * @param int $storeId
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function actionProductNoMatch(
        array $responseConfigurable,
        array $attributeVariation,
        array $responseSimple,
        int   $storeId
    ) {
        $product = $this->productRepository->getById($responseConfigurable['product_id_configurable']);

        $configurableOptions = $this->getConfigurableOptions($attributeVariation);
        $associatedProductIds = $responseSimple['simple_product_no_match'];
        $extensionConfigurableAttributes = $product->getExtensionAttributes();

        if (!empty($extensionConfigurableAttributes->getConfigurableProductLinks())) {
            $associatedProductIds = array_unique(array_merge(
                $extensionConfigurableAttributes->getConfigurableProductLinks(),
                $associatedProductIds
            ), SORT_REGULAR);
        }

        if (!empty($extensionConfigurableAttributes->getConfigurableProductOptions())) {
            $configurableOptions = array_merge(
                $extensionConfigurableAttributes->getConfigurableProductOptions(),
                $configurableOptions
            );
        }

        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
        $product->setExtensionAttributes($extensionConfigurableAttributes);

        $this->productRepository->save($product);

        $publishProductData = [
            'productId' => $responseConfigurable['product_id'],
            'marketplaceId' => $responseConfigurable['marketplace_id'],
            'result' => self::RESULT_PUBLISHED,
            'skus' => $responseSimple['sku_publish_no_match']
        ];

        $publishLog = $this->json->serialize($publishProductData);
        $this->logger->info('PUBLISH NO MATCH: ' . $publishLog);

        $this->productHelper->publishResult($publishProductData, $storeId);
    }

    /**
     * @param $attributeVariation
     * @return \Magento\ConfigurableProduct\Api\Data\OptionInterface[]
     * @throws NoSuchEntityException
     */
    public function getConfigurableOptions($attributeVariation)
    {
        $attributeValues[] = ['value_index' => self::POSITION_DEFAULT];
        $attributeDataTenant = $this->productHelper->getAttributeDataByCode(
            self::ATTRIBUTE_CODE_VARIANT_SELLER
        );

        $configurableAttributesData = [
            [
                'attribute_id' => $attributeDataTenant->getId(),
                'label' => $attributeDataTenant->getFrontendLabel(),
                'position' => self::POSITION_DEFAULT,
                'values' => $attributeValues
            ]
        ];

        $i = self::POSITION_DEFAULT + 1;
        foreach ($attributeVariation as $attribute) {
            if (array_key_exists($attribute['name'], $this->variantNameData)) {
                $attributeData = $this->productHelper->getAttributeDataByCode(
                    $this->variantNameData[$attribute['name']]
                );

                $attributeToConfigurable = [
                    'attribute_id' => $attributeData->getId(),
                    'label' => $attributeData->getFrontendLabel(),
                    'position' => $i,
                    'values' => $attributeValues
                ];
                $configurableAttributesData[] = $attributeToConfigurable;
                $i++;
            }
        }

        return $this->optionsConfigurableFactory->create($configurableAttributesData);
    }
}
