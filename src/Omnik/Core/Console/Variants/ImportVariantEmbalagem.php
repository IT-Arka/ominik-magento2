<?php

namespace Omnik\Core\Console\Variants;

use Exception;
use Omnik\Core\Model\Integration\Variant\GetValueByVariant;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;

class ImportVariantEmbalagem extends Command
{
    public const ATTRIBUTE_CODE_VARIANT_EMBALAGEM = 'variant_embalagem';
    public const VARIANT_EMBALAGEM = 'EMBALAGEM';

    /**
     * @var State
     */
    protected State $state;

    /**
     * @var AttributeOptionManagementInterface
     */
    private AttributeOptionManagementInterface $optionManagement;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    private AttributeOptionInterfaceFactory $optionFactory;

    /**
     * @var GetValueByVariant
     */
    private GetValueByVariant $getValueByVariant;

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @param State $state
     * @param GetValueByVariant $getValueByVariant
     * @param AttributeOptionManagementInterface $optionManagement
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param Config $eavConfig
     */
    public function __construct(
        State $state,
        GetValueByVariant $getValueByVariant,
        AttributeOptionManagementInterface $optionManagement,
        AttributeOptionInterfaceFactory $optionFactory,
        Config $eavConfig
    ) {
        $this->state = $state;
        $this->getValueByVariant = $getValueByVariant;
        $this->optionManagement = $optionManagement;
        $this->optionFactory = $optionFactory;
        $this->eavConfig = $eavConfig;
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('omnik:variant-embalagem:import')
            ->setDescription('Import values from embalagem variant from omnik');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);
        $output->write(__('Starting Import Variant %variant ', ['variant' => self::VARIANT_EMBALAGEM]));

        $this->state->setAreaCode('global');

        $variantData = $this->getValueByVariant->execute(self::VARIANT_EMBALAGEM);

        if (!$variantData) {
            $output->writeln('Nothing to import');
            return Command::FAILURE;
        }

        if (is_array($variantData) && array_key_exists('fails', $variantData)) {
            $fails = $variantData['fails'];
            if ($fails == 1)
            {
                return Command::FAILURE;
            }
        }

        $attributeData = $this->eavConfig->getAttribute(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            self::ATTRIBUTE_CODE_VARIANT_EMBALAGEM,
        )->getSource()->getAllOptions();

        $optionLabelExistData = array_column($attributeData, 'label');

        foreach ($variantData as $key => $variant) {
            if (in_array($variant['name'], $optionLabelExistData)) {
                continue;
            }

            $option = $this->optionFactory->create();
            $option->setLabel($variant['name']);

            try {
                $this->optionManagement->add(
                    ProductAttributeInterface::ENTITY_TYPE_CODE,
                    self::ATTRIBUTE_CODE_VARIANT_EMBALAGEM,
                    $option
                );
            } catch (\Exception $e) {
                continue;
            }
        }

        $resultTime = microtime(true) - $startTime;
        $output->writeln(
            __(
                'has been rebuilt successfully in %time',
                [
                    'time' => gmdate('H:i:s', (int) $resultTime),
                    'variant' => self::VARIANT_EMBALAGEM
                ]
            )
        );

        return Command::SUCCESS;
    }
}
