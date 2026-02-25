<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Repositories;

use Exception;
use Omnik\Core\Api\VariantRepositoryInterface;
use Omnik\Core\Api\Data\VariantInterface;
use Omnik\Core\Model\VariantFactory;
use Omnik\Core\Model\ResourceModel\Variant;
use Omnik\Core\Model\ResourceModel\Variant\CollectionFactory as VariantCollectionFactory;
use Omnik\Core\Api\Data\SearchResults\VariantSearchResultsInterfaceFactory as VariantSearchResultsFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class VariantRepository implements VariantRepositoryInterface
{
    /**
     * @var Variant
     */
    private Variant $variantResourceModel;

    /**
     * @var VariantCollectionFactory
     */
    private VariantCollectionFactory $variantCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var VariantSearchResultsFactory
     */
    private VariantSearchResultsFactory $variantSearchResultsFactory;

    /**
     * @var VariantFactory
     */
    private VariantFactory $variantFactory;

    /**
     * @param Variant $variantResourceModel
     * @param VariantCollectionFactory $variantCollectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param VariantSearchResultsFactory $variantSearchResultsFactory
     * @param VariantFactory $variantFactory
     */
    public function __construct(
        Variant $variantResourceModel,
        VariantCollectionFactory $variantCollectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        VariantSearchResultsFactory $variantSearchResultsFactory,
        VariantFactory $variantFactory
    ) {
        $this->variantResourceModel = $variantResourceModel;
        $this->variantCollectionFactory = $variantCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->variantSearchResultsFactory = $variantSearchResultsFactory;
        $this->variantFactory = $variantFactory;
    }
    /**
     * @param VariantInterface $variant
     * @return VariantInterface
     * @throws CouldNotSaveException
     */
    public function save(VariantInterface $variant): VariantInterface
    {
        try {
            $this->variantResourceModel->save($variant);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $variant;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->variantCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->variantSearchResultsFactory->create();
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
        $variant = $this->variantFactory->create();
        $this->variantResourceModel->load($variant, $entityId);

        if (!$variant->getId()) {
            throw new NoSuchEntityException(
                __('The variant with the %1 ID doesn\'t exist.', $entityId)
            );
        }

        return $variant;
    }

    /**
     * @param VariantInterface $variant
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(VariantInterface $variant): bool
    {
        try {
            $this->variantResourceModel->delete($variant);
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
}
