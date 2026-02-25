<?php declare(strict_types=1);
/** Copyright © Omnik. All rights reserved. */

namespace Omnik\Core\Block\Component;

use Magento\Framework\View\Element\Template;

class QtyCounter extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Magento_Theme::component/qty-counter.phtml';

    /**
     * @param Template\Context $context
     */
    public function __construct(
        Template\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getJsConfigJson(): string
    {
        return json_encode($this->getJsConfig());
    }

    /**
     * @return array
     */
    public function getJsConfig(): array
    {
        return array_merge_recursive(
            [
                'component' => 'Magento_Theme/js/component/qty-counter'
            ],
            $this->getData('js_config') ?: []
        );
    }
}
