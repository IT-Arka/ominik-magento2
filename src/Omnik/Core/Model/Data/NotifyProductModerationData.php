<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Data;

use Magento\Framework\Math\Random;
use Omnik\Core\Api\NotifyOmnikRepositoryInterface;
use Omnik\Core\Api\Data\NotifyProductModerationDataInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Omnik\Core\Model\ResourceModel\NotifyOmnik as NotifyOmnikResource;

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
     * @var NotifyOmnikResource
     */
    private NotifyOmnikResource $notifyOmnikResource;

    /**
     * @var Random
     */
    private Random $random;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param NotifyOmnikRepositoryInterface $notifyOmnikRepository
     * @param NotifyOmnikResource $notifyOmnikResource
     * @param Random $random
     */
    public function __construct(
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        NotifyOmnikRepositoryInterface $notifyOmnikRepository,
        NotifyOmnikResource            $notifyOmnikResource,
        Random                         $random
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->notifyOmnikRepository = $notifyOmnikRepository;
        $this->notifyOmnikResource = $notifyOmnikResource;
        $this->random = $random;
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
     * @inheritDoc
     */
    public function claimProductModerationApproved(?int $maxAttempts = null): array
    {
        $token = $this->random->getUniqueHash('pc_');

        return $this->notifyOmnikResource->claimBatch(
            $token,
            [
                'event' => NotifyProductModerationDataInterface::EVENT_MODERATION_APPROVED,
                'resource_type' => NotifyProductModerationDataInterface::RESOURCE_PRODUCT,
            ],
            NotifyProductModerationDataInterface::QTY_LIMIT_REGISTERS,
            $maxAttempts
        );
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
        if ($status == NotifyProductModerationDataInterface::STATUS_INTEGRATED) {
            $notifyOmnik->setErrorBody('');
        }

        $this->notifyOmnikRepository->save($notifyOmnik);
    }

    /**
     * @inheritDoc
     */
    public function changeAttempts(int $id): int
    {
        $notifyOmnik = $this->notifyOmnikRepository->getById($id);
        $attempts = (int) $notifyOmnik->getAttempts() + 1;
        $notifyOmnik->setAttempts($attempts);
        $this->notifyOmnikRepository->save($notifyOmnik);

        return $attempts;
    }

    /**
     * @inheritDoc
     */
    public function releaseClaim(int $id): void
    {
        $this->notifyOmnikResource->releaseClaim($id);
    }
}
