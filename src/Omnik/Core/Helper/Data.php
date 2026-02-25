<?php

declare(strict_types=1);

namespace Omnik\Core\Helper;

use Omnik\Core\Api\Data\Price\PriceQueueInterface;
use Omnik\Core\Api\Data\Stock\StockQueueInterface;
use Omnik\Core\Model\Publisher\Price\UpdatePrice;
use Omnik\Core\Model\Publisher\Stock\UpdateStock;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * @var PriceQueueInterface
     */
    private PriceQueueInterface $priceQueue;

    /**
     * @var UpdatePrice
     */
    private UpdatePrice $publisherUpdatePrice;

    /**
     * @var StockQueueInterface
     */
    private StockQueueInterface $stockQueue;

    /**
     * @var UpdateStock
     */
    private UpdateStock $publisherUpdateStock;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var StoreManagerInterface
     */
    public StoreManagerInterface $storeManager;

    const XML_PATH_ATTEMPTS = 'omnik_core/general/attempts';
    const XML_PATH_TIMEOUT_MINUTES = 'omnik_core/general/timeout_minutes';

    /**
     * @param PriceQueueInterface $priceQueue
     * @param UpdatePrice $publisherUpdatePrice
     * @param StockQueueInterface $stockQueue
     * @param UpdateStock $publisherUpdateStock
     * @param Json $json
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        PriceQueueInterface $priceQueue,
        UpdatePrice $publisherUpdatePrice,
        StockQueueInterface $stockQueue,
        UpdateStock $publisherUpdateStock,
        Json $json,
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        $this->priceQueue = $priceQueue;
        $this->publisherUpdatePrice = $publisherUpdatePrice;
        $this->stockQueue = $stockQueue;
        $this->publisherUpdateStock = $publisherUpdateStock;
        $this->json = $json;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param array $params
     * @return void
     */
    public function publisherQueueUpdatePrice(array $params)
    {
        $this->priceQueue->setEvent($params['event']);
        $this->priceQueue->setResourceType($params['resourceType']);
        $this->priceQueue->setResourceId($params['resourceId']);
        $this->priceQueue->setFromPrice($params['json']['fromPrice']);
        $this->priceQueue->setPrice($params['json']['price']);

        $this->publisherUpdatePrice->execute($this->priceQueue);
    }

    /**
     * @param array $params
     * @return void
     */
    public function publisherQueueUpdateStock(array $params)
    {
        $this->stockQueue->setEvent($params['event']);
        $this->stockQueue->setResourceType($params['resourceType']);
        $this->stockQueue->setResourceId($params['resourceId']);
        $this->stockQueue->setStock($params['json']['stock']);

        $this->publisherUpdateStock->execute($this->stockQueue);
    }

    /**
     * Get attempts configuration value
     *
     * @param int|null $storeId
     * @return int
     */
    public function getAttempts(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_ATTEMPTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get timeout minutes configuration value
     *
     * @param int|null $storeId
     * @return int
     */
    public function getTimeoutMinutes(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_TIMEOUT_MINUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get timeout in seconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getTimeoutSeconds(?int $storeId = null): int
    {
        return $this->getTimeoutMinutes($storeId) * 60;
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
