<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

use Omnik\Core\Api\Data\VariantInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface VariantRepositoryInterface
{
    /**
     * @param VariantInterface $variant
     * @return mixed
     */
    public function save(VariantInterface $variant);

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
     * @param VariantInterface $variant
     * @return mixed
     */
    public function delete(VariantInterface $variant);

    /**
     * @param int $entityId
     * @return mixed
     */
    public function deleteById(int $entityId);
}
