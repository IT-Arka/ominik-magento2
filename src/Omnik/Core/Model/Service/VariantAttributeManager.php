<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Service;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Cria/garante atributos de produto a partir das variantes da Omnik
 * e importa suas opções (values). Idempotente: não duplica atributos nem opções.
 */
class VariantAttributeManager
{
    /**
     * Prefixo aplicado ao attribute_code gerado a partir do nome da variante Omnik.
     */
    public const ATTRIBUTE_PREFIX = 'variant_';

    /**
     * Variantes que já possuem atributo dedicado criado por setup patch.
     * Mapeia o nome Omnik (uppercase) para o attribute_code existente.
     *
     * @var array<string,string>
     */
    private const KNOWN_VARIANTS = [
        'COR'       => 'variant_color',
        'TAMANHO'   => 'variant_tamanho',
        'EMBALAGEM' => 'variant_embalagem',
        'SELLER'    => 'variant_seller',
    ];

    /**
     * @var EavSetup
     */
    private EavSetup $eavSetup;

    /**
     * @param AttributeSet $attributeSet
     * @param EavSetupFactory $eavSetupFactory
     * @param ResourceProduct $resourceProduct
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavConfig $eavConfig
     * @param AttributeOptionManagementInterface $optionManagement
     * @param AttributeOptionInterfaceFactory $optionFactory
     */
    public function __construct(
        private readonly AttributeSet                       $attributeSet,
        private readonly EavSetupFactory                    $eavSetupFactory,
        private readonly ResourceProduct                    $resourceProduct,
        private readonly ModuleDataSetupInterface           $moduleDataSetup,
        private readonly EavConfig                          $eavConfig,
        private readonly AttributeOptionManagementInterface $optionManagement,
        private readonly AttributeOptionInterfaceFactory    $optionFactory
    ) {
        $this->eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
    }

    /**
     * Converte o nome de uma variante Omnik no attribute_code Magento correspondente.
     * Reusa o atributo já existente quando a variante é conhecida (COR, TAMANHO...).
     *
     * @param string $variantName
     * @return string
     */
    public function resolveAttributeCode(string $variantName): string
    {
        $name = strtoupper(trim($variantName));
        if (isset(self::KNOWN_VARIANTS[$name])) {
            return self::KNOWN_VARIANTS[$name];
        }

        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim((string)$slug, '_');

        return self::ATTRIBUTE_PREFIX . $slug;
    }

    /**
     * Garante que o atributo select existe e está em todos os attribute sets.
     * Não recria se já existir (idempotente).
     *
     * @param string $variantName
     * @return string attribute_code resolvido
     */
    public function ensureAttribute(string $variantName): string
    {
        $attributeCode = $this->resolveAttributeCode($variantName);
        $label = ucfirst(strtolower(trim($variantName)));

        $existingId = $this->eavSetup->getAttributeId(Product::ENTITY, $attributeCode);
        if (!$existingId) {
            $this->eavSetup->addAttribute(
                Product::ENTITY,
                $attributeCode,
                [
                    'type' => 'int',
                    'label' => $label,
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
        }

        $this->addAttributeToAllSets($attributeCode);
        $this->eavConfig->clear();

        return $attributeCode;
    }

    /**
     * Adiciona o atributo a todos os attribute sets (grupo General).
     *
     * @param string $attributeCode
     * @return void
     */
    private function addAttributeToAllSets(string $attributeCode): void
    {
        $entityType = $this->resourceProduct->getEntityType();
        $attributeSetCollection = $this->attributeSet->setEntityTypeFilter($entityType);

        foreach ($attributeSetCollection as $attributeSet) {
            $this->eavSetup->addAttributeToSet(
                'catalog_product',
                $attributeSet->getAttributeSetName(),
                'General',
                $attributeCode
            );
        }
    }

    /**
     * Importa as opções (values) da variante para o atributo, sem duplicar.
     *
     * @param string $attributeCode
     * @param array $values lista de itens da variante Omnik (cada um com 'name')
     * @return int quantidade de opções novas criadas
     */
    public function importOptions(string $attributeCode, array $values): int
    {
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
        if (!$attribute || !$attribute->getAttributeId()) {
            return 0;
        }

        $existingLabels = [];
        if ($attribute->usesSource()) {
            foreach ($attribute->getSource()->getAllOptions() as $option) {
                if (!empty($option['label'])) {
                    $existingLabels[] = (string)$option['label'];
                }
            }
        }

        $created = 0;
        foreach ($values as $value) {
            $label = trim((string)($value['name'] ?? ''));
            if ($label === '' || in_array($label, $existingLabels, true)) {
                continue;
            }

            $option = $this->optionFactory->create();
            $option->setLabel($label);

            try {
                $this->optionManagement->add(
                    ProductAttributeInterface::ENTITY_TYPE_CODE,
                    $attributeCode,
                    $option
                );
                $existingLabels[] = $label;
                $created++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $created;
    }
}
