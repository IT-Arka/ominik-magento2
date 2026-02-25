<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Cron\SaveHandler\Product;

use Omnik\Core\Api\Data\NotifyOmnikDataInterface;
use Omnik\Core\Api\NotifyHandlerInterface;
use Omnik\Core\Model\Service\Product as ProductService;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;

class InsertEmbalagem implements NotifyHandlerInterface
{
    public const ATTRIBUTE_CODE_EMBALAGEM = 'variant_embalagem';

    /**
     * @var NotifyOmnikDataInterface
     */
    private NotifyOmnikDataInterface $notifyOmnikDataInterface;

    /**
     * @var ProductService
     */
    private ProductService $productService;

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @param NotifyOmnikDataInterface $notifyOmnikDataInterface
     * @param ProductService $productService
     * @param Config $eavConfig
     */
    public function __construct(
        NotifyOmnikDataInterface $notifyOmnikDataInterface,
        ProductService $productService,
        Config $eavConfig
    ) {
        $this->notifyOmnikDataInterface = $notifyOmnikDataInterface;
        $this->productService = $productService;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param array $registers
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function execute(array $registers): void
    {
        if (!empty($registers)) {
            $qtyRegisters = 0;

            foreach ($registers as $data) {
                if ($data['event'] == self::INSERT && $data['resource_type'] == self::RESOURCE_EMBALAGEM) {
                    $optionLabel = trim($data['resource_id']);

                    $attributeData = $this->eavConfig->getAttribute(
                        ProductAttributeInterface::ENTITY_TYPE_CODE,
                        self::ATTRIBUTE_CODE_EMBALAGEM,
                    )->getSource()->getAllOptions();

                    $optionLabelExistData = array_column($attributeData, 'label');

                    if (in_array($optionLabel, $optionLabelExistData)) {
                        $this->notifyOmnikDataInterface->changeStatusNotify((int) $data['entity_id']);
                        continue;
                    }

                    $this->productService->addOptionToAttribute($optionLabel, self::ATTRIBUTE_CODE_EMBALAGEM);
                    $this->notifyOmnikDataInterface->changeStatusNotify((int) $data['entity_id']);

                    $qtyRegisters++;
                }

                if ($qtyRegisters == self::QTY_LIMIT_REGISTERS) {
                    break;
                }
            }
        }
    }
}
