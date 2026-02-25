<?php
declare(strict_types=1);

namespace Omnik\Core\Model\Repositories;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Omnik\Core\Api\Data;
use Omnik\Core\Api\SellerRepositoryInterface;
use Omnik\Core\Model\ResourceModel\Seller as ResourceSeller;
use Omnik\Core\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
use Omnik\Core\Model\SellerFactory;
use Omnik\Core\Logger\Logger;

class SellerRepository implements SellerRepositoryInterface
{
    /**
     * @var ResourceSeller
     */
    private ResourceSeller $resource;

    /**
     * @var SellerFactory
     */
    private SellerFactory $modelFactory;

    /**
     * @var SellerCollectionFactory
     */
    private SellerCollectionFactory $collectionFactory;

    /**
     * @var Data\SearchResults\SellerSearchResultsInterfaceFactory
     */
    private Data\SearchResults\SellerSearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * SellerRepository constructor.
     * @param ResourceSeller $resource
     * @param SellerFactory $modelFactory
     * @param SellerCollectionFactory $collectionFactory
     * @param Data\SearchResults\SellerSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param Logger $logger
     */
    public function __construct(
        ResourceSeller $resource,
        SellerFactory $modelFactory,
        SellerCollectionFactory $collectionFactory,
        Data\SearchResults\SellerSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        Logger $logger
    ) {
        $this->resource = $resource;
        $this->modelFactory = $modelFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function save(Data\SellerInterface $model): Data\SellerInterface
    {
        try {
            $this->resource->save($model);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function update(Data\SellerInterface $model, $seller_id): Data\SellerInterface
    {
        $modelDB = $this->getById($seller_id);
        $model->setId($modelDB->getId());
        $this->save($model);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getById($id)
    {
        $model = $this->modelFactory->create();
        $this->resource->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(
                __('The Seller with the %1 ID doesn\'t exist.', $id)
            );
        }
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getByOmnikId($omnik_id)
    {
        $model = $this->modelFactory->create();
        $this->resource->load($model, $omnik_id, Data\SellerInterface::OMNIK_ID);
        if (!$model->getId()) {
            throw new NoSuchEntityException(
                __('The Seller with the %1 OMNIK_ID doesn\'t exist.', $omnik_id)
            );
        }
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): Data\SearchResults\SellerSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(Data\SellerInterface $model): bool
    {
        try {
            $this->resource->delete($model);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id): bool
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @inheritDoc
     */
    public function deleteByOmnikId($omnik_id): bool
    {
        $model = $this->getByOmnikId($omnik_id);
        $this->delete($model);
        return true;
    }
}
