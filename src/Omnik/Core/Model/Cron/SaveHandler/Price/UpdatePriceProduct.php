<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Price;

use Exception;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Model\Service\Product;
use Omnik\Core\Logger\Logger;

class UpdatePriceProduct implements NotifyHandlerInterface
{
    /**
     * @var NotifyOmnikDataInterface
     */
    private NotifyOmnikDataInterface $notifyOmnikDataInterface;

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
     * @param Product $productService
     * @param Logger $logger
     */
    public function __construct(
        NotifyOmnikDataInterface $notifyOmnikDataInterface,
        Product $productService,
        Logger $logger
    ) {
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
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
                if ($data['event'] == self::UPDATE && $data['resource_type'] == self::RESOURCE_PRICE) {
                    $idNotify = (int) $data['entity_id'];
                    $fromPrice = (float) $data['from_price'];
                    $price = (float) $data['price'];

                    if ($fromPrice > 0 && $price > 0) {
                        $sku = $this->productService->getSkuProduct($data['resource_id']);

                        if (!is_null($sku)) {
                            try {
                                $this->productService->updatePriceProductBySku(
                                    $sku,
                                    $fromPrice,
                                    $price
                                );

                                $this->notifyOmnikDataInterface->changeStatusNotify(
                                    $idNotify,
                                    NotifyOmnikDataInterface::STATUS_INTEGRATED
                                );
                            } catch (\Throwable $e) {
                                // Catch every failure (not only NoSuchEntityException) so an
                                // unexpected error on one register cannot abort the whole batch.
                                $this->logger->error(
                                    'Price update error - notify_id: ' . $idNotify .
                                    ' - sku: ' . $sku . ' - ' . $e->getMessage()
                                );
                                $this->notifyOmnikDataInterface->changeStatusNotify(
                                    $idNotify,
                                    NotifyOmnikDataInterface::STATUS_ERROR
                                );
                                continue;
                            }
                        } else {
                            $this->notifyOmnikDataInterface->changeStatusNotify(
                                $idNotify,
                                NotifyOmnikDataInterface::STATUS_NOT_FOUND_MAGENTO
                            );
                        }
                    } else {
                        $this->notifyOmnikDataInterface->changeStatusNotify(
                            $idNotify,
                            NotifyOmnikDataInterface::STATUS_NOT_FOUND
                        );
                    }

                    $qtyRegisters++;
                }

                if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                    break;
                }
            }
        }
    }
}
