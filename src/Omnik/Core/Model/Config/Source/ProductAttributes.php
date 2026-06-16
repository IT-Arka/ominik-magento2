<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class ProductAttributes implements OptionSourceInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {}

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Selecione um atributo --')]];

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addVisibleFilter()
            ->setOrder('attribute_code', 'ASC');

        foreach ($collection as $attribute) {
            $code = $attribute->getAttributeCode();
            if (empty($code)) {
                continue;
            }
            $label = $attribute->getFrontendLabel();
            $options[] = [
                'value' => $code,
                'label' => $label ? sprintf('%s [%s]', $label, $code) : $code,
            ];
        }

        return $options;
    }
}
