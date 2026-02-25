<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Seller;

use Exception;
use Omnik\Core\Model\Integration\Seller\GetSellerByDocument;
use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Model\Service\Seller;
use Magento\Framework\Exception\LocalizedException;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Helper\Config;
use Omnik\Core\Model\Data\NotifyOmnikData;

class InsertSeller implements NotifyHandlerInterface
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
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Config $helperConfig
     */
    private Config $helperConfig;

    /**
     * @var NotifyOmnikData $notifyOmnikData
     */
    private NotifyOmnikData $notifyOmnikData;

    /**
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param GetSellerByDocument $getSellerByDocument
     * @param Seller $sellerService
     * @param Logger $logger
     * @param Config $helperConfig
     * @param NotifyOmnikData $notifyOmnikData
     */
    public function __construct(
        NotifyOmnikDataInterface $notifyOmnikDataInterface,
        GetSellerByDocument $getSellerByDocument,
        Seller $sellerService,
        Logger $logger,
        Config $helperConfig,
        NotifyOmnikData $notifyOmnikData
    ) {
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
        $this->getSellerByDocument = $getSellerByDocument;
        $this->sellerService = $sellerService;
        $this->logger = $logger;
        $this->helperConfig = $helperConfig;
        $this->notifyOmnikData = $notifyOmnikData;
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
            try {
                $qtyRegisters = 0;

                foreach ($registers as $data) {
                    if ($data['event'] == self::INSERT && $data['resource_type'] == self::RESOURCE_SELLER) {
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

                        $configAttempts = $this->helperConfig->getAttempts($storeId);
                        $attempts = preg_replace('/[^0-9]/', '', $configAttempts);
                        $attempts = (empty($attempts) || $attempts <= 0) ? 1 : (int)$attempts;

                        $qtyAttempts = $data['attempts'];

                        if ((int)$qtyAttempts <= (int)$attempts) {
                            $this->notifyOmnikDataInterface->changeAttempts($idNotify);
                        }

                        $this->sellerService->insertSeller($seller, $storeId);
                        $this->notifyOmnikDataInterface->changeStatusNotify($idNotify);

                        $qtyRegisters++;
                    }

                    if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                        break;
                    }
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $this->notifyOmnikDataInterface->changeStatusNotify(
                    $idNotify,
                    NotifyOmnikDataInterface::STATUS_ERROR
                );
            }
        }
    }
}
