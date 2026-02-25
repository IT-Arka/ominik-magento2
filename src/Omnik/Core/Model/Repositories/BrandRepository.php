<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Repositories;

use Exception;
use Omnik\Core\Api\BrandRepositoryInterface;
use Omnik\Core\Api\Data\BrandInterface;
use Omnik\Core\Model\BrandFactory;
use Omnik\Core\Model\ResourceModel\Brand;
use Omnik\Core\Model\ResourceModel\Brand\CollectionFactory as BrandCollectionFactory;
use Omnik\Core\Api\Data\SearchResults\BrandSearchResultsInterfaceFactory as BrandSearchResultsFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BrandRepository implements BrandRepositoryInterface
{
    /**
     * @var Brand
     */
    private Brand $brandResourceModel;

    /**
     * @var BrandCollectionFactory
     */
    private BrandCollectionFactory $brandCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var BrandSearchResultsFactory
     */
    private BrandSearchResultsFactory $brandSearchResultsFactory;

    /**
     * @var BrandFactory
     */
    private BrandFactory $brandFactory;

    /**
     * @param Brand $brandResourceModel
     * @param BrandCollectionFactory $brandCollectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param BrandSearchResultsFactory $brandSearchResultsFactory
     * @param BrandFactory $brandFactory
     */
    public function __construct(
        Brand $brandResourceModel,
        BrandCollectionFactory $brandCollectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        BrandSearchResultsFactory $brandSearchResultsFactory,
        BrandFactory $brandFactory
    ) {
        $this->brandResourceModel = $brandResourceModel;
        $this->brandCollectionFactory = $brandCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->brandSearchResultsFactory = $brandSearchResultsFactory;
        $this->brandFactory = $brandFactory;
    }
    /**
     * @param BrandInterface $brand
     * @return BrandInterface
     * @throws CouldNotSaveException
     */
    public function save(BrandInterface $brand): BrandInterface
    {
        try {
            $this->brandResourceModel->save($brand);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $brand;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->brandCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->brandSearchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @param int $entityId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId)
    {
        $brand = $this->brandFactory->create();
        $this->brandResourceModel->load($brand, $entityId);

        if (!$brand->getId()) {
            throw new NoSuchEntityException(
                __('The brand with the %1 ID doesn\'t exist.', $entityId)
            );
        }

        return $brand;
    }

    /**
     * @param BrandInterface $brand
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(BrandInterface $brand): bool
    {
        try {
            $this->brandResourceModel->delete($brand);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $entityId
     * @return bool|mixed
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $entityId)
    {
        return $this->delete($this->getById($entityId));
    }

    /**
     * @param string $brandCode
     * @return \Omnik\Brand\Model\Brand|mixed
     * @throws NoSuchEntityException
     */
    public function getByBrandCode(string $brandCode)
    {
        $brand = $this->brandFactory->create();
        $this->brandResourceModel->load($brand, $brandCode, BrandInterface::BRAND_CODE);

        if (!$brand->getId()) {
            throw new NoSuchEntityException(
                __('The brand with the %1 BRAND_CODE doesn\'t exist.', $brandCode)
            );
        }

        return $brand;
    }
}
