<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Category\Config;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class Params
{
    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepositoryInterface;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param Json $json
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepositoryInterface,
        Json $json,
        CollectionFactory $collectionFactory
    ) {
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
        $this->json = $json;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param array $params
     * @param string $tenantMarketplace
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getParams(array $params, string $tenantMarketplace): string
    {
        $parameters['tenant'] = $tenantMarketplace;
        $parameters['operator'] = $tenantMarketplace;
        $parameters['channel'] = $tenantMarketplace;
        $parameters['eanRequired'] = false;
        $parameters['categoryData']['channel'] = $tenantMarketplace;

        if (!empty($params['level'])) {
            $levels = explode("/", $params['path']);
        } else {
            $levels = $this->getLevelCategoryInsert((int) $params['parent']);
            $levels[] = $this->getCategoryByName($params['name']);
        }

        foreach ($levels as $index => $level) {
            if ($index == 0) {
                continue;
            }

            $category = $this->categoryRepositoryInterface->get($level);
            $parameters['categoryData']["id" . $index] = $level;
            $parameters['categoryData']["name" . $index] = $category->getName();
        }

        return $this->json->serialize($parameters);
    }

    /**
     * @param string $name
     * @return int
     * @throws LocalizedException
     */
    private function getCategoryByName(string $name): int
    {
        $collection = $this->collectionFactory->create()
            ->addAttributeToFilter('name', $name)
            ->setPageSize(1);

        return (int) $collection->getFirstItem()->getId();
    }

    /**
     * @param int $parentId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getLevelCategoryInsert(int $parentId): array
    {
        $level = [];
        $result = $this->categoryRepositoryInterface->get($parentId);

        if (!empty($result->getData())) {
            $path = $result->getPath();
            $level = explode("/", $path);
        }

        return $level;
    }

    /**
     * @param array $idsCategory
     * @param string $tenantMarketplace
     * @return string
     * @throws NoSuchEntityException
     */
    public function getParamsCategory(array $idsCategory, string $tenantMarketplace): string
    {
        $parameters['tenant'] = $tenantMarketplace;
        $parameters['operator'] = $tenantMarketplace;
        $parameters['channel'] = $tenantMarketplace;
        $parameters['eanRequired'] = false;
        $parameters['categoryData']['channel'] = $tenantMarketplace;

        foreach ($idsCategory as $index => $categoryId) {
            if ($index == 0) {
                continue;
            }

            $category = $this->categoryRepositoryInterface->get((int) $categoryId);
            $parameters['categoryData']["id" . $index] = $categoryId;
            $parameters['categoryData']["name" . $index] = $category->getName();
        }

        return $this->json->serialize($parameters);
    }
}
