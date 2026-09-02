<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model\Integration\Order;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Helper\Config as IntegrationConfig;
use Omnik\Core\Model\Config;
use Omnik\Core\Model\Http\Client;
use Omnik\Core\Model\Integration\Order\GetOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Mesmo contrato de SendStatusNewTest para a consulta de pedido por
 * marketplaceId: o Config compartilhado do Client pode carregar a URL de
 * frete de um request anterior; a consulta deve partir da URL de pedidos.
 *
 * @covers \Omnik\Core\Model\Integration\Order\GetOrder
 */
class GetOrderTest extends TestCase
{
    private const ORDERS_URL = 'https://api.sandbox.omnik.io/HUBSAN';
    private const FREIGHT_URL = 'https://api.sandbox.omnik.io/v1/freight';
    private const STORE_ID = 3;

    private Client&MockObject $client;
    private Config $config;
    private GetOrder $getOrder;

    protected function setUp(): void
    {
        $this->config = new Config([ConfigInterface::PARAM_URL => self::FREIGHT_URL]);

        $this->client = $this->createMock(Client::class);
        $this->client->method('getConfig')->willReturn($this->config);

        $integrationConfig = $this->createMock(IntegrationConfig::class);
        $integrationConfig->method('getUrl')->with(self::STORE_ID)->willReturn(self::ORDERS_URL);

        $this->getOrder = new GetOrder(
            $integrationConfig,
            $this->createMock(CacheInterface::class),
            $this->client,
            $this->createMock(Json::class)
        );
    }

    public function testResetsClientUrlToOrdersBaseBeforeFetching(): void
    {
        // Arrange
        $urlAtRequestTime = null;
        $this->client->expects($this->once())
            ->method('getNewRequest')
            ->with(
                '/v1/orders/marketplaceid/000000123',
                true,
                [ConfigInterface::PARAM_SELLER => 'IAMN1']
            )
            ->willReturnCallback(function () use (&$urlAtRequestTime) {
                $urlAtRequestTime = $this->config->get(ConfigInterface::PARAM_URL);
                return ['orderId' => 'OMK-1'];
            });

        // Act
        $result = $this->getOrder->execute('IAMN1', self::STORE_ID, '000000123');

        // Assert
        $this->assertSame(self::ORDERS_URL, $urlAtRequestTime);
        $this->assertSame(['orderId' => 'OMK-1'], $result);
    }
}
