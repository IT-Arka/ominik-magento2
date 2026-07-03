<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration\Product;

/**
 * Builds the payload for PUT /v1/catalog/products/publishResult in the exact
 * shape the Omnik API expects, per the contract provided by the client:
 *
 *   {
 *     "productId": "17v520032B50g569",     // Omnik product id
 *     "marketplaceId": "70076000233",       // Magento product id (entity_id)
 *     "result": "published",                // published | not-published
 *     "skus": [
 *       {
 *         "result": "published",            // published | not-published
 *         "marketplaceId": "MP00000604597", // Magento sku id (entity_id) — omitted when not-published
 *         "skuId": "17C5f493945D5616"        // Omnik sku id
 *       }
 *     ]
 *   }
 *
 * Note: the envelope is a single OBJECT (not an array), `result` is
 * published/not-published (not SUCCESS/ERROR), the sku list key is `skus`
 * (not skuResults), and there is no `messages` field.
 *
 * Centralizing the format here keeps every caller (Integrate success/error,
 * ProcessMatch match/no-match) consistent and prevents format drift.
 */
class PublishResultBuilder
{
    public const RESULT_PUBLISHED = 'published';
    public const RESULT_NOT_PUBLISHED = 'not-published';

    /**
     * Build a publish payload.
     *
     * @param string $productId Omnik product id
     * @param string $marketplaceId Magento product id (entity_id)
     * @param string $result self::RESULT_PUBLISHED | self::RESULT_NOT_PUBLISHED
     * @param array $skus list of items built via skuResult()
     * @return array payload ready for Publish::execute()
     */
    public function build(string $productId, string $marketplaceId, string $result, array $skus): array
    {
        return [
            'productId' => $productId,
            'marketplaceId' => $marketplaceId,
            'result' => $result,
            'skus' => array_values($skus),
        ];
    }

    /**
     * Convenience: a fully published product.
     */
    public function buildPublished(string $productId, string $marketplaceId, array $skus): array
    {
        return $this->build($productId, $marketplaceId, self::RESULT_PUBLISHED, $skus);
    }

    /**
     * Convenience: a product that failed to publish.
     */
    public function buildNotPublished(string $productId, string $marketplaceId, array $skus): array
    {
        return $this->build($productId, $marketplaceId, self::RESULT_NOT_PUBLISHED, $skus);
    }

    /**
     * Build a single sku entry.
     *
     * For a published sku, pass the Magento sku id ($marketplaceId) and the Omnik
     * sku id ($skuId). For a not-published sku, omit $marketplaceId (pass '') —
     * the contract sends only result + skuId in that case.
     *
     * @param string $result self::RESULT_PUBLISHED | self::RESULT_NOT_PUBLISHED
     * @param string $skuId Omnik sku id
     * @param string $marketplaceId Magento sku id (entity_id); '' to omit (not-published)
     * @return array
     */
    public function skuResult(string $result, string $skuId, string $marketplaceId = ''): array
    {
        $entry = [
            'result' => $result,
            'skuId' => $skuId,
        ];

        if ($marketplaceId !== '') {
            $entry['marketplaceId'] = $marketplaceId;
        }

        return $entry;
    }
}
