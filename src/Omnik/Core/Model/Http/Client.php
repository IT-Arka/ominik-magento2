<?php

namespace Omnik\Core\Model\Http;

use AllowDynamicProperties;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Model\Config;
use Omnik\Core\Helper\Config as ConfigHelper;
use Exception;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Ramsey\Uuid\Uuid;
use Laminas\Http\Client as LaminasClient;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Http\Client\Adapter\Curl;
use Psr\Log\LoggerInterface;

#[AllowDynamicProperties] class Client extends \Laminas\Http\Client
{
    /**
     * @var Config|null
     */

    /**
     * @var LaminasClient
     */
    private LaminasClient $httpLaminaClient;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ConfigHelper $configHelper
     * @param LaminasClient $httpLaminaClient
     * @param Json $json
     * @param Config $config
     * @param Curl $adapter
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface   $storeManager,
        ConfigHelper            $configHelper,
        LaminasClient           $httpLaminaClient,
        Json                    $json,
        Config                  $config,
        Curl                    $adapter,
        LoggerInterface         $logger
    ) {
        $this->httpLaminaClient = $httpLaminaClient;
        $this->json = $json;
        $this->config = $config;
        $this->adapter = $adapter;
        $this->configHelper = $configHelper;
        $this->storeManagers = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param Config $config
     * @return Config|null
     */
    public function setConfig(Config $config): ?Config
    {
        if (!$this->config) {
            $this->config = $config;
        }

        return $this->config;
    }

    /**
     * @return Config|null
     */
    public function getConfig(): ?Config
    {
        return $this->config;
    }

    /**
     * @param $path
     * @param $params
     * @param mixed $returnJson
     *
     * @return mixed
     * @throws Exception
     */
    public function postRequest($path, $params, $returnJson = true): ?array
    {
        $method = Request::METHOD_POST;

        return $this->defaultRequest($path, $method, $params, $returnJson);
    }

    /**
     * @param $path
     * @param $params
     * @param bool $returnJson
     * @param array $additionalHeaders
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function putRequest($path, $params = [], bool $returnJson = true, $additionalHeaders = [])
    {
        $method = Request::METHOD_PUT;
        // $jsonRequest = null;
        // if (!empty($params)) {
        //     $jsonRequest = $params;
        // }

        return $this->defaultRequest($path, $method, $params, $returnJson, $additionalHeaders);
    }

    /**
     * @param $path
     * @param bool $returnJson
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function deleteRequest($path, bool $returnJson = true)
    {
        $method = Request::METHOD_DELETE;

        return $this->defaultRequest($path, $method, "", $returnJson);
    }

    /**
     * @param $path
     * @param bool $returnJson
     * @param array $additionalHeaders
     * @return array|bool[]|mixed|string|null
     * @throws Exception
     */
    public function getNewRequest($path, bool $returnJson = true, $additionalHeaders = []): mixed
    {

        $method = Request::METHOD_GET;

        return $this->defaultRequest($path, $method, "", $returnJson, $additionalHeaders);
    }

    /**
     * Handle Curl exceptions
     *
     * @param mixed $requestId
     * @param $message
     * @param mixed $httpStatus
     *
     */
    protected function saveError($requestId, $message, $httpStatus)
    {
        $this->logger->info($this->json->serialize([
            'requestId' => $requestId,
            'errorCode' => $httpStatus,
            'errorType' => 'CURL',
            'errorMsg' => $message
        ]));
        // $logger = $this->config->get(ConfigInterface::PARAM_LOGGER);
        // $logger->error(
        //     $this->json->serialize([
        //         'requestId' => $requestId,
        //         'errorCode' => $httpStatus,
        //         'errorType' => 'CURL',
        //         'errorMsg' => $message
        //     ])
        // );
    }

    /**
     * Logs the API request
     *
     * @param $requestId
     * @param $requestUrl
     * @param mixed $jsonParams
     */
    private function logRequest($requestId, $requestUrl, $jsonParams)
    {
        $logger = $this->config->get(ConfigInterface::PARAM_LOGGER);
        if ($this->config->get(ConfigInterface::PARAM_LOGS_ENABLED)) {
            $info = [
                'requestId' => $requestId,
                'requestUrl' => $requestUrl,
                'requestParams' => $jsonParams,
            ];

            $logger->info($this->json->serialize($info));
        }
    }

    /**
     * Logs the request if logging is enabled on module
     *
     * @param string $requestId
     * @param int $httpStatus
     * @param string $result
     * @param array|null $headers
     */
    private function logResponseIfEnabled(
        string $requestId,
        int $httpStatus,
        string $result,
        ?array $headers = null
    ) {
        $logger = $this->config->get(ConfigInterface::PARAM_LOGGER);
        if ($this->config->get(ConfigInterface::PARAM_LOGS_ENABLED)) {
            $logger->info(
                $this->json->serialize([
                    'requestId' => $requestId,
                    'responseStatus' => $httpStatus,
                    'response' => $result,
                    'headers' => $headers,
                ])
            );
        }
    }

    /**
     * Returns a custom user agent
     *
     * @return string
     */
    private function getUserAgent(): string
    {
        return
            $this->config->get(ConfigInterface::PARAM_APPLICATION_NAME) .
            ' ' .
            $this->config->get(ConfigInterface::PARAM_USER_AGENT_SUFFIX) .
            $this->config->get(ConfigInterface::PARAM_LIB_VERSION);
    }

    /**
     * @param string $method
     * @return string[]|null
     */
    public function getHeaders(string $method): ?array
    {
        $storeId = $this->storeManagers->getStore()->getId();
        $token = $this->configHelper->getToken($storeId);
        if (empty($token) && $method != Request::METHOD_GET) {
            return null;
        }

        // Prepare request headers
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => $this->getUserAgent()
        ];
        // set authorization credentials
        if (!empty($token)) {
            $headers['token'] = $token;
            $headers['application_id'] = $this->configHelper->getApplicationId($storeId);
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

    /**
     * Returns the response in the desired format
     *
     * @param string $requestId
     * @param string $result
     * @param bool $returnJson
     * @return mixed|string|null
     */
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

    /**
     * Set request parameters
     *
     * @param LaminasClient $client (by ref)
     * @param string $method
     * @param $param
     */
    private function setParameters(LaminasClient $client, string $method, $param)
    {
        if ($method == Request::METHOD_GET && $param) {
            $client->setParameterGet($param);
        } elseif ($param) {
            if (is_array($param)) {
                $param = $this->json->serialize($param);
            }

            $client->setRawBody($param);
        }
    }

    /**
     * @param $path
     * @param $method
     * @param $params
     * @param $returnJson
     * @param array $additionalHeaders
     * @return array|mixed|string|null
     * @throws Exception
     */
    private function defaultRequest($path, $method, $params, $returnJson, $additionalHeaders = [])
    {
        $storeId = $this->storeManagers->getStore()->getId();
        $token = $this->configHelper->getToken($storeId);
        $url = $this->config->get("url");
        try {
            if($url){
                $requestUrl = $url . $path;
            }else{
                $requestUrl = $this->configHelper->getUrl($storeId) . $path;
            }

            $timeout = $this->config->get(ConfigInterface::PARAM_TIMEOUT);
            $requestId = Uuid::uuid1()->toString();

            if (strpos($requestUrl, 'catalog/products/publishResult') !== false) {
                $requestUrl = str_replace('HUBSAN/', '', $requestUrl);
            }

            if (strpos($requestUrl, 'orders/status/new') !== false) {
                $requestUrl = str_replace('v1/freight', 'HUB', $requestUrl);
            }

            if (strpos($requestUrl, 'orders/marketplaceid') !== false) {
                $requestUrl = str_replace('v1/freight', 'HUB', $requestUrl);
            }

            // log the request
            $this->logRequest($requestId, $requestUrl, $params);

            // $request = new Request();
            // $request->setMethod($method);
            // $request->setUri($requestUrl);
            // $request->getHeaders()->addHeaders($this->getHeaders($method));
            // $request->setContent($request->getPost()->toString());
            $headers = $this->getHeaders($method);
            if (isset($headers) == null) {
                $headers = [];
            } else {
                $headers = array_merge($headers, $additionalHeaders);
            }
            // Sends the request

            $config = ['adapter' => \Laminas\Http\Client\Adapter\Curl::class,
                            'sslverifypeer' => false];

            $client = new LaminasClient($requestUrl, $config);
            $client->setMethod($method);
            $client->setUri($requestUrl);

            if (is_array($params)) {
                // $client->setParameterPost(json_encode($params));
                $client->setRawBody(json_encode($params));
            }

            if (is_string($params) && !empty($params)) {
                $client->setRawBody($params);
            }

            $client->setOptions([
                'timeout' => ($timeout ?? 360)
            ]);
            $client->setHeaders($headers);
            $response = $client->send();

            $httpStatus = $response->getStatusCode();
            $result = $response->getBody();

            // Logs the response
            $this->logResponseIfEnabled($requestId, $httpStatus, $result, $headers);

            // result not 200 throw error
            if ($httpStatus !== 200) {
                $this->saveError($requestId, $response->getReasonPhrase(), $httpStatus);

                return [
                    'fails' => true,
                    'httpStatus' => $httpStatus,
                    'response' => $result
                ];
            }
            if ($response->getStatusCode()) {
                return $this->returnResponse($requestId, $result, $returnJson);
            }


        } catch (\Exception $e) {
          //TODO: Log
            return ['fails' => true];
        }

        return null;
    }
}
