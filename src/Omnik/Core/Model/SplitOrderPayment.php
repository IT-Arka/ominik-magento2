<?php

namespace Omnik\Core\Model;

class SplitOrderPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD = 'splitorder';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD;
}
