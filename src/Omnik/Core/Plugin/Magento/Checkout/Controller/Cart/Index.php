<?php

declare(strict_types=1);

namespace Omnik\Core\Plugin\Magento\Checkout\Controller\Cart;

use Omnik\Core\Model\Cart\Items;

class Index
{

    /**
     * @var Items
     */
    private Items $items;

    /**
     * @param Items $items
     */
    public function __construct(
        Items $items
    ) {
        $this->items = $items;
    }

    /**
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(
        \Magento\Checkout\Controller\Cart\Index $subject,
        $result
    ) {
        $this->items->updatePriceCartItems();
        return $result;
    }
}
