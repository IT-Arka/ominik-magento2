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

class AddVariantEmbalagemAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var AttributeSet
     */
    protected AttributeSet $attributeSet;

    /**
     * @var EavSetupFactory
     */
    protected EavSetupFactory $eavSetupFactory;

    /**
     * @var ResourceProduct
     */
    protected ResourceProduct $resourceProduct;

    /**
     * @var ModuleDataSetupInterface
     */
    protected ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param AttributeSet $attributeSet
     * @param EavSetupFactory $eavSetupFactory
     * @param ResourceProduct $resourceProduct
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        AttributeSet $attributeSet,
        EavSetupFactory $eavSetupFactory,
        ResourceProduct $resourceProduct,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->attributeSet    = $attributeSet;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->resourceProduct = $resourceProduct;
        $this->moduleDataSetup = $moduleDataSetup;
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
            'variant_embalagem',
            [
                'type' => 'int',
                'label' => 'Embalagem',
                'input' => 'select',
                'user_defined' => true,
                'required' => false,
                'default' => '',
                'searchable' => true,
                'filterable' => true,
                'visible_on_front' => true,
                'used_for_promo_rules' => true,
                'filterable_in_search' => true,
                'visible_in_advanced_search' => true,
                'used_in_product_listing' => true,
                'is_filterable_in_grid' => true,
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
                "variant_embalagem"
            );
        }
    }

    public function revert()
    {
        $eavSetup = $this->eavSetupFactory->create(["setup" => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(
            Product::ENTITY,
            'variant_embalagem'
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
