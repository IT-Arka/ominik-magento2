<?php
declare(strict_types=1);

namespace Omnik\Core\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Handler extends StreamHandler
{
    /**
     * @param \Magento\Framework\Filesystem\Driver\File $filesystem
     */
    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $filesystem
    ) {
        
        $logFilePath = BP . '/var/log/omnik-' . date('Y-m-d') . '.log';
        
        parent::__construct($logFilePath, Logger::DEBUG, true, 0644);
    }

}
