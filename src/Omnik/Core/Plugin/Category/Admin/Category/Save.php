<?php

declare(strict_types=1);

namespace Omnik\Core\Plugin\Category\Admin\Category;

use Exception;
use Omnik\Core\Model\Category\Config\Params;
use Omnik\Core\Model\Integration\Category\Send;
use Omnik\Core\Helper\Config;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\GroupRepositoryInterface;
use Omnik\Integration\Model\AbstractIntegration;

class Save
{
    /**
     * @var Params
     */
    private Params $params;

    /**
     * @var Send
     */
    private Send $send;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var GroupRepositoryInterface
     */
    private GroupRepositoryInterface $groupStoreRepository;

    /**
     * @param Params $params
     * @param Send $send
     * @param Config $config
     * @param GroupRepositoryInterface $groupStoreRepository
     */
    public function __construct(
        Params $params,
        Send $send,
        Config $config,
        GroupRepositoryInterface $groupStoreRepository
    ) {
        $this->send = $send;
        $this->params = $params;
        $this->config = $config;
        $this->groupStoreRepository = $groupStoreRepository;
    }

    /**
     * @param \Magento\Catalog\Controller\Adminhtml\Category\Save $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function afterExecute(
        \Magento\Catalog\Controller\Adminhtml\Category\Save $subject,
        $result
    ) {
        $parameters = $subject->getRequest()->getParams();

        if (isset($parameters['path_ids'][1])) {
            $rootCategoryId = $parameters['path_ids'][1];
        } else {
            $level = $this->params->getLevelCategoryInsert((int) $parameters['parent']);
            $rootCategoryId = $level[1];
        }

        $storeId = AbstractIntegration::STORE_ID_DEFAULT;

        $groups = $this->groupStoreRepository->getList();

        foreach ($groups as $group) {
            if ($group->getRootCategoryId() == $rootCategoryId) {
                $storeId = (int) $group->getDefaultStoreId();
            }
        }

        $tenantMarketplace = $this->config->getTenantMarketplace($storeId);
        $params = $this->params->getParams($parameters, $tenantMarketplace);

        $this->send->execute($params, $tenantMarketplace, $storeId);

        return $result;
    }
}
