<?php
declare(strict_types=1);

namespace Omnik\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TimeoutMinutes implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        
        for ($i = 1; $i <= 60; $i++) {
            $options[] = [
                'value' => $i,
                'label' => $i . ' ' . ($i === 1 ? __('minute') : __('minutes'))
            ];
        }
        
        return $options;
    }
}
