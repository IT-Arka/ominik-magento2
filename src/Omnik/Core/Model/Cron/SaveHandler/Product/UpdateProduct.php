<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Product;

use Exception;
use Omnik\Core\Model\Integration\Product\GetProduct;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Model\Service\Product;

class UpdateProduct implements NotifyHandlerInterface
{
    /**
     * @var NotifyOmnikDataInterface
     */
    private NotifyOmnikDataInterface $notifyOmnikDataInterface;

    /**
     * @var GetProduct
     */
    private GetProduct $getProduct;

    /**
     * @var Product
     */
    private Product $productService;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param GetProduct $getProduct
     * @param Product $productService
     * @param Logger $logger
     */
    public function __construct(
        NotifyOmnikDataInterface $notifyOmnikDataInterface,
        GetProduct $getProduct,
        Product $productService,
        Logger $logger
    ) {
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
        $this->getProduct = $getProduct;
        $this->productService = $productService;
        $this->logger = $logger;
    }

    /**
     * @param array $registers
     * @return void
     * @throws Exception
     */
    public function execute(array $registers): void
    {
        if (!empty($registers)) {
            $qtyRegisters = 0;

            foreach ($registers as $data) {
                if ($data['event'] == self::UPDATE && $data['resource_type'] == self::RESOURCE_PRODUCT) {
                    $storeId = (int) $data['store_id'];
                    $productData = $this->getProduct->execute($data['resource_id'], $storeId);

                    if (isset($productData['productData'])) {
                        try {
                            $this->productService->updateProduct($productData, $storeId);
                        } catch (Exception $e) {
                            $this->notifyOmnikDataInterface->changeStatusNotify((int) $data['entity_id']);
                            $this->logger->error(
                                'UPDATE | PRODUCT (' . $data['resource_id'] . '): ' . $e->getMessage()
                            );

                            continue;
                        }
                    }

                    $this->notifyOmnikDataInterface->changeStatusNotify((int) $data['entity_id']);

                    $qtyRegisters++;
                }

                if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                    break;
                }
            }
        }
    }
}
