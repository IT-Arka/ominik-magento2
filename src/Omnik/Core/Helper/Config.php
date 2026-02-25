<?php

namespace Omnik\Core\Helper;

use Omnik\Core\Model\AbstractIntegration;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    public const PATH_OMNIK_INTEGRATION = 'omnik_integration';
    public const PATH_ENABLE = self::PATH_OMNIK_INTEGRATION . '/general/enable';
    public const PATH_MODE = self::PATH_OMNIK_INTEGRATION . '/general/mode';
    public const PATH_URL = self::PATH_OMNIK_INTEGRATION . '/general/url';
    public const PATH_URL_CATALOG = self::PATH_OMNIK_INTEGRATION . '/general/url_catalog';
    public const PATH_URL_FREIGHT = self::PATH_OMNIK_INTEGRATION . '/general/url_freight';
    public const PATH_TOKEN = self::PATH_OMNIK_INTEGRATION . '/general/token';
    public const PATH_APPLICATION_ID = self::PATH_OMNIK_INTEGRATION . '/general/application_id';
    public const PATH_TIMEOUT = self::PATH_OMNIK_INTEGRATION . '/general/timeout';
    public const PATH_LOG_REQUESTS = self::PATH_OMNIK_INTEGRATION . '/general/log_requests';
    public const PATH_TENANT_MARKETPLACE = self::PATH_OMNIK_INTEGRATION . '/general/tenant_marketplace';

    public const PATH_SELLER_NOTIFY_ATTEMPTS = 'omnik_core/general/attampts';

    public const PATH_SELLER_NOTIFY_TIMEOUT = 'omnik_core/general/timeoutminutes';

    /**
     * @param $storeId
     * @return mixed
     */
    public function isEnabled($storeId)
    {
        return $this->scopeConfig->getValue(self::PATH_ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getApiMode()
    {
        return $this->scopeConfig->getValue(self::PATH_MODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getUrl($storeId)
    {
        return $this->scopeConfig->getValue(self::PATH_URL, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getToken($storeId)
    {
        return $this->scopeConfig->getValue(self::PATH_TOKEN, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getApplicationId($storeId)
    {
        return $this->scopeConfig->getValue(self::PATH_APPLICATION_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getTimeout($storeId)
    {
        return $this->scopeConfig->getValue(self::PATH_TIMEOUT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function isLogEnabled($storeId)
    {
        return $this->scopeConfig->getValue(self::PATH_LOG_REQUESTS, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getUrlCatalog($storeId): string
    {
        $urlCatalog = $this->scopeConfig->getValue(self::PATH_URL_CATALOG, ScopeInterface::SCOPE_STORE, $storeId);

        return !is_null($urlCatalog) && strlen(trim($urlCatalog)) > 0 ? $urlCatalog : '';
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getUrlFreight($storeId): string
    {
        $urlFreight = $this->scopeConfig->getValue(self::PATH_URL_FREIGHT, ScopeInterface::SCOPE_STORE, $storeId);

        return !is_null($urlFreight) && strlen(trim($urlFreight)) > 0 ? $urlFreight : '';
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getTenantMarketplace(int $storeId = AbstractIntegration::STORE_ID_DEFAULT): string
    {
        $tenantMarketplace = $this->scopeConfig->getValue(
            self::PATH_TENANT_MARKETPLACE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return !is_null($tenantMarketplace) && strlen(trim($tenantMarketplace)) > 0 ? $tenantMarketplace : '';
    }

    public function getAttempts($storeId): string
    {
        $attempts = $this->scopeConfig->getValue(
            self::PATH_SELLER_NOTIFY_ATTEMPTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $attempts;
    }
}
