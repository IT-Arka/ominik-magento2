<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Seller;

use Exception;
use Omnik\Core\Model\Integration\Seller\GetSellerByDocument;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Model\Service\Seller;
use Magento\Framework\Exception\LocalizedException;
use Omnik\Core\Helper\ApiResponse;
use Omnik\Core\Logger\Logger;

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
     * @var ApiResponse
     */
    private ApiResponse $apiResponse;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param GetSellerByDocument $getSellerByDocument
     * @param Seller $sellerService
     * @param ApiResponse $apiResponse
     * @param Logger $logger
     */
    public function __construct(
        NotifyOmnikDataInterface $notifyOmnikDataInterface,
        GetSellerByDocument $getSellerByDocument,
        Seller $sellerService,
        ApiResponse $apiResponse,
        Logger $logger
    ) {
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
        $this->getSellerByDocument = $getSellerByDocument;
        $this->sellerService = $sellerService;
        $this->apiResponse = $apiResponse;
        $this->logger = $logger;
    }

    /**
     * @param array $registers
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(array $registers): void
    {
        if (empty($registers)) {
            return;
        }

        $qtyRegisters = 0;

        foreach ($registers as $data) {
            if ($data['event'] != self::UPDATE || $data['resource_type'] != self::RESOURCE_SELLER) {
                continue;
            }

            $storeId = (int) $data['store_id'];
            $idNotify = (int) $data['entity_id'];

            // Isolate each register so one failure does not abort the remaining ones.
            try {
                $seller = $this->getSellerByDocument->execute($data['seller'], $storeId);

                // A transient API failure (fails:true) must not retire the seller as
                // NOT_FOUND. Count the attempt and leave it pending for the next run.
                if ($this->apiResponse->isTransportFailure(is_array($seller) ? $seller : null)) {
                    $this->logger->error(
                        'Seller update API transient failure (fails:true) - notify_id: ' . $idNotify .
                        ' - seller: ' . $data['seller']
                    );
                    $this->notifyOmnikDataInterface->changeAttempts($idNotify);
                    continue;
                }

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
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Seller update error - notify_id: ' . $idNotify . ' - ' . $e->getMessage()
                );
                $this->notifyOmnikDataInterface->changeStatusNotify(
                    $idNotify,
                    NotifyOmnikDataInterface::STATUS_ERROR
                );
            }

            if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                break;
            }
        }
    }
}
