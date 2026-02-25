<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Data;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Omnik\Core\Api\NotifyOmnikRepositoryInterface;

class VerifyModerationApproved
{
    public const EVENT_MODERATION_APPROVED = 'MODERATION_APPROVED';
    public const RESOURCE_PRODUCT = 'PRODUCT';

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var NotifyOmnikRepositoryInterface
     */
    private NotifyOmnikRepositoryInterface $notifyOmnikRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param NotifyOmnikRepositoryInterface $notifyOmnikRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        NotifyOmnikRepositoryInterface $notifyOmnikRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->notifyOmnikRepository = $notifyOmnikRepository;
    }

    /**
     * @param string $seller
     * @param string $resourceId
     * @return array
     */
    public function getModerationApproved(string $seller, string $resourceId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('seller', $seller)
            ->addFilter('event', self::EVENT_MODERATION_APPROVED)
            ->addFilter('resource_type', self::RESOURCE_PRODUCT)
            ->addFilter('resource_id', $resourceId)
            ->addFilter('error_body', true, 'null')
            ->create();

        return $this->notifyOmnikRepository->getList($searchCriteria);
    }
}
