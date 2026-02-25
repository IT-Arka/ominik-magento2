<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Omnik\Core\Api\Data\OmnikFreightRatesInterface;

interface OmnikFreightRatesRepositoryInterface
{
    /**
     * @param OmnikFreightRatesInterface $omnikFreightRates
     * @return int
     */
    public function save(OmnikFreightRatesInterface $omnikFreightRates);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param int $id
     * @return OmnikFreightRatesInterface
     */
    public function getById(int $id);
}
