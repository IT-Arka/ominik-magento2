<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Seller;

use Exception;
use Omnik\Core\Model\Integration\Seller\GetSellerByDocument;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Model\Service\Seller;
use Magento\Framework\Exception\LocalizedException;

class UpdateSeller implements NotifyHandlerInterface
{
    /**
     * @var NotifyOmnikDataInterface
     */
    private NotifyOmnikDataInterface $notifyOmnikDataInterface;

    /**
     * @var GetSellerByDocument
     */
    private GetSellerByDocument $getSellerByDocument;

    /**
     * @var Seller
     */
    private Seller $sellerService;

    /**
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param GetSellerByDocument $getSellerByDocument
     * @param Seller $sellerService
     */
    public function __construct(
        NotifyOmnikDataInterface $notifyOmnikDataInterface,
        GetSellerByDocument $getSellerByDocument,
        Seller $sellerService
    ) {
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
        $this->getSellerByDocument = $getSellerByDocument;
        $this->sellerService = $sellerService;
    }

    /**
     * @param array $registers
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(array $registers): void
    {
        if (!empty($registers)) {
            $qtyRegisters = 0;

            foreach ($registers as $data) {
                if ($data['event'] == self::UPDATE && $data['resource_type'] == self::RESOURCE_SELLER) {
                    $storeId = (int) $data['store_id'];
                    $idNotify = (int) $data['entity_id'];

                    $seller = $this->getSellerByDocument->execute($data['seller'], $storeId);

                    if (!isset($seller['sellerData'])) {
                        $this->notifyOmnikDataInterface->changeStatusNotify(
                            $idNotify,
                            NotifyOmnikDataInterface::STATUS_NOT_FOUND
                        );
                        continue;
                    }

                    $this->sellerService->updateSeller($seller, $storeId);
                    $this->notifyOmnikDataInterface->changeStatusNotify($idNotify);

                    $qtyRegisters++;
                }

                if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                    break;
                }
            }
        }
    }
}
