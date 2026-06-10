<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Data;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Omnik\Core\Api\NotifyOmnikRepositoryInterface;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Omnik\Core\Helper\Config;
use Omnik\Core\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;

class NotifyOmnikData implements NotifyOmnikDataInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var NotifyOmnikRepositoryInterface
     */
    private NotifyOmnikRepositoryInterface $notifyOmnikRepositoryInterface;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var Config $helperConfig
     */
    private Config $helperConfig;

    /**
     * @var Data $helperData
     */
    private Data $helperData;

    /**
     * @var serializerInterface $serializer
     */
    private $serializer;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param NotifyOmnikRepositoryInterface $notifyOmnikRepositoryInterface
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param Config $helperConfig
     * @param Data $helperData
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        NotifyOmnikRepositoryInterface $notifyOmnikRepositoryInterface,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        Config $helperConfig,
        Data $helperData,
        SerializerInterface $serializer
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->notifyOmnikRepositoryInterface = $notifyOmnikRepositoryInterface;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->helperConfig = $helperConfig;
        $this->helperData = $helperData;
        $this->serializer = $serializer;
    }

    /**
     * @param string|null $methods
     * @return array
     */
    public function getInfoByStatus($methods = null): array
    {

        if ($methods == "cronseller") {

            $statuses = [
                self::STATUS_NOT_INTEGRATED,
                self::STATUS_ERROR,
                self::STATUS_NOT_FOUND,
                self::STATUS_NOT_FOUND_MAGENTO
            ];

            $storeId = $this->helperData->getStoreId();
            $attempts = $this->helperConfig->getAttempts($storeId);

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('status', $statuses, 'in')
                ->addFilter('attempts', $attempts, 'lteq')
                ->addFilter('resource_type', 'SELLER', 'eq')
                ->create();

            return $this->notifyOmnikRepositoryInterface->getList($searchCriteria);

        } else {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                'status',
                self::STATUS_NOT_INTEGRATED
            )->create();

            return $this->notifyOmnikRepositoryInterface->getList($searchCriteria);
        }
    }

    /**
     * @return array
     */
    public function getOrderInfoByStatus(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status',self::STATUS_NOT_INTEGRATED)
            ->addFilter('resource_type',self::RESOURCE_TYPE_ORDER)
            ->create();

        return $this->notifyOmnikRepositoryInterface->getList($searchCriteria);

    }

    /**
     * @param int $id
     * @param $statusId
     * @param string|null $errorBody
     * @return void
     */
    public function changeStatusNotify(int $id, $statusId = self::STATUS_INTEGRATED, string $errorBody = null): void
    {
        $notifyOmnik = $this->notifyOmnikRepositoryInterface->getById($id);
        $notifyOmnik->setStatus($statusId);
        if($errorBody){
            $notifyOmnik->setErrorBody($errorBody);
        }

        $this->notifyOmnikRepositoryInterface->save($notifyOmnik);
    }

    public function changeAttempts(int $id): void
    {
        $notifyOmnik = $this->notifyOmnikRepositoryInterface->getById($id);
        $notifyOmnik->setAttempts($notifyOmnik->getAttempts() + 1);
        $this->notifyOmnikRepositoryInterface->save($notifyOmnik);
    }

}
