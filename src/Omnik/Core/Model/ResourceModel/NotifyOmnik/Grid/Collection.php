<?php

namespace Omnik\Core\Model\ResourceModel\NotifyOmnik\Grid;

use Omnik\Core\Model\ResourceModel\NotifyOmnik\Collection as NotifyCollection;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;

class Collection extends NotifyCollection implements SearchResultInterface
{
    protected $aggregations;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param $mainTable
     * @param $eventPrefix
     * @param $eventObject
     * @param $resourceModel
     * @param $model
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface        $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface       $eventManager,
                               $mainTable,
                               $eventPrefix,
                               $eventObject,
                               $resourceModel,
                               $model = Document::class,
        ?AdapterInterface       $connection = null,
        ?AbstractDb             $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;

        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);

        $this->_prepareCollection();
    }

    /**
     * @return void
     */
    public function _prepareCollection()
    {
        $this->getSelect()
            ->where('main_table.status != ?', 1)
            ->where('main_table.resource_type = ?', 'PRODUCT')
            ->where('main_table.event = ?', 'MODERATION_APPROVED');
    }

    /**
     * @return \Magento\Framework\Api\Search\AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param $aggregations
     * @return Collection|void
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * @return null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return $this|Collection
     */
    public function setSearchCriteria(?\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @param $totalCount
     * @return $this|Collection
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * @param array|null $items
     * @return $this|Collection
     */
    public function setItems(?array $items = null)
    {
        return $this;
    }
}
