<?php

declare(strict_types=1);

namespace Omnik\Core\Helper\Product;

use Exception;
use Omnik\Core\Model\Integration\Product\Publish;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\Data\ImageContentInterfaceFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

class Data extends AbstractHelper
{
    public const MEDIA_TYPE_IMAGE = 'image';

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    /**
     * @var ProductAttributeMediaGalleryEntryInterfaceFactory
     */
    private ProductAttributeMediaGalleryEntryInterfaceFactory $mediaGalleryFactory;

    /**
     * @var ImageContentInterfaceFactory
     */
    private ImageContentInterfaceFactory $imageContentFactory;

    /**
     * @var ProductAttributeMediaGalleryManagementInterface
     */
    private ProductAttributeMediaGalleryManagementInterface $productMediaGallery;

    /**
     * @var Publish
     */
    private Publish $publish;

    /**
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param ProductAttributeMediaGalleryEntryInterfaceFactory $mediaGalleryFactory
     * @param ImageContentInterfaceFactory $imageContentFactory
     * @param ProductAttributeMediaGalleryManagementInterface $productMediaGallery
     * @param Publish $publish
     * @param Context $context
     */
    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ProductAttributeMediaGalleryEntryInterfaceFactory $mediaGalleryFactory,
        ImageContentInterfaceFactory $imageContentFactory,
        ProductAttributeMediaGalleryManagementInterface $productMediaGallery,
        Publish $publish,
        Context $context
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->mediaGalleryFactory = $mediaGalleryFactory;
        $this->imageContentFactory = $imageContentFactory;
        $this->productMediaGallery = $productMediaGallery;
        $this->publish = $publish;
        parent::__construct($context);
    }

    /**
     * @param string $attributeCode
     * @param string $attributeLabel
     * @return int
     * @throws NoSuchEntityException
     */
    public function getOptionIdAttributeByLabel(string $attributeCode, string $attributeLabel): int
    {
        $attributeData = $this->getAttributeDataByCode($attributeCode);
        $optionId = 0;

        if ($attributeData->usesSource()) {
            $optionId = $attributeData->getSource()->getOptionId(trim($attributeLabel ?? ''));
        }

        return (int) $optionId;
    }

    /**
     * @param string $attributeCode
     * @return ProductAttributeInterface
     * @throws NoSuchEntityException
     */
    public function getAttributeDataByCode(string $attributeCode): ProductAttributeInterface
    {
         return $this->productAttributeRepository->get($attributeCode);
    }

    /**
     * @param string $pathImage
     * @param string $sku
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function addImage(string $pathImage, string $sku)
    {
        try {
            $imageContent = $this->imageContentFactory->create();

            $imageData = explode('/', $pathImage);
            $imageContent->setName(end($imageData));
            $imageContent->setType(getimagesize($pathImage)['mime']);
            $imageContent->setBase64EncodedData(base64_encode(file_get_contents($pathImage)));

            $mediaGallery = $this->mediaGalleryFactory->create();

            $mediaGallery->setDisabled(false);
            $mediaGallery->setFile(end($imageData));
            $mediaGallery->setMediaType(self::MEDIA_TYPE_IMAGE);
            $mediaGallery->setContent($imageContent);

            $this->productMediaGallery->create($sku, $mediaGallery);
        }catch (Exception $exception){
            $this->_logger->error($exception->getMessage());
        }
    }

    /**
     * @param $publishData
     * @param $storeId
     * @return void
     * @throws Exception
     */
    public function publishResult($publishData, $storeId)
    {
        $this->publish->execute($publishData, $storeId);
    }
}
