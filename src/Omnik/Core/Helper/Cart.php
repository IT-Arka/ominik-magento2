<?php

declare(strict_types=1);

namespace Omnik\Core\Helper;

use Omnik\Core\Model\Config\Configurable\ProductsOptions;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url\Helper\Data;
use Magento\Checkout\Model\Session;

class Cart extends Data
{
    /**
     * @var ProductsOptions
     */
    private ProductsOptions $productsOptions;
    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @param ProductsOptions $productsOptions
     * @param Context $context
     * @param CustomerSessionFactory $sessionFactory
     * @param Session $session
     */
    public function __construct(
        ProductsOptions        $productsOptions,
        Context                $context,
        CustomerSessionFactory $sessionFactory,
        Session                $session
    ) {
        $sessionFactory->create();
        $this->productsOptions = $productsOptions;
        $this->checkoutSession = $session;
        parent::__construct($context);
    }

    /**
     * @param array $result
     * @return array
     */
    private function getArrayItems(array $result): array
    {
        $resultItem = [];

        foreach ($result as $seller => $values) {
            foreach ($values as $vendor => $val) {
                $c = 0;
                foreach ($val as $value) {
                    $resultItem[$seller][$vendor][$c] = $value;
                    $c++;
                }
            }
        }

        return $resultItem;
    }

    /**
     * @param array $items
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getItems(array $items): array
    {
        $result = $this->productsOptions->separeItemsByVendor($items);
        return $this->getArrayItems($result);
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSummaryCount()
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getItemsQty()) {
            return true;
        }
        return false;
    }
}
