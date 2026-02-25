<?php

namespace Omnik\Core\Observer;

use Exception;
use Omnik\Core\Model\Integration\Product\Send;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Config\Params;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;

class ProductSave implements ObserverInterface
{
    public const TYPE_PRODUCT_CONFIGURABLE = 'configurable';

    /**
     * @var Params
     */
    private Params $params;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Send
     */
    private Send $sendProduct;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param Params $params
     * @param Json $json
     * @param Send $sendProduct
     * @param Logger $logger
     */
    public function __construct(
        Params $params,
        Json $json,
        Send $sendProduct,
        Logger $logger
    ) {
        $this->params = $params;
        $this->json = $json;
        $this->sendProduct = $sendProduct;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();

        if ($observer->getProduct()->getData('type_id') == self::TYPE_PRODUCT_CONFIGURABLE) {
            $parameters = $this->params->createParameters($product);
            $this->logger->info('BODY SEND: ' .  $parameters);

            $response = $this->sendProduct->execute($parameters);
            $this->logger->info('RESPONSE OMNIK: ' .  $this->json->serialize($response));
        }
    }
}
