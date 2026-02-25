<?php

namespace Omnik\Core\Helper\Checkout;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * @param $superAttributes
     * @return array
     */
    public function getSuperAttributeValues($superAttributes)
    {
        $values = [];
        $attributesString = str_replace('%5D', '', str_replace('super_attribute%5B', '', $superAttributes));
        $attributes = explode('&', $attributesString);
        foreach ($attributes as $attribute) {
            $attributeValue = explode('=', $attribute);
            $values[abs($attributeValue[0])] = $attributeValue[1];
        }

        return $values;
    }
}
