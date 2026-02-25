<?php

declare(strict_types=1);

namespace Omnik\Core\Observer;

use Exception;
use Omnik\Core\Model\Category\Config\Params;
use Omnik\Core\Helper\Config;
use Omnik\Core\Model\AbstractIntegration;
use Omnik\Core\Model\Integration\Category\Get;
use Omnik\Core\Model\Integration\Category\Send;
use Omnik\Core\Model\Integration\Category\Update;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\GroupRepositoryInterface;

class CategorySave implements ObserverInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Params
     */
    private Params $params;

    /**
     * @var Get
     */
    private Get $get;

    /**
     * @var Send
     */
    private Send $send;

    /**
     * @var Update
     */
    private Update $update;

    /**
     * @var GroupRepositoryInterface
     */
    private GroupRepositoryInterface $groupRepository;

    /**
     * @param Config $config
     * @param Params $params
     * @param Get $get
     * @param Send $send
     * @param Update $update
     * @param GroupRepositoryInterface $groupRepository
     */
    public function __construct(
        Config $config,
        Params $params,
        Get $get,
        Send $send,
        Update $update,
        GroupRepositoryInterface $groupRepository
    ) {
        $this->config = $config;
        $this->params = $params;
        $this->get = $get;
        $this->send = $send;
        $this->update = $update;
        $this->groupRepository = $groupRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $categoryData = $observer->getCategory()->getData();

        $entityId = (int) $categoryData['entity_id'];

        if (isset($categoryData['path_ids'][1]) == null) {
            $rootCategoryId = "";
        } else {
            $rootCategoryId = $categoryData['path_ids'][1];
        }

        $storeId = AbstractIntegration::STORE_ID_DEFAULT;

        $groups = $this->groupRepository->getList();

        foreach ($groups as $group) {
            if ($group->getRootCategoryId() == $rootCategoryId) {
                $storeId = (int) $group->getDefaultStoreId();
            }
        }

        $tenantMarketplace = $this->config->getTenantMarketplace($storeId);

        $params = $this->params->getParamsCategory($categoryData['path_ids'], $tenantMarketplace);

        $categoryAlreadyIntegrated = $this->get->execute($params, $tenantMarketplace, $entityId, $storeId);
        $categoryAlreadyIntegrated = (!isset($categoryAlreadyIntegrated['fails'])) ?? $categoryAlreadyIntegrated;

        if (!$categoryAlreadyIntegrated) {
            $this->send->execute($params, $tenantMarketplace, $storeId);
        }

        if ($categoryAlreadyIntegrated) {

            $this->update->execute($params, $tenantMarketplace, $entityId, $storeId);
        }

        // $this->send->execute($params, $tenantMarketplace, $storeId);
    }
}
