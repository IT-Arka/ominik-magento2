<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Stock;

use Exception;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Model\Service\Product;

class UpdateStockProduct implements NotifyHandlerInterface
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
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param Product $productService
     */
    public function __construct(
        NotifyOmnikDataInterface $notifyOmnikDataInterface,
        Product $productService
    ) {
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
        $this->productService = $productService;
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
                if ($data['event'] == self::UPDATE && $data['resource_type'] == self::RESOURCE_INVENTORY) {
                    $idNotify = (int) $data['entity_id'];

                    if (!is_null($data['stock'])) {
                        $sku = $this->productService->getSkuProduct($data['resource_id']);

                        if (!is_null($sku)) {
                            try {
                                $this->productService->updateQtyProductBySku(
                                    $sku,
                                    (int) $data['stock']
                                );

                                $this->notifyOmnikDataInterface->changeStatusNotify(
                                    $idNotify,
                                    NotifyOmnikDataInterface::STATUS_INTEGRATED
                                );
                            } catch (Exception $e) {
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
