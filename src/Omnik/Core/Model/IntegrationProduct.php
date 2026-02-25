<?php

declare(strict_types=1);

namespace Omnik\Core\Model;

use Omnik\Core\Model\Management\CreateProduct;
use Omnik\Core\Model\Management\ProcessMatch;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

class IntegrationProduct
{
    /**
     * @var CreateProduct
     */
    private CreateProduct $createProduct;

    /**
     * @var ProcessMatch
     */
    private ProcessMatch $processMatch;

    /**
     * @param CreateProduct $createProduct
     * @param ProcessMatch $processMatch
     */
    public function __construct(
        CreateProduct $createProduct,
        ProcessMatch  $processMatch
    ) {
        $this->createProduct = $createProduct;
        $this->processMatch = $processMatch;
    }

    /**
     * @param array $productModeratedData
     * @param int $storeId
     * @param bool $isConfigurableExists
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function integrate(array $productModeratedData, int $storeId, bool $isConfigurableExists = false)
    {
        $responseCreateSimple = $this->createProduct->createProductSimple($productModeratedData, $storeId);

        if ($responseCreateSimple['simple_product_match']) {
            $this->processMatch->actionProductMatch($responseCreateSimple, $storeId);
        }

        if (!$isConfigurableExists) {
            $this->createProduct->createProductConfigurable($productModeratedData, $storeId);
        }

        if ($responseCreateSimple['simple_product_no_match']) {
            $responseCreateConfigurable = $this->createProduct->getProductConfigurableParams($productModeratedData);
            $attributeVariation = $productModeratedData['skus'][0]['attributes'];

            $this->processMatch->actionProductNoMatch(
                $responseCreateConfigurable,
                $attributeVariation,
                $responseCreateSimple,
                $storeId
            );
        }
    }
}
