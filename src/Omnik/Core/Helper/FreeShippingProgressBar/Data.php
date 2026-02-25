<?php

namespace Omnik\Core\Helper\FreeShippingProgressBar;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     *  Uses default checkout cart section
     */
    protected const CONFIG_PATH_IS_ENABLED_MODULE = 'checkout/cart/freeshipping_progress_enable';

    protected const CONFIG_PATH_PROGRESS_MIN_TOTAL = 'checkout/cart/freeshipping_progress_min_total';

    protected const CONFIG_PATH_FREESHIPPING_ACTIVE ='carriers/freeshipping/active';

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var Session $session
     */
    protected $session;

    /**
     * @var PriceCurrencyInterface $priceCurrency
     */
    protected $priceCurrency;

    /**
     * Countdown constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $session
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        ScopeConfigInterface   $scopeConfig,
        Session                $session,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Validate if current order total is free shipping eligible
     *
     * @return bool
     */
    public function isFreeShippingEligible(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::CONFIG_PATH_IS_ENABLED_MODULE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return float
     */
    public function getFreeShippingMinValue(): float
    {
        return (float)$this->scopeConfig->getValue(self::CONFIG_PATH_PROGRESS_MIN_TOTAL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isFreeShippingActive(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::CONFIG_PATH_FREESHIPPING_ACTIVE, ScopeInterface::SCOPE_STORE);
    }
}
