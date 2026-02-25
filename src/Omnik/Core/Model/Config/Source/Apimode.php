<?php

namespace Omnik\Core\Model\Config\Source;

class Apimode implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'staging', 'label' => __('Staging')],
            ['value' => 'production', 'label' => __('Production')]
        ];
    }
}
