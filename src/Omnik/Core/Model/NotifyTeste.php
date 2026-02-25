<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\NotifyTesteInterface;
use Omnik\Core\Cron\Orders\CronChangeOrderStatus;
use Omnik\Core\Cron\Price\CronUpdatePriceProduct;
use Omnik\Core\Cron\Product\CronProductAttribute;
use Omnik\Core\Cron\Product\CronProductUpdate;
use Omnik\Core\Cron\Seller\CronSeller;
use Omnik\Core\Cron\Stock\CronUpdateStockProduct;
use Magento\Framework\App\RequestInterface;

/**
 * essa webapi serve para testar as crons de notificação
 */
class NotifyTeste implements NotifyTesteInterface
{
    /**
     * @var CronChangeOrderStatus
     */
    private CronChangeOrderStatus $cronChangeOrderStatus;

    /**
     * @var CronProductAttribute
     */
    private CronProductAttribute $cronProductAttribute;

    /**
     * @var CronUpdateStockProduct
     */
    private CronUpdateStockProduct $cronUpdateStockProduct;

    /**
     * @var CronUpdatePriceProduct
     */
    private CronUpdatePriceProduct $cronUpdatePriceProduct;

    /**
     * @var CronSeller
     */
    private CronSeller $cronSeller;

    /**
     * @var CronProductUpdate
     */
    private CronProductUpdate $cronProductUpdate;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;


    /**
     * @param CronChangeOrderStatus $cronChangeOrderStatus
     * @param CronProductAttribute $cronProductAttribute
     * @param CronUpdateStockProduct $cronUpdateStockProduct
     * @param CronUpdatePriceProduct $cronUpdatePriceProduct
     * @param CronSeller $cronSeller
     * @param CronProductUpdate $cronProductUpdate
     * @param RequestInterface $request
     */
    public function __construct(
        CronChangeOrderStatus  $cronChangeOrderStatus,
        CronProductAttribute   $cronProductAttribute,
        CronUpdateStockProduct $cronUpdateStockProduct,
        CronUpdatePriceProduct $cronUpdatePriceProduct,
        CronSeller             $cronSeller,
        CronProductUpdate      $cronProductUpdate,
        RequestInterface       $request
    ) {
        $this->cronChangeOrderStatus = $cronChangeOrderStatus;
        $this->cronProductAttribute = $cronProductAttribute;
        $this->cronUpdateStockProduct = $cronUpdateStockProduct;
        $this->cronUpdatePriceProduct = $cronUpdatePriceProduct;
        $this->cronSeller = $cronSeller;
        $this->cronProductUpdate = $cronProductUpdate;
        $this->request = $request;
    }

    /**
     * @return mixed|void
     */
    public function execute()
    {
        $type = $this->request->getParam('type') ?? '';
        $this->runCron($type);
    }

    /**
     * @param mixed $type
     * @return void
     */
    public function runCron($type)
    {
        switch ($type) {
            case 'orderStatus':
                $this->cronChangeOrderStatus->execute();
                break;
            case 'productAttribute':
                $this->cronProductAttribute->execute();
                break;
            case 'updateStock':
                $this->cronUpdateStockProduct->execute();
                break;
            case 'updatePrice':
                $this->cronUpdatePriceProduct->execute();
                break;
            case 'seller':
                $this->cronSeller->execute();
                break;
            case 'productUpdate':
                $this->cronProductUpdate->execute();
                break;
            default:
                $this->cronChangeOrderStatus->execute();
                $this->cronProductAttribute->execute();
                $this->cronUpdateStockProduct->execute();
                $this->cronUpdatePriceProduct->execute();
                $this->cronSeller->execute();
                $this->cronProductUpdate->execute();
        }
    }
}
