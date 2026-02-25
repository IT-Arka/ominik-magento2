<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Seller;

use Omnik\Core\Api\SellerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Data
{

    /**
     * @var SellerRepositoryInterface
     */
    private SellerRepositoryInterface $sellerRepositoryInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param SellerRepositoryInterface $sellerRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SellerRepositoryInterface $sellerRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->sellerRepositoryInterface = $sellerRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string $name
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSellerDataBySellerFantasyName(string $name): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('fantasy_name', $name)
            ->create();

        $seller = $this->sellerRepositoryInterface->getList($searchCriteria);
        return $seller->getItems();
    }
}
