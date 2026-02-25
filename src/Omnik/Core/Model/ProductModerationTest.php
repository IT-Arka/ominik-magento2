<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Api\ProductModerationTestInterface;
use Omnik\Core\Cron\ProductCron;

/**
 * webapi to test integration product
 */
class ProductModerationTest implements ProductModerationTestInterface
{
    /**
     * @var ProductCron
     */
    private ProductCron $productCron;

    /**
     * @param ProductCron $productCron
     */
    public function __construct(
        ProductCron $productCron
    ) {
        $this->productCron = $productCron;
    }

    /**
     * @return mixed|void
     */
    public function execute()
    {
        $this->productCron->execute();
    }
}
