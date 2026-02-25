<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Frequency implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected static $options;

    public const CRON_HOUR = 'H';
    public const CRON_DAILY = 'D';
    public const CRON_WEEKLY = 'W';
    public const CRON_MONTHLY = 'M';
    public const CRON_MINUTE = 'MIN';

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if (!self::$options) {
            self::$options = [
                ['label' => __('Hour'), 'value' => self::CRON_HOUR],
                ['label' => __('Daily'), 'value' => self::CRON_DAILY],
                ['label' => __('Weekly'), 'value' => self::CRON_WEEKLY],
                ['label' => __('Monthly'), 'value' => self::CRON_MONTHLY],
                ['label' => __('Minute'), 'value' => self::CRON_MINUTE]
            ];
        }
        return self::$options;
    }
}
