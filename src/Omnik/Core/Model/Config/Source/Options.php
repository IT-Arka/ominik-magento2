<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Config\Source;

use Omnik\Core\Api\Data\SearchResults\BrandSearchResultsInterface;
use Omnik\Core\Model\Repositories\BrandRepository;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Options extends AbstractSource
{
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var BrandRepository
     */
    private BrandRepository $brandRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param BrandRepository $brandRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        BrandRepository $brandRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->brandRepository = $brandRepository;
    }

    /**
     * @return string[][]|null
     */
    public function getAllOptions(): ?array
    {
        $optionDefault = [
            ['label' => '', 'value' => '']
        ];

        if ($this->getBrands()->getTotalCount() > 0) {
            $brandData = $this->getBrands()->getItems();

            foreach ($brandData as $brand) {
                $this->_options[] = [
                    'label' =>  $brand['brand_name'],
                    'value' => $brand['brand_name']
                ];
            }

            return array_merge($optionDefault, $this->_options);
        }

        return $optionDefault;
    }

    /**
     * @param $value
     * @return bool|mixed|string
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }

        return false;
    }

    /**
     * @return BrandSearchResultsInterface
     */
    public function getBrands(): BrandSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->brandRepository->getList($searchCriteria);
    }
}
