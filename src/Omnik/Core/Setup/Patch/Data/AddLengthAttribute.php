<?php

declare(strict_types=1);

namespace Omnik\Core\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class AddLengthAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @param AttributeSet $attributeSet
     * @param EavSetupFactory $eavSetupFactory
     * @param ResourceProduct $resourceProduct
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly AttributeSet $attributeSet,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly ResourceProduct $resourceProduct,
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {

    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(["setup" => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            'lenght',
            [
                'label' => 'Product Length',
                'user_defined' => true,
                'required' => false,
                'group' => 'General'
            ]
        );

        $entityType = $this->resourceProduct->getEntityType();
        $attributeSetCollection = $this->attributeSet->setEntityTypeFilter($entityType);

        foreach ($attributeSetCollection as $attributeSet) {
            $eavSetup->addAttributeToSet(
                "catalog_product",
                $attributeSet->getAttributeSetName(),
                "General",
                "length"
            );
        }
    }

    public function revert()
    {
        $eavSetup = $this->eavSetupFactory->create(["setup" => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(
            Product::ENTITY,
            'length'
        );
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
