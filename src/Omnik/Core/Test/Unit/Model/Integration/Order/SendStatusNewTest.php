<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model\Integration\Order;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Helper\Config as IntegrationConfig;
use Omnik\Core\Model\AbstractIntegration;
use Omnik\Core\Model\Config;
use Omnik\Core\Model\Http\Client;
use Omnik\Core\Model\Integration\Order\SendStatusNew;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * O Client HTTP e o seu Config são singletons de DI compartilhados por todas
 * as integrações. Durante o checkout a cotação de frete
 * (Freight\GetShippingRates) grava a URL de frete nesse Config e nunca a
 * restaura; o POST do pedido no mesmo request saía então para
 * `<url_freight>/v1/orders/status/new`, que o Client reescrevia para a rota
 * de produção `HUB` — inexistente no host de sandbox (HTTP 503) — e o pedido
 * nunca chegava à Omnik. O endpoint de pedidos precisa SEMPRE partir da URL
 * de pedidos (`omnik_integration/general/url`).
 *
 * @covers \Omnik\Core\Model\Integration\Order\SendStatusNew
 */
class SendStatusNewTest extends TestCase
{
    private const ORDERS_URL = 'https://api.sandbox.omnik.io/HUBSAN';
    private const FREIGHT_URL = 'https://api.sandbox.omnik.io/v1/freight';
    private const STORE_ID = 7;

    private Client&MockObject $client;
    private Config $config;
    private SendStatusNew $sendStatusNew;

    protected function setUp(): void
    {
        // Estado que GetShippingRates deixa para trás no mesmo request.
        $this->config = new Config([ConfigInterface::PARAM_URL => self::FREIGHT_URL]);

        $this->client = $this->createMock(Client::class);
        $this->client->method('getConfig')->willReturn($this->config);

        $integrationConfig = $this->createMock(IntegrationConfig::class);
        $integrationConfig->method('getUrl')->with(self::STORE_ID)->willReturn(self::ORDERS_URL);

        $this->sendStatusNew = new SendStatusNew(
            $integrationConfig,
            $this->createMock(CacheInterface::class),
            $this->client,
            $this->createMock(Json::class)
        );
    }

    public function testResetsClientUrlToOrdersBaseBeforePosting(): void
    {
        // Arrange
        $urlAtPostTime = null;
        $this->client->expects($this->once())
            ->method('postRequest')
            ->with(AbstractIntegration::PATH_ORDERS_STATUS_NEW, '{"tenant":"IAMN1"}', true)
            ->willReturnCallback(function () use (&$urlAtPostTime) {
                $urlAtPostTime = $this->config->get(ConfigInterface::PARAM_URL);
                return ['orderData' => ['orderId' => 'OMK-1']];
            });

        // Act
        $result = $this->sendStatusNew->execute('{"tenant":"IAMN1"}', 'IAMN1', self::STORE_ID);

        // Assert — a URL de frete deixada pelo request anterior não vaza para o pedido.
        $this->assertSame(self::ORDERS_URL, $urlAtPostTime);
        $this->assertSame(['orderData' => ['orderId' => 'OMK-1']], $result);
    }
}
