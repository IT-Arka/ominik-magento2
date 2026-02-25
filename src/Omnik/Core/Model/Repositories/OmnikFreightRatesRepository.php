<?php
declare(strict_types=1);

namespace Omnik\Core\Model\Repositories;

use Omnik\Core\Model\SearchResults\OmnikFreightRatesSearchResults;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Omnik\Core\Api\Data;
use Omnik\Core\Api\OmnikFreightRatesRepositoryInterface;
use Omnik\Core\Model\ResourceModel\OmnikFreightRates as ResourceOmnikFreightRates;
use Omnik\Core\Model\ResourceModel\OmnikFreightRates\CollectionFactory as OmnikFreightRatesCollectionFactory;
use Omnik\Core\Model\OmnikFreightRatesFactory;

class OmnikFreightRatesRepository implements OmnikFreightRatesRepositoryInterface
{
    /**
     * @param ResourceOmnikFreightRates $resource
     * @param OmnikFreightRatesFactory $modelFactory
     * @param OmnikFreightRatesCollectionFactory $collectionFactory
     * @param Data\SearchResults\OmnikFreightRatesResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilder $searchCriteria
     */
    public function __construct(
        private readonly ResourceOmnikFreightRates                                   $resource,
        private readonly OmnikFreightRatesFactory                                    $modelFactory,
        private readonly OmnikFreightRatesCollectionFactory                          $collectionFactory,
        private readonly Data\SearchResults\OmnikFreightRatesResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface                                $collectionProcessor,
        private readonly SearchCriteriaBuilder                                       $searchCriteria
    ) {

    }

    /**
     * @inheritDoc
     */
    public function save(Data\OmnikFreightRatesInterface $model): Data\OmnikFreightRatesInterface
    {
        try {
            $this->resource->save($model);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function update(Data\OmnikFreightRatesInterface $model, $seller_id): Data\OmnikFreightRatesInterface
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
                __('The Rate with the %1 ID doesn\'t exist.', $id)
            );
        }
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): Data\SearchResults\OmnikFreightRatesResultsInterface
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
    public function delete(Data\OmnikFreightRatesInterface $model): bool
    {
        try {
            $this->resource->delete($model);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param $quoteId
     * @param string $method
     * @param string $sellerTenant
     * @param string $condition
     * @return OmnikFreightRatesSearchResults
     */
    public function getFreightRate($quoteId, string $method, string $sellerTenant, string $condition = 'eq'): OmnikFreightRatesSearchResults
    {
        $this->searchCriteria->addFilter('quote_id', $quoteId);
        $this->searchCriteria->addFilter('delivery_method_id', $method, $condition);
        $this->searchCriteria->addFilter('seller_tenant', $sellerTenant);

        $searchCriteria = $this->searchCriteria->create();

        return $this->getList($searchCriteria);
    }
}
