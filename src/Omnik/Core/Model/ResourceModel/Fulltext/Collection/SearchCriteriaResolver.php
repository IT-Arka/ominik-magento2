<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future.
 *
 * @author    Danilo Cavalcanti <danilo-cm@hotmail.com>
 */

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel\Fulltext\Collection;

use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolver
    as MagentoSearchCriteriaResolver;
use Magento\Framework\Data\Collection;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\DeploymentConfig;

class SearchCriteriaResolver extends MagentoSearchCriteriaResolver
{
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $builder;

    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * @var string
     */
    private string $searchRequestName;

    /**
     * @var int
     */
    private int $size;

    /**
     * @var array
     */
    private array $orders;

    /**
     * @var int
     */
    private int $currentPage;

    /**
     * @var Session
     */
    private Session $catalogSession;

    /**
     * @var \Cm_Cache_Backend_Redis
     */
    private \Cm_Cache_Backend_Redis $redis;

    /**
     * @var DeploymentConfig
     */
    private DeploymentConfig $deploymentConfig;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param Session $catalogSession
     * @param SearchCriteriaBuilder $builder
     * @param Collection $collection
     * @param string $searchRequestName
     * @param int $currentPage
     * @param int $size
     * @param array|null $orders
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     * @throws \Zend_Cache_Exception
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        Session $catalogSession,
        SearchCriteriaBuilder $builder,
        Collection $collection,
        string $searchRequestName,
        int $currentPage,
        int $size,
        ?array
        $orders
    ) {
        $this->builder = $builder;
        $this->collection = $collection;
        $this->searchRequestName = $searchRequestName;
        $this->currentPage = $currentPage;
        $this->size = $size;
        $this->orders = $orders;
        $this->catalogSession = $catalogSession;

        $options = $deploymentConfig->get('cache/frontend/default/backend_options');

        if (isset($options['remote_backend_options'])) {
            $options = $options['remote_backend_options'];
        }

        $this->redis = new \Cm_Cache_Backend_Redis($options);

        parent::__construct($builder, $collection, $searchRequestName, $currentPage, $size, $orders);
    }

    /**
     * @return SearchCriteria
     */
    public function resolve(): SearchCriteria
    {
        $key = 'curPage_' . $this->catalogSession->getSessionId();
        $currentPage = $this->redis->load($key) ? $this->redis->load($key) : $this->currentPage;
        $searchCriteria = $this->builder->create();
        $searchCriteria->setRequestName($this->searchRequestName);
        $searchCriteria->setSortOrders($this->orders);
        $searchCriteria->setCurrentPage((int) $currentPage);
        if ($this->size) {
            $searchCriteria->setPageSize($this->size);
        }

        return $searchCriteria;
    }
}
