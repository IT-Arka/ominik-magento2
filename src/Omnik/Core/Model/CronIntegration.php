<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Exception;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Omnik\Core\Model\Config\Source\Frequency;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\App\Request\Http;

class CronIntegration extends Value
{
    private const SECTION_ID_DEFAULT = 'omnik_notify_send_webhook_';
    private array $configPathSystemData = [
        self::SECTION_ID_DEFAULT . 'config_cron_order' => 'omnik_notify/jobs/create_omnik_status_order',
        self::SECTION_ID_DEFAULT . 'config_cron_integration_product' => 'omnik_integration_product/jobs/create_integration_product',
        self::SECTION_ID_DEFAULT . 'config_cron_product_attribute' => 'omnik_notify/jobs/create_integration_product_attributes',
        self::SECTION_ID_DEFAULT . 'config_cron_update_stock_product' => 'omnik_notify/jobs/update_stock_product',
        self::SECTION_ID_DEFAULT . 'config_cron_update_price_product' => 'omnik_notify/jobs/update_price_product',
        self::SECTION_ID_DEFAULT . 'config_cron_seller' => 'omnik_notify/jobs/create_integration_seller',
        self::SECTION_ID_DEFAULT . 'config_cron_product_update' => 'omnik_notify/jobs/product_update_attributes',
    ];

    /**
     * @var ValueFactory $configValueFactory
     */
    protected ValueFactory $configValueFactory;

    /**
     * @var string
     */
    protected string $_runModelPath = '';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param Http $httprequest
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        ValueFactory $configValueFactory,
        Http $httprequest,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->configValueFactory = $configValueFactory;
        $this->httprequest = $httprequest;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return CronIntegration
     * @throws Exception
     */
    public function afterSave()
    {
        $files = $this->httprequest->getPost();
        $configState = $files['config_state'];

        foreach ($configState as $key => $value) {
            if (!$value) {
                continue;
            }

            $configPath = $this->configPathSystemData[$key];
            $groupId = str_replace(self::SECTION_ID_DEFAULT, "", $key);

            $frequency = $this->getGroups()[$groupId]['fields']['frequency']['value'];
            $time = $this->getGroups()[$groupId]['fields']['time']['value'];

            $cronExprArray = [
                intval($time[1]), //Minute
                intval($time[0]), //Hour
                $frequency == Frequency::CRON_MONTHLY ? '1' : '*', //Day of the Month
                '*', //Month of the Year
                $frequency == Frequency::CRON_WEEKLY ? '1' : '*', //Day of the Week
            ];

            if ($frequency == Frequency::CRON_HOUR) {
                $cronExprArray = [0, '*/' . intval($time[0]), '*', '*', '*'];
            }

            if ($frequency == Frequency::CRON_MINUTE) {
                $cronExprArray = ['*/' . intval($time[0]), '*', '*', '*', '*'];
            }

            $cronExprString = join(' ', $cronExprArray);

            $path['cron_expr'] = 'crontab/' . $configPath . '/schedule/cron_expr';
            $path['run_model'] = 'crontab/' . $configPath . '/run/model';

            try {
                $this->configValueFactory->create()->load(
                    $path['cron_expr'],
                    'path'
                )->setValue(
                    $cronExprString
                )->setPath(
                    $path['cron_expr']
                )->save();
                $this->configValueFactory->create()->load(
                    $path['run_model'],
                    'path'
                )->setValue(
                    $this->_runModelPath
                )->setPath(
                    $path['run_model']
                )->save();
            } catch (Exception $e) {
                throw new Exception(__('We can\'t save the cron expression.'));
            }
        }

        return parent::afterSave();
    }
}
