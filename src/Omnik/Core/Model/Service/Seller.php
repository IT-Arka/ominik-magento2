<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Service;

use Omnik\Core\Model\Repositories\SellerRepository;
use Omnik\Core\Model\SellerFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Serialize\Serializer\Json;

class Seller
{
    public const ATTRIBUTE_CODE_VARIANT_SELLER = 'variant_seller';

    /**
     * @var SellerFactory
     */
    private SellerFactory $sellerFactory;

    /**
     * @var SellerRepository
     */
    private SellerRepository $sellerRepository;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var AttributeOptionManagementInterface
     */
    private AttributeOptionManagementInterface $optionManagement;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    private AttributeOptionInterfaceFactory $optionFactory;

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    /**
     * @param SellerFactory $sellerFactory
     * @param SellerRepository $sellerRepository
     * @param Json $json
     * @param AttributeOptionManagementInterface $optionManagement
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param Config $eavConfig
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     */
    public function __construct(
        SellerFactory $sellerFactory,
        SellerRepository $sellerRepository,
        Json $json,
        AttributeOptionManagementInterface $optionManagement,
        AttributeOptionInterfaceFactory $optionFactory,
        Config $eavConfig,
        ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->sellerFactory = $sellerFactory;
        $this->sellerRepository = $sellerRepository;
        $this->json = $json;
        $this->optionManagement = $optionManagement;
        $this->optionFactory = $optionFactory;
        $this->eavConfig = $eavConfig;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * @param array $sellerData
     * @param int $storeId
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function insertSeller(array $sellerData, int $storeId)
    {
        $seller = $this->sellerFactory->create();
        $seller->setData($this->getSellerData($sellerData, $storeId));
        $this->sellerRepository->save($seller);
        $this->addSellerToAttribute($sellerData['sellerData']['fantasyName']);
    }

    /**
     * @param array $sellerData
     * @param int $storeId
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function updateSeller(array $sellerData, int $storeId)
    {
        $seller = $this->sellerRepository->getByOmnikId($sellerData['tenant']);

        if ($seller) {
            if ($seller->getFantasyName() != $sellerData['sellerData']['fantasyName']) {
                $this->updateSellerLabelAttribute($seller->getFantasyName(), $sellerData['sellerData']['fantasyName']);
            }

            $seller->addData($this->getSellerData($sellerData, $storeId));

            $this->sellerRepository->save($seller);
        }
    }

    /**
     * @param array $data
     * @param int $storeId
     * @return array
     */
    public function getSellerData(array $data, int $storeId): array
    {
        $sellerData['omnik_id'] = $data['tenant'];
        $sellerData['active'] = $data['contractData']['status'] == 'ATIVO' ? true : false;
        $sellerData['hub_session_id'] = $data['sellerData']['hubSessionId'];
        $sellerData['company_name'] = $data['sellerData']['companyName'];
        $sellerData['fantasy_name'] = $data['sellerData']['fantasyName'];
        $sellerData['cnae'] = $data['sellerData']['cnae'];
        $sellerData['state_registration'] = $data['sellerData']['stateRegistration'];
        $sellerData['municipal_registration'] = $data['sellerData']['municipalRegistration'];
        $sellerData['tax_regime'] = $data['sellerData']['taxRegime'];
        $sellerData['date_accession_national_simple'] = $data['sellerData']['dateAccessionNationalSimple'];
        $sellerData['holding'] = $data['sellerData']['holding'];
        $sellerData['matrix'] = $data['sellerData']['matrix'];
        $sellerData['branch'] = $data['sellerData']['branch'];
        $sellerData['store_id'] = $storeId;

        return $sellerData;
    }

    /**
     * @param $fantasyName
     * @return void
     * @throws LocalizedException
     * @throws InputException
     * @throws StateException
     */
    public function addSellerToAttribute($fantasyName)
    {
        $attributeData = $this->eavConfig->getAttribute(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            self::ATTRIBUTE_CODE_VARIANT_SELLER,
        )->getSource()->getAllOptions();

        $optionLabelExistData = array_column($attributeData, 'label');

        if (!in_array(trim($fantasyName), $optionLabelExistData)) {
            $option = $this->optionFactory->create();
            $option->setLabel(trim($fantasyName));

            $this->optionManagement->add(
                ProductAttributeInterface::ENTITY_TYPE_CODE,
                self::ATTRIBUTE_CODE_VARIANT_SELLER,
                $option
            );
        }
    }

    /**
     * @param string $sellerLabelOld
     * @param string $sellerLabelNew
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function updateSellerLabelAttribute(string $sellerLabelOld, string $sellerLabelNew)
    {
        $attributeData = $this->productAttributeRepository->get(self::ATTRIBUTE_CODE_VARIANT_SELLER);
        $optionId = 0;

        if ($attributeData->usesSource()) {
            $optionId = $attributeData->getSource()->getOptionId($sellerLabelOld);
        }

        $options = $attributeData->getOptions();
        foreach ($options as $option) {
            if ($option->getValue() == $optionId) {
                $option->setLabel($sellerLabelNew);
                $attributeData->setOptions([$option]);
                $this->productAttributeRepository->save($attributeData);
                break;
            }
        }
    }
}
