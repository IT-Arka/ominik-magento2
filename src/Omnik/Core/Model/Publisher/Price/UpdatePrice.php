<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Publisher\Price;

use Omnik\Core\Api\Data\Price\PriceQueueInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class UpdatePrice
{
    public const TOPIC_NAME = 'price.update';

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
     * @param PriceQueueInterface $priceQueue
     * @return void
     */
    public function execute(PriceQueueInterface $priceQueue)
    {
        $this->publisher->publish(self::TOPIC_NAME, $priceQueue);
    }
}
