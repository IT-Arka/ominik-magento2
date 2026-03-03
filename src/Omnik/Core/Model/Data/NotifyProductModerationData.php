<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Data;

use Omnik\Core\Api\NotifyOmnikRepositoryInterface;
use Omnik\Core\Api\Data\NotifyProductModerationDataInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class NotifyProductModerationData implements NotifyProductModerationDataInterface
{
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
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        NotifyOmnikRepositoryInterface $notifyOmnikRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->notifyOmnikRepository = $notifyOmnikRepository;
    }

    /**
     * @return array
     */
    public function getProductModerationApproved(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('event', NotifyProductModerationDataInterface::EVENT_MODERATION_APPROVED)
            ->addFilter('resource_type', NotifyProductModerationDataInterface::RESOURCE_PRODUCT)
            ->addFilter('status', NotifyProductModerationDataInterface::STATUS_NOT_INTEGRATED)
            ->setPageSize(NotifyProductModerationDataInterface::QTY_LIMIT_REGISTERS)
            ->create();

        return $this->notifyOmnikRepository->getList($searchCriteria);
    }

    /**
     * @param int $id
     * @param int $status
     * @param string|null $errorBody
     * @return void
     */
    public function changeStatusNotify(int $id, int $status, ?string $errorBody = null): void
    {
        $notifyOmnik = $this->notifyOmnikRepository->getById($id);
        $notifyOmnik->setStatus($status);
        if ($errorBody) {
            $notifyOmnik->setErrorBody($errorBody);
        }
        if ($status == 1) {
            $notifyOmnik->setErrorBody('');
        }

        $this->notifyOmnikRepository->save($notifyOmnik);
    }
}
