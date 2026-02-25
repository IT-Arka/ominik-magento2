<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

use Omnik\Core\Api\Data\BrandInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BrandRepositoryInterface
{
    /**
     * @param BrandInterface $brand
     * @return mixed
     */
    public function save(BrandInterface $brand);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param int $entityId
     * @return mixed
     */
    public function getById(int $entityId);

    /**
     * @param BrandInterface $brand
     * @return mixed
     */
    public function delete(BrandInterface $brand);

    /**
     * @param int $entityId
     * @return mixed
     */
    public function deleteById(int $entityId);

    /**
     * @param string $brandCode
     * @return mixed
     */
    public function getByBrandCode(string $brandCode);
}
