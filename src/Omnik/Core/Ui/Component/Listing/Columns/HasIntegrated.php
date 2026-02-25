<?php

namespace Omnik\Core\Ui\Component\Listing\Columns;

use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Model\SplitOrderPayment;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class HasIntegrated extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        foreach ($dataSource['data']['items'] as $key => $item) {
            $dataSource['data']['items'][$key][SplitOrderInterface::SPLIT_ORDER_HAS_INTEGRATED] = __('Not integrated with OMNIK');
            if ($item['has_integrated_omnik'] == '1') {
                $dataSource['data']['items'][$key][SplitOrderInterface::SPLIT_ORDER_HAS_INTEGRATED] = __("Integrated with OMNIK");
            }
            if ($item['payment_method'] != SplitOrderPayment::METHOD) {
                $dataSource['data']['items'][$key]['has_integrated_omnik'] = null;
            }
        }
        return parent::prepareDataSource($dataSource);
    }
}
