<?php

declare(strict_types=1);

namespace Omnik\Core\Helper\SalesIntegration;

use Omnik\Core\Api\NotifyOmnikRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    public const EVENT_NEW = 'NEW';
    public const RESOURCE_TYPE_ORDER = 'ORDER';

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
     * @param Context $context
     */
    public function __construct(
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        NotifyOmnikRepositoryInterface $notifyOmnikRepository,
        Context                        $context
    )
    {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->notifyOmnikRepository = $notifyOmnikRepository;
        parent::__construct($context);
    }

    /**
     * @param $orderId
     * @return array
     */
    public function getNotificationNewOrder($orderId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('event', self::EVENT_NEW)
            ->addFilter('resource_type', self::RESOURCE_TYPE_ORDER)
            ->addFilter('resource_market_place_id', $orderId)
            ->create();

        return $this->notifyOmnikRepository->getList($searchCriteria);
    }
}
