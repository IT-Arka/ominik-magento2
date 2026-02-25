<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Config\Source\ProductVariant;

use Omnik\Core\Model\Repositories\VariantRepository;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\Serializer\Json;

class Options extends AbstractSource
{
    public const VARIANT_EMBALAGEM = 'EMBALAGEM';
    public const VARIANT_EMBALAGEM_DEFAULT = 'UN/01';

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var VariantRepository
     */
    private VariantRepository $variantRepository;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param VariantRepository $variantRepository
     * @param Json $json
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        VariantRepository $variantRepository,
        Json $json
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->variantRepository = $variantRepository;
        $this->json = $json;
    }

    /**
     * @return array|null
     */
    public function getAllOptions(): ?array
    {
        $optionDefault = [
            ['label' => self::VARIANT_EMBALAGEM_DEFAULT, 'value' => self::VARIANT_EMBALAGEM_DEFAULT]
        ];

        if ($this->getVariantOption()) {
            $optionData = $this->getVariantOption();

            foreach ($optionData['options'] as $option) {
                if ($option['code'] == self::VARIANT_EMBALAGEM_DEFAULT) {
                    continue;
                }

                $this->_options[] = [
                    'label' =>  $option['name'],
                    'value' => $option['code']
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
     * @return array
     */
    public function getVariantOption(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'variant_name',
            self::VARIANT_EMBALAGEM
        )->create();

        $variants = $this->variantRepository->getList($searchCriteria);

        $optionData = [];
        if ($variants->getTotalCount() > 0) {
            $variantData = $variants->getItems();

            foreach ($variantData as $variant) {
                $optionData = $this->json->unserialize($variant['variant_option']);
            }
        }

        return $optionData;
    }
}
