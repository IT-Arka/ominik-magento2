<?php

namespace Omnik\Core\Console\Variants;

use Exception;
use Omnik\Core\Model\Repositories\VariantRepository;
use Omnik\Core\Model\ResourceModel\Variant;
use Omnik\Core\Model\VariantFactory;
use Omnik\Core\Model\Integration\Variant\GetList;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;

class ImportVariants extends Command
{
    public const OFFSET = 'offset';

    /**
     * @var State
     */
    protected State $state;

    /**
     * @var GetList
     */
    private GetList $getList;

    /**
     * @var VariantFactory
     */
    private VariantFactory $variantFactory;

    /**
     * @var Variant
     */
    private Variant $variantResource;

    /**
     * @var VariantRepository
     */
    private VariantRepository $variantRepository;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @param State $state
     * @param GetList $getList
     * @param VariantFactory $variantFactory
     * @param Variant $variantResource
     * @param VariantRepository $variantRepository
     * @param Json $json
     */
    public function __construct(
        State $state,
        GetList $getList,
        VariantFactory $variantFactory,
        Variant $variantResource,
        VariantRepository $variantRepository,
        Json $json
    ) {
        $this->state = $state;
        $this->getList = $getList;
        $this->variantFactory = $variantFactory;
        $this->variantResource = $variantResource;
        $this->variantRepository = $variantRepository;
        $this->json = $json;
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('omnik:variants:import')
            ->setDescription('Import product variants from omnik')
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
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode('global');
        $offset = $input->getOption(self::OFFSET) ?: 0;
        $variants = $this->getList->execute($offset);

        if (!isset($variants['values'])) {
            $output->writeln('Error with Omnik API Variant');
            return Command::FAILURE;
        }

        foreach ($variants['values'] as $variant) {
            $model = $this->variantFactory->create();

            if (isset($variant['variantData']['code'])) {
                $this->variantResource->load($model, $variant['variantData']['code'], 'variant_code');
            }

            $model->addData($this->getVariantData($variant));

            $this->variantRepository->save($model);
        }

        $output->writeln('Variants has been imported');

        return Command::SUCCESS;
    }

    /**
     * @param array $data
     * @return array
     */
    private function getVariantData(array $data): array
    {
        $variantData = [];
        $variantData['tenant'] = $data['tenant'];
        $variantData['operator'] = $data['operator'];
        $variantData['variant'] = $data['variantData']['variant'];
        $variantData['variant_id'] = $data['variantData']['id'];
        $variantData['variant_code'] = $data['variantData']['code'];
        $variantData['variant_name'] = $data['variantData']['name'];
        $variantData['variant_option'] = $this->json->serialize(['options' => $data['values']]);
        $variantData['create_date'] = $data['createDate'];
        $variantData['last_update'] = $data['lastUpdate'];

        return $variantData;
    }
}
