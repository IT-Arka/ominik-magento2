<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\RegionRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region as RegionResourceModel;
use Magento\Directory\Model\Region;
use Magento\Framework\Exception\NoSuchEntityException;

class RegionRepository implements RegionRepositoryInterface
{
    /**
     * @var RegionFactory
     */
    private RegionFactory $regionFactory;

    /**
     * @var RegionResourceModel
     */
    private RegionResourceModel $regionResourceModel;

    /**
     * @var Region
     */
    private Region $region;

    /**
     * @param RegionFactory $regionFactory
     * @param RegionResourceModel $regionResourceModel
     * @param Region $region
     */
    public function __construct(
        RegionFactory $regionFactory,
        RegionResourceModel $regionResourceModel,
        Region $region
    ) {
        $this->regionFactory = $regionFactory;
        $this->regionResourceModel = $regionResourceModel;
        $this->region = $region;
    }

    /**
     * @param int $regionId
     * @return \Magento\Directory\Model\Region
     * @throws NoSuchEntityException
     */
    public function getById(int $regionId): \Magento\Directory\Model\Region
    {
        $region = $this->regionFactory->create();
        $this->regionResourceModel->load($region, $regionId);

        if (!$region->getRegionId()) {
            throw new NoSuchEntityException(__('Region Id %1 doesnt exists', $regionId));
        }

        return $region;
    }

    /**
     * @param string $uf
     * @return array
     */
    public function getRegionByCode(string $uf): array
    {
        $result = [];
        $region = $this->region->loadByCode($uf, "BR");
        $result['region_id'] = $region->getRegionId();
        $result['name'] = $region->getDefaultName();
        $result['code'] = $region->getCode();

        return $result;
    }
}
