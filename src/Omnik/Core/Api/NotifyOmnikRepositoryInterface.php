<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

use Omnik\Core\Api\Data\NotifyOmnikInterface;

interface NotifyOmnikRepositoryInterface
{

    /**
     * @param NotifyOmnikInterface $notifyOmnikInterface
     * @return int
     */
    public function save(NotifyOmnikInterface $notifyOmnikInterface): int;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): array;

    /**
     * @param int $id
     * @return NotifyOmnikInterface
     */
    public function getById(int $id): NotifyOmnikInterface;
}
