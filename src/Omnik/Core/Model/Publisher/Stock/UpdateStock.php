<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Publisher\Stock;

use Omnik\Core\Api\Data\Stock\StockQueueInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class UpdateStock
{
    public const TOPIC_NAME = 'stock.update';

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @param StockQueueInterface $stockQueue
     * @return void
     */
    public function execute(StockQueueInterface $stockQueue)
    {
        $this->publisher->publish(self::TOPIC_NAME, $stockQueue);
    }
}
