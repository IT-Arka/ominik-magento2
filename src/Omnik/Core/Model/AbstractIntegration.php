<?php

namespace Omnik\Core\Model;

use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Helper\Config as IntegrationConfig;
use Omnik\Core\Model\Http\Client;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class AbstractIntegration
{
    public const LIB_VERSION = '1.0.0';
    public const API_VERSION = 'v1';
    public const DEFAULT_LOGGER_NAME = 'integration';
    public const USER_AGENT_SUFFIX = 'omnik-integration-sdk/';
    public const APPLICATION_NAME = 'Omnik Integration SDK';
    public const PREFIX_PROD = '/HUB';
    public const PREFIX_HOMOL = '/HUBHOM';
    public const OFFSET_VALUE_DEFAULT = 0;
    public const LIMIT_VALUE_DEFAULT = 10;
    public const STORE_ID_DEFAULT = 1;

    public const PATH = "/v1/shoppers/";
    public const PATH_GET_BY_DOCUMENT = "/v1/shoppers/document/";
    public const PATH_UPDATE_BY_DOCUMENT = "/v1/shoppers/document/";
    public const PATH_GET_RESTRICTION_SHOPPER = '/v1/restrictionShopper/';
    public const PATH_GET_RESTRICTION_SUPPLIER = '/v1/restrictionSupplier/';
    public const INTEGRATION_SELLER_PATH = '/v1/sellers';
    public const INTEGRATION_SELLER_ZIPCODE_PATH = '/v1/sellerZipCode';
    public const INTEGRATION_DISTRIBUTOR_FREIGHT = '/v1/distributorFreight';
    public const INTEGRATION_BRAND_PATH = '/v1/brands';
    public const GET_COMMERCIAL_CLASSIFICATION = '/v1/commercialclassification';
    public const INTEGRATION_VARIANTS_PATH = '/v1/variants';
    public const INTEGRATION_PRODUCT_PATH = '/catalog-products';
    public const PATH_PRODUCT_MODERATED = '/v1/products/moderated/';
    public const PATH_PRODUCT_PUBLISH = '/v1/catalog/products/publishResult';
    public const PATH_PRODUCT_SKUS = '/v1/products/skus';
    public const PATH_PRODUCT = '/v1/products/';
    public const PATH_GET_CATEGORIES = '/v1/categories/marketplaces/{{marketplaceId}}/{{marketplace_category_id}}';
    public const PATH_SEND_CATEGORIES = '/v1/categories/marketplaces/';
    public const PATH_UPDATE_CATEGORIES = '/v1/categories/marketplaces/{{marketplaceId}}/{{marketplace_category_id}}';
    public const PATH_ORDERS_STATUS_NEW = '/v1/orders/status/new';
    public const PATH_ORDERS_STATUS_APPROVED = '/v1/orders/marketplaceid/{{marketplaceId}}/status/approved';
    public const PATH_ORDERS_STATUS_NOTAPPROVED = '/v1/orders/marketplaceid/{{marketplaceId}}/status/notapproved';
    public const PATH_ORDERS_MARKETPLACE = '/v1/orders/marketplaceid/{{marketplaceId}}';
    public const PATH_CUSTOMER_SERVICES = '/v1/orders/customerservice';
    public const PATH_CUSTOMER_SERVICES_SUBJECTS = '/v1/orders/customerservice/subjects';
    public const PATH_VENDOR_TEAM_LIST = '/v1/vendor/team/';
    public const PATH_VENDOR_BY_CODE = '/v1/vendor/code/';
    public const PATH_VENDOR_SHOPPER_BY_CODE = '/v1/vendor/shopper/code/';
    public const PATH_VENDOR_LIST = '/v1/vendor';
    public const PATH_FREIGHT_ALLSELLERS = '/quoteByProxy/allSellers';

    protected IntegrationConfig $integrationConfig;
    protected CacheInterface $cache;
    protected Client $httpClient;
    protected ?Config $config = null;
    protected ?Logger $logger = null;
    private Json $json;
    private Client $client;

    /**
     * @param IntegrationConfig $integrationConfig
     * @param CacheInterface $cache
     * @param Client $httpClient
     * @param Json $json
     */
    public function __construct(
        IntegrationConfig $integrationConfig,
        CacheInterface $cache,
        Client $client,
        Json $json
    ) {
        $this->integrationConfig = $integrationConfig;
        $this->cache = $cache;
        $this->client = $client;
        $this->json = $json;

    }

    public function initialize(int $storeId = self::STORE_ID_DEFAULT): static
    {
        if (!$this->client) {
            $this->config = new Config();
            $this->config->set(ConfigInterface::PARAM_URL, $this->getUrl($storeId));
            $this->config->set(ConfigInterface::PARAM_TOKEN, $this->integrationConfig->getToken($storeId));
            $this->config->set(ConfigInterface::PARAM_APPLICATION_ID, $this->integrationConfig->getApplicationId($storeId));
            $this->config->set(ConfigInterface::PARAM_TIMEOUT, $this->integrationConfig->getTimeout($storeId));
            $this->config->set(ConfigInterface::PARAM_API_VERSION, self::API_VERSION);
            $this->config->set(ConfigInterface::PARAM_LIB_VERSION, self::LIB_VERSION);
            $this->config->set(ConfigInterface::PARAM_USER_AGENT_SUFFIX, self::USER_AGENT_SUFFIX);
            $this->config->set(ConfigInterface::PARAM_LOGS_ENABLED, $this->integrationConfig->isLogEnabled($storeId));
            $this->config->set(ConfigInterface::PARAM_LOGGER, $this->getLogger());

            $this->client->setConfig($this->config);

            // Configuração específica para o Laminas HTTP Client
            $this->client->setUri($this->config->get(ConfigInterface::PARAM_URL));
            $this->client->setOptions(['adapter' => 'Laminas\Http\Client\Adapter\Curl', 'sslverifypeer' => false]);
            $this->client->setMethod('GET');
            $this->client->setHeaders(['User-Agent' => $this->getUserAgent()]);
            $this->client->setRawBody('');

            $this->client->setOptions(['timeout' => ($this->config->get(ConfigInterface::PARAM_TIMEOUT) ?? 360)]);
            $this->client->getRequest()->setUri($this->config->get(ConfigInterface::PARAM_URL));
            $this->client->getRequest()->setMethod($this->client->getMethod());
            $this->client->getRequest()->setContent($this->client->getRequest()->getContent());
            $this->client->getRequest()->getHeaders()->addHeaders($this->getHeaders($this->client->getMethod()));
            $this->client->getUri()->setPath('/');
            $this->client->getUri()->setQuery('');
        }

        return $this;
    }

    public function getClient(int $storeId = self::STORE_ID_DEFAULT): Client
    {
        $this->getConfig($storeId);

        if (!$this->client) {
            $this->client = new Client($this->httpClient, $this->json, $this->config);
        }

        return $this->client;
    }

    public function getLogger(): Logger
    {
        if (!$this->logger) {
            $filePath = '/var/log/' . self::DEFAULT_LOGGER_NAME . '.log';
            $this->logger = new Logger(self::DEFAULT_LOGGER_NAME);
            $this->logger->pushHandler(new StreamHandler(BP . $filePath, Logger::INFO));
        }

        return $this->logger;
    }

    public function getConfig(int $storeId = self::STORE_ID_DEFAULT): ?Config
    {
        if (!$this->config) {
            $this->initialize($storeId);
        }

        return $this->config;
    }

    public function isEnabled(int $storeId = self::STORE_ID_DEFAULT)
    {
        return $this->integrationConfig->isEnabled($storeId);
    }

    public function getUrl($storeId): string
    {
        return $this->integrationConfig->getUrl($storeId);
    }

    public function getUrlCatalog(int $storeId = self::STORE_ID_DEFAULT): string
    {
        return $this->integrationConfig->getUrlCatalog($storeId);
    }

    public function getUrlFreight(int $storeId = self::STORE_ID_DEFAULT): string
    {
        return $this->integrationConfig->getUrlFreight($storeId);
    }

    private function getUserAgent(): string
    {
        return
            $this->config->get(ConfigInterface::PARAM_APPLICATION_NAME) .
            ' ' .
            $this->config->get(ConfigInterface::PARAM_USER_AGENT_SUFFIX) .
            $this->config->get(ConfigInterface::PARAM_LIB_VERSION);
    }

    private function getHeaders(string $method): ?array
    {
        $token = $this->config->get(ConfigInterface::PARAM_TOKEN);
        if (empty($token) && $method != 'GET') {
            return null;
        }

        $headers = ['Content-Type' => 'application/json', 'User-Agent' => $this->getUserAgent()];

        if (!empty($token)) {
            $headers['token'] = $token;
            $headers['application_id'] = $this->config->get(ConfigInterface::PARAM_APPLICATION_ID);
        }

        $sellerId = $this->config->get(ConfigInterface::PARAM_SELLER);
        if (isset($sellerId) && !empty($sellerId)) {
            $headers['seller'] = $sellerId;
        }

        $authorization = $this->config->get(ConfigInterface::PARAM_TOKEN_AUTHORIZATION);
        if (isset($authorization) && !empty($authorization)) {
            $headers['Authorization'] = 'Bearer ' . $authorization;
        }

        return $headers;
    }

    private function returnResponse(string $requestId, string $result, bool $returnJson = true)
    {
        if ($returnJson) {
            $jsonResponse = $this->json->unserialize($result);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger = $this->config->get(ConfigInterface::PARAM_LOGGER);
                $logger->info(
                    $this->json->serialize([
                        'requestId' => $requestId,
                        'errorCode' => json_last_error(),
                        'errorType' => 'JSON',
                        'response' => $result,
                    ])
                );

                return null;
            }

            return $jsonResponse;
        }

        return $result;
    }
}
