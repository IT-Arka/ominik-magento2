<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to https://www.omnik.com.br/ for more information.
 *
 * @Agency    Omnik Formação e Consultoria, Inc. (http://www.omnik.com.br)
 * @author    Fausto Marins <fausto.junior@omnik.com.br>
 */

namespace Omnik\Core\Helper;

use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CustomTierPrice
 *
 * @package Omnik\CustomCard\Helper
 */
class CustomTierPrice extends AbstractHelper
{
    const DEFAULT_CUSTOMER_GROUP = 32000;
    private DesignInterface $design;
    private CalculatorInterface $calculator;
    private Session $customerSession;
    private PriceCurrencyInterface $priceCurrency;
    private LoggerInterface $logger;
    private int|null $customerGroup;

    /**
     * CustomTierPrice constructor.
     *
     * @param Context $context
     * @param DesignInterface $design
     * @param CalculatorInterface $calculator
     * @param Session $customerSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        DesignInterface $design,
        CalculatorInterface $calculator,
        Session $customerSession,
        PriceCurrencyInterface $priceCurrency,
        LoggerInterface $logger
    ) {
        $this->design          = $design;
        $this->calculator      = $calculator;
        $this->customerSession = $customerSession;
        $this->priceCurrency   = $priceCurrency;
        $this->logger          = $logger;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function activeTierPrice(): bool
    {
        return $this->design->getDesignTheme()->getThemeTitle() == "Stock";
    }

    /**
     * @return int
     */
    private function getCustomerGroup(): int
    {
        if (empty($this->customerGroup)) {
            if ($this->customerSession->isLoggedIn()) {
                $this->customerGroup = $this->customerSession->getCustomer()->getGroupId();
            }

            $this->customerGroup = self::DEFAULT_CUSTOMER_GROUP;
        }

        return $this->customerGroup;
    }

    /**
     * @param $product
     * @return array
     */
    public function getTierPrices($product): array
    {
        $result = [];

        try {
            $tierPrices = $product->getTierPrice();

            foreach ($tierPrices as $tierPrice) {
                if ($tierPrice["cust_group"] != $this->getCustomerGroup()) {
                    continue;
                }

                $savePercent = $this->getSavePercent($product, $tierPrice["price"]);
                if ($savePercent < 1) continue;

                $result[] = [
                    "price_qty" => (int) $tierPrice["price_qty"],
                    "price" => $this->priceCurrency->format(number_format($tierPrice["price"], 2)),
                    "save_percent" => $this->formatPercent($savePercent),
                ];
            }
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
        }

        return $result;
    }

    /**
     * @param float $percent
     * @return string
     */
    public function formatPercent(float $percent): string
    {
        return rtrim(
            rtrim(number_format($percent, 2), '0'),
            '.'
        );
    }

    /**
     * @param $product
     * @param $newValue
     * @return float
     */
    public function getSavePercent($product, $newValue): float
    {
        return round(100 - ((100 / $product->getFinalPrice()) * $newValue));
    }
}
