<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Model\Integration\Product;

use Omnik\Core\Model\Integration\Product\PublishResultBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Omnik\Core\Model\Integration\Product\PublishResultBuilder
 */
class PublishResultBuilderTest extends TestCase
{
    private PublishResultBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PublishResultBuilder();
    }

    public function testBuildPublishedMatchesContractShape(): void
    {
        $skus = [
            $this->builder->skuResult(PublishResultBuilder::RESULT_PUBLISHED, 'omnik-sku-1', 'mage-sku-1'),
            $this->builder->skuResult(PublishResultBuilder::RESULT_PUBLISHED, 'omnik-sku-2', 'mage-sku-2'),
        ];

        $payload = $this->builder->buildPublished('omnik-prod-1', 'mage-prod-1', $skus);

        // Envelope is a single OBJECT (not a list).
        $this->assertSame('omnik-prod-1', $payload['productId']);
        $this->assertSame('mage-prod-1', $payload['marketplaceId']);
        $this->assertSame('published', $payload['result']);
        $this->assertArrayNotHasKey('messages', $payload);

        $this->assertSame(
            ['result' => 'published', 'skuId' => 'omnik-sku-1', 'marketplaceId' => 'mage-sku-1'],
            $payload['skus'][0]
        );
    }

    public function testNotPublishedSkuOmitsMarketplaceId(): void
    {
        // A not-published sku carries only result + skuId (no marketplaceId).
        $sku = $this->builder->skuResult(PublishResultBuilder::RESULT_NOT_PUBLISHED, 'omnik-sku-9');

        $this->assertSame(
            ['result' => 'not-published', 'skuId' => 'omnik-sku-9'],
            $sku
        );
        $this->assertArrayNotHasKey('marketplaceId', $sku);
    }

    public function testBuildNotPublishedProduct(): void
    {
        $payload = $this->builder->buildNotPublished('omnik-prod-1', '', [
            $this->builder->skuResult(PublishResultBuilder::RESULT_NOT_PUBLISHED, 'omnik-sku-1'),
        ]);

        $this->assertSame('not-published', $payload['result']);
        $this->assertSame('', $payload['marketplaceId']);
        $this->assertSame('not-published', $payload['skus'][0]['result']);
    }

    public function testSkusAreReindexed(): void
    {
        $skus = [7 => $this->builder->skuResult(PublishResultBuilder::RESULT_PUBLISHED, 'o', 'm')];
        $payload = $this->builder->buildPublished('p', 'm', $skus);

        $this->assertArrayHasKey(0, $payload['skus']);
    }
}
