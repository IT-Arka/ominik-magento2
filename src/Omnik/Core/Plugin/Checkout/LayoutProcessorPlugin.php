<?php

namespace Omnik\Core\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

class LayoutProcessorPlugin
{
    /**
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(LayoutProcessor $subject, array $jsLayout)
    {
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['street'] = [
            'component' => 'Magento_Ui/js/form/components/group',
            //'label' => __('Street Address'), I removed main label
            'required' => false, //turn false because I removed main label
            'dataScope' => 'shippingAddress.street',
            'provider' => 'checkoutProvider',
            'sortOrder' => 70,
            'type' => 'group',
            'additionalClasses' => 'street',
            'children' => [
                $this->getComponent('Address *', '0', true),
                $this->getComponent('House Number *', '1', true),
                $this->getComponent('Neighborhood *', '2', true),
                $this->getComponent('Complement', '3', false)
            ]
        ];
        return $jsLayout;
    }

    /**
     * @param string $label
     * @param string $dataScope
     * @param bool $required
     * @return array
     */
    public function getComponent(string $label, string $dataScope, bool $required): array
    {
        return [
            'label' => __($label),
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => 'shippingAddress',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input'
            ],
            'dataScope' => $dataScope,
            'provider' => 'checkoutProvider',
            'validation' => ['required-entry' => $required, "min_text_length" => 1, "max_text_length" => 255],
        ];
    }
}
