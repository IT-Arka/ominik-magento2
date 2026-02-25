<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface RegionRepositoryInterface
{

    /**
     * @param int $regionId
     * @return \Magento\Directory\Model\Region
     */
    public function getById(int $regionId): \Magento\Directory\Model\Region;

    /**
     * @param string $uf
     * @return array
     */
    public function getRegionByCode(string $uf): array;
}
