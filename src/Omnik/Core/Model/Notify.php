<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Exception;
use Omnik\Core\Model\AbstractIntegration;
use Omnik\Core\Api\NotifyInterface;
use Omnik\Core\Api\NotifyOmnikRepositoryInterface;
use Omnik\Core\Api\Data\NotifyOmnikInterface;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Data\VerifyModerationApproved;
use Omnik\Core\Helper\Data;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Store\Model\StoreManagerInterface;

class Notify implements NotifyInterface
{
    /**
     * @var NotifyOmnikRepositoryInterface
     */
    private NotifyOmnikRepositoryInterface $notifyOmnikRepositoryInterface;

    /**
     * @var NotifyOmnikInterface
     */
    private NotifyOmnikInterface $notifyOmnikInterface;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var VerifyModerationApproved
     */
    private VerifyModerationApproved $verifyModerationApproved;

    /**
     * @var Data
     */
    private Data $queueHelper;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param NotifyOmnikRepositoryInterface $notifyOmnikRepositoryInterface
     * @param NotifyOmnikInterface $notifyOmnikInterface
     * @param Request $request
     * @param Logger $logger
     * @param VerifyModerationApproved $verifyModerationApproved
     * @param Data $queueHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        NotifyOmnikRepositoryInterface $notifyOmnikRepositoryInterface,
        NotifyOmnikInterface $notifyOmnikInterface,
        Request $request,
        Logger $logger,
        VerifyModerationApproved $verifyModerationApproved,
        Data $queueHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->notifyOmnikRepositoryInterface = $notifyOmnikRepositoryInterface;
        $this->notifyOmnikInterface = $notifyOmnikInterface;
        $this->request = $request;
        $this->logger = $logger;
        $this->verifyModerationApproved = $verifyModerationApproved;
        $this->queueHelper = $queueHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     * @throws CouldNotSaveException
     */
    public function execute(): array
    {
        $params = $this->request->getBodyParams();

        switch ($params['resourceType']) {
            case self::RESOURCE_PRICE:
                $this->queueHelper->publisherQueueUpdatePrice($params);
                break;
            case self::RESOURCE_INVENTORY:
                $this->queueHelper->publisherQueueUpdateStock($params);
                break;
            default:
                $this->notifyOmnikInterface->setDate($params['date']);

                if (isset($params['seller'])) {
                    $this->notifyOmnikInterface->setSeller($params['seller']);
                }

                $this->notifyOmnikInterface->setEvent($params['event']);
                $this->notifyOmnikInterface->setResourceId($params['resourceId']);
                $this->notifyOmnikInterface->setResourceType($params['resourceType']);

                if (isset($params['resourceURI'])) {
                    $this->notifyOmnikInterface->setResourceUri($params['resourceURI']);
                }

                if (isset($params['resourceMarketePlaceId'])) {
                    $this->notifyOmnikInterface->setResourceMarketPlaceId($params['resourceMarketePlaceId']);
                }

                if (isset($params['resourceMarketePlaceURI'])) {
                    $this->notifyOmnikInterface->setResourceMarketPlaceUri($params['resourceMarketePlaceURI']);
                }

                $storeId = AbstractIntegration::STORE_ID_DEFAULT;
                $httpHost = $this->request->getHttpHost();
                $frontendName = $this->getFrontendName();

                if (stripos($httpHost, $frontendName) !== false) {
                    if (stripos($httpHost, self::URL_STAGING) !== false) {
                        $storeId = self::STORE_ID_STAGING;
                    } else {
                        $storeId = $this->getCurrentStoreId();
                    }
                }

                $this->notifyOmnikInterface->setStoreId($storeId);

                try {
                    $moderationApproved = [];

                    if (
                        $params['event'] == VerifyModerationApproved::EVENT_MODERATION_APPROVED &&
                        $params['resourceType'] == VerifyModerationApproved::RESOURCE_PRODUCT
                    ) {
                        $moderationApproved = $this->verifyModerationApproved->getModerationApproved(
                            $params['seller'],
                            $params['resourceId']
                        );
                    }

                    if (!$moderationApproved) {
                        $this->notifyOmnikRepositoryInterface->save($this->notifyOmnikInterface);
                    }

                    $this->logger->info($this->request->getContent());
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                    throw new CouldNotSaveException(__('Could not save the notify.'));
                }

                break;
        }

        $response[] = ['message' => 'success'];

        return $response;
    }


    public function getStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }

    public function getCurrentStoreId()
    {
        $idStore = $this->storeManager->getStore()->getId();
        
        if ($idStore == 0) {
            $idStore = AbstractIntegration::STORE_ID_DEFAULT;
        }

        return $idStore;
    }

    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    public function getFrontendName(): string
    {
        return $this->storeManager->getStore()->getFrontendName();
    }
}
