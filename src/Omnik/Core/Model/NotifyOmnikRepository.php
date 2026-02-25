<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\Data\NotifyOmnikInterface;
use Omnik\Core\Api\NotifyOmnikRepositoryInterface;
use Omnik\Core\Model\ResourceModel\NotifyOmnik as NotifyOmnikResourceModel;
use Omnik\Core\Model\ResourceModel\NotifyOmnik\CollectionFactory as NotifyOmnikCollectionFactory;
use Omnik\Core\Api\Data\NotifyOmnikSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class NotifyOmnikRepository implements NotifyOmnikRepositoryInterface
{

    /**
     * @var NotifyOmnikResourceModel
     */
    private NotifyOmnikResourceModel $notifyOmnikResourceModel;

    /**
     * @var NotifyOmnikSearchResultsInterfaceFactory
     */
    private NotifyOmnikSearchResultsInterfaceFactory $notifyOmnikSearchResults;

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var NotifyOmnikCollectionFactory
     */
    private NotifyOmnikCollectionFactory $notifyOmnikCollectionFactory;

    /**
     * @var NotifyOmnikFactory
     */
    private NotifyOmnikFactory $notifyOmnikFactory;

    /**
     * NotifyOmnikRepository constructor.
     * @param NotifyOmnikResourceModel $notifyOmnikResourceModel
     * @param NotifyOmnikSearchResultsInterfaceFactory $notifyOmnikSearchResults
     * @param CollectionProcessorInterface $collectionProcessor
     * @param NotifyOmnikCollectionFactory $notifyOmnikCollectionFactory
     * @param NotifyOmnikFactory $notifyOmnikFactory
     */
    public function __construct(
        NotifyOmnikResourceModel $notifyOmnikResourceModel,
        NotifyOmnikSearchResultsInterfaceFactory $notifyOmnikSearchResults,
        CollectionProcessorInterface $collectionProcessor,
        NotifyOmnikCollectionFactory $notifyOmnikCollectionFactory,
        NotifyOmnikFactory $notifyOmnikFactory
    ) {
        $this->notifyOmnikResourceModel = $notifyOmnikResourceModel;
        $this->notifyOmnikSearchResults = $notifyOmnikSearchResults;
        $this->collectionProcessor = $collectionProcessor;
        $this->notifyOmnikCollectionFactory = $notifyOmnikCollectionFactory;
        $this->notifyOmnikFactory = $notifyOmnikFactory;
    }

    /**
     * @param NotifyOmnikInterface $notifyOmnikInterface
     * @return int
     * @throws CouldNotSaveException
     */
    public function save(NotifyOmnikInterface $notifyOmnikInterface): int
    {
        try {
            $this->notifyOmnikResourceModel->save($notifyOmnikInterface);
            return (int) $notifyOmnikInterface->getId();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the notify'));
        }
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): array
    {
        $items = [];
        $collection = $this->notifyOmnikCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->notifyOmnikSearchResults->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        if (!empty($searchResults->getItems())) {
            foreach ($searchResults->getItems() as $item) {
                $items[] = $item->getData();
            }
        }

        return $items;
    }

    /**
     * @param int $id
     * @return NotifyOmnikInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): NotifyOmnikInterface
    {
        $notifyOmnik = $this->notifyOmnikFactory->create();
        $this->notifyOmnikResourceModel->load($notifyOmnik, $id);

        if (!$notifyOmnik->getId()) {
            throw new NoSuchEntityException(__('Notify Omnik %1 does not exist', $id));
        }

        return $notifyOmnik;
    }
}
