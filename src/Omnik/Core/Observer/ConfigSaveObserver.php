<?php
declare(strict_types=1);

namespace Omnik\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Omnik\Core\Helper\StatusMapping as StatusMappingHelper;
use Psr\Log\LoggerInterface;

/**
 * Class ConfigSaveObserver
 * Observer to handle config save events
 */
class ConfigSaveObserver implements ObserverInterface
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var StatusMappingHelper
     */
    private $statusMappingHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param CacheManager $cacheManager
     * @param StatusMappingHelper $statusMappingHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        CacheManager $cacheManager,
        StatusMappingHelper $statusMappingHelper,
        LoggerInterface $logger
    ) {
        $this->cacheManager = $cacheManager;
        $this->statusMappingHelper = $statusMappingHelper;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            // Clear status mapping cache
            $this->statusMappingHelper->clearCache();
            
            // Clean specific cache types
            $cacheTypes = [
                'config',
                'layout',
                'block_html',
                'full_page'
            ];
            
            $this->cacheManager->clean($cacheTypes);
            
            $this->logger->info('Omnik Core: Cache cleared after configuration save.');
            
        } catch (\Exception $e) {
            $this->logger->error(
                'Omnik Core: Error clearing cache after configuration save: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
