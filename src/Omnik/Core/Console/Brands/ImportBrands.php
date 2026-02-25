<?php

namespace Omnik\Core\Console\Brands;

use Exception;
use Omnik\Core\Model\Integration\Brand\GetList;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;

class ImportBrands extends Command
{
    public const OFFSET = 'offset';
    public const ATTRIBUTE_CODE_BRAND = 'brand';

    /**
     * @var State
     */
    protected State $state;

    /**
     * @var GetList
     */
    private GetList $getList;

    /**
     * @var AttributeOptionManagementInterface
     */
    private AttributeOptionManagementInterface $optionManagement;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    private AttributeOptionInterfaceFactory $optionFactory;
    private Config $eavConfig;

    /**
     * @param State $state
     * @param GetList $getList
     * @param AttributeOptionManagementInterface $optionManagement
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param Config $eavConfig
     */
    public function __construct(
        State $state,
        GetList $getList,
        AttributeOptionManagementInterface $optionManagement,
        AttributeOptionInterfaceFactory $optionFactory,
        Config $eavConfig
    ) {
        $this->state = $state;
        $this->getList = $getList;
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
        $this->setName('omnik:brands:import')
            ->setDescription('Import brands from omnik')
            ->setDefinition([
                new InputOption(
                    self::OFFSET,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Indicates from which is an increase'
                ),
                new InputArgument(
                    self::OFFSET,
                    InputArgument::OPTIONAL,
                    'Offset',
                    '0'
                )
            ]);

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode('global');
        $offset = $input->getOption(self::OFFSET) ?: 0;
        $brands = $this->getList->execute($offset);

        if (!isset($brands['values'])) {
            $output->writeln('Error with Omnik API Brand');
            return Command::FAILURE;
        }

        $attributeData = $this->eavConfig->getAttribute(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            self::ATTRIBUTE_CODE_BRAND,
        )->getSource()->getAllOptions();

        $optionLabelExistData = array_column($attributeData, 'label');

        $optionBrandData = $this->prepareData($brands);

        $this->saveBrand($optionBrandData, $optionLabelExistData);

        $output->writeln('Brands has been added: 1');
        $j = 1;
        for ($i = 10; $i <= $brands['total'];) {
            $brands = $this->getList->execute($i);

            $optionBrandData = $this->prepareData($brands);

            $this->saveBrand($optionBrandData, $optionLabelExistData);

            $j++;
            $output->writeln('Brands has been added: ' . $j);
            $i = $i + 10;
        }
        return Command::SUCCESS;
    }

    /**
     * @param $optionBrandData
     * @param $optionLabelExistData
     * @return void
     * @throws InputException
     * @throws StateException
     */
    private function saveBrand($optionBrandData, $optionLabelExistData)
    {
        foreach ($optionBrandData as $optionBrand) {
            if (in_array(trim($optionBrand['label']), $optionLabelExistData)) {
                continue;
            }

            $this->addOptionToBrand($optionBrand);
        }
    }

    /**
     * @param $optionBrand
     * @return void
     * @throws InputException
     * @throws StateException
     */
    private function addOptionToBrand($optionBrand)
    {
        $option = $this->optionFactory->create();
        $option->setLabel($optionBrand['label']);

        $this->optionManagement->add(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            self::ATTRIBUTE_CODE_BRAND,
            $option
        );
    }

    /**
     * @param $brands
     * @return array
     */
    private function prepareData($brands): array
    {
        $optionBrandData = [];
        foreach ($brands['values'] as $key => $brand) {
            $optionBrandData[$key]['label'] = $brand['brandData']['name'];
        }

        return $optionBrandData;
    }
}
