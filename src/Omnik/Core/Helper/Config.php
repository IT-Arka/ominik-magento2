<?php

namespace Omnik\Core\Helper;

use Omnik\Core\Model\AbstractIntegration;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
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
    public const PATH_SELLER_NOTIFY_TIMEOUT  = 'omnik_core/general/timeoutminutes';

    // Address mapping
    public const PATH_ADDR_IDX_ADDRESS      = 'omnik_integration/address_mapping/street_index_address';
    public const PATH_ADDR_IDX_NUMBER       = 'omnik_integration/address_mapping/street_index_number';
    public const PATH_ADDR_IDX_COMPLEMENT   = 'omnik_integration/address_mapping/street_index_complement';
    public const PATH_ADDR_IDX_NEIGHBORHOOD = 'omnik_integration/address_mapping/street_index_neighborhood';

    // Product attribute mapping
    public const PATH_ATTR_TENANT          = 'omnik_integration/product_attributes/attr_tenant';
    public const PATH_ATTR_SKU_ID          = 'omnik_integration/product_attributes/attr_sku_id';
    public const PATH_ATTR_VARIANT_SELLER  = 'omnik_integration/product_attributes/attr_variant_seller';
    public const PATH_ATTR_VARIANT_COLOR   = 'omnik_integration/product_attributes/attr_variant_color';
    public const PATH_ATTR_VARIANT_EMBAL   = 'omnik_integration/product_attributes/attr_variant_embalagem';
    public const PATH_ATTR_VARIANT_TAMANHO = 'omnik_integration/product_attributes/attr_variant_tamanho';
    public const PATH_ATTR_ERP_CODE        = 'omnik_integration/product_attributes/attr_erp_code';
    public const PATH_ATTR_BRAND           = 'omnik_integration/product_attributes/attr_brand';

    // Boleto extra days
    public const PATH_BOLETO_EXTRA_DAYS = 'omnik_integration/general/boleto_extra_days';

    public function __construct(
        Context $context,
        private readonly EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function isEnabled($storeId)
    {
        return $this->scopeConfig->getValue(self::PATH_ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    // -------------------------------------------------------------------------
    // Address mapping
    // -------------------------------------------------------------------------

    public function getStreetIndexes(int $storeId): array
    {
        return [
            'address'      => (int)($this->scopeConfig->getValue(self::PATH_ADDR_IDX_ADDRESS, ScopeInterface::SCOPE_STORE, $storeId) ?? 0),
            'number'       => (int)($this->scopeConfig->getValue(self::PATH_ADDR_IDX_NUMBER, ScopeInterface::SCOPE_STORE, $storeId) ?? 1),
            'complement'   => (int)($this->scopeConfig->getValue(self::PATH_ADDR_IDX_COMPLEMENT, ScopeInterface::SCOPE_STORE, $storeId) ?? 2),
            'neighborhood' => (int)($this->scopeConfig->getValue(self::PATH_ADDR_IDX_NEIGHBORHOOD, ScopeInterface::SCOPE_STORE, $storeId) ?? 3),
        ];
    }

    // -------------------------------------------------------------------------
    // Product attribute mapping
    // -------------------------------------------------------------------------

    public function getAttrTenant(int $storeId = 0): string
    {
        return (string)($this->scopeConfig->getValue(self::PATH_ATTR_TENANT, ScopeInterface::SCOPE_STORE, $storeId) ?: 'tenant');
    }

    public function getAttrSkuId(int $storeId = 0): string
    {
        return (string)($this->scopeConfig->getValue(self::PATH_ATTR_SKU_ID, ScopeInterface::SCOPE_STORE, $storeId) ?: 'sku_id_omnik');
    }

    public function getAttrVariantSeller(int $storeId = 0): string
    {
        return (string)($this->scopeConfig->getValue(self::PATH_ATTR_VARIANT_SELLER, ScopeInterface::SCOPE_STORE, $storeId) ?: 'variant_seller');
    }

    public function getAttrVariantColor(int $storeId = 0): string
    {
        return (string)($this->scopeConfig->getValue(self::PATH_ATTR_VARIANT_COLOR, ScopeInterface::SCOPE_STORE, $storeId) ?: 'variant_color');
    }

    public function getAttrVariantEmbalagem(int $storeId = 0): string
    {
        return (string)($this->scopeConfig->getValue(self::PATH_ATTR_VARIANT_EMBAL, ScopeInterface::SCOPE_STORE, $storeId) ?: 'variant_embalagem');
    }

    public function getAttrVariantTamanho(int $storeId = 0): string
    {
        return (string)($this->scopeConfig->getValue(self::PATH_ATTR_VARIANT_TAMANHO, ScopeInterface::SCOPE_STORE, $storeId) ?: 'variant_tamanho');
    }

    public function getAttrErpCode(int $storeId = 0): string
    {
        return (string)($this->scopeConfig->getValue(self::PATH_ATTR_ERP_CODE, ScopeInterface::SCOPE_STORE, $storeId) ?: 'erp_code');
    }

    public function getAttrBrand(int $storeId = 0): string
    {
        return (string)($this->scopeConfig->getValue(
            self::PATH_ATTR_BRAND,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'brand');
    }

    public function getBoletoExtraDays(int $storeId = 0): int
    {
        return (int)($this->scopeConfig->getValue(self::PATH_BOLETO_EXTRA_DAYS, ScopeInterface::SCOPE_STORE, $storeId) ?? 3);
    }

    public function isProductionMode(int $storeId = 0): bool
    {
        return $this->scopeConfig->getValue(self::PATH_MODE, ScopeInterface::SCOPE_STORE, $storeId) === 'production';
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
     * @return string
     */
    public function getToken($storeId): string
    {
        $encrypted = $this->scopeConfig->getValue(self::PATH_TOKEN, ScopeInterface::SCOPE_STORE, $storeId);
        if (empty($encrypted)) {
            return '';
        }
        return (string)$this->encryptor->decrypt($encrypted);
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

        return (string)($attempts ?? '3');
    }
}
