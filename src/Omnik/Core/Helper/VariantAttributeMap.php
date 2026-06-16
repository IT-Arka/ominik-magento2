<?php
declare(strict_types=1);

namespace Omnik\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Omnik\Core\Model\ResourceModel\VariantAttributeMap\CollectionFactory as VariantMapCollectionFactory;
use Omnik\Core\Model\Service\VariantAttributeManager;

/**
 * Resolve qual atributo de produto do Magento corresponde a uma variante da Omnik.
 *
 * Ordem de resolução:
 *  1. De-para configurado no admin (tabela omnik_variant_attribute_map);
 *  2. Atributos fixos conhecidos via Helper\Config (COR/TAMANHO/EMBALAGEM/SELLER);
 *  3. Convenção automática variant_<nome> (mesma usada pelo comando sync-attributes).
 */
class VariantAttributeMap extends AbstractHelper
{
    /**
     * @var array<string,string>|null cache do de-para em memória (uppercase => attribute_code)
     */
    private ?array $mapCache = null;

    /**
     * @param Context $context
     * @param VariantMapCollectionFactory $collectionFactory
     * @param Config $configHelper
     * @param VariantAttributeManager $attributeManager
     */
    public function __construct(
        Context $context,
        private readonly VariantMapCollectionFactory $collectionFactory,
        private readonly Config $configHelper,
        private readonly VariantAttributeManager $attributeManager
    ) {
        parent::__construct($context);
    }

    /**
     * Retorna o attribute_code Magento para uma variante Omnik.
     *
     * @param string $variantName
     * @return string
     */
    public function getAttributeCode(string $variantName): string
    {
        $key = strtoupper(trim($variantName));

        // 1. Variantes core (COR/TAMANHO/EMBALAGEM/SELLER) SEMPRE vêm do mapeamento fixo
        //    (Helper\Config). São estruturais — o storefront, swatches e split dependem
        //    delas. Não podem ser sobrescritas pelo de-para dinâmico para não divergir
        //    do que o front lê diretamente.
        $known = $this->getKnownAttribute($key);
        if ($known !== null) {
            return $known;
        }

        // 2. De-para dinâmico configurado no admin (variantes adicionais)
        $map = $this->getConfiguredMap();
        if (isset($map[$key]) && $map[$key] !== '') {
            return $map[$key];
        }

        // 3. Convenção automática variant_<nome> (plug-and-play, sem configurar nada)
        return $this->attributeManager->resolveAttributeCode($variantName);
    }

    /**
     * Carrega (com cache) o de-para configurado: NOME_OMNIK(uppercase) => attribute_code.
     *
     * @return array<string,string>
     */
    private function getConfiguredMap(): array
    {
        if ($this->mapCache !== null) {
            return $this->mapCache;
        }

        $this->mapCache = [];
        $collection = $this->collectionFactory->create();
        $collection->getActiveMappings();

        foreach ($collection as $mapping) {
            $variant = strtoupper(trim((string)$mapping->getOmnikVariant()));
            $attribute = trim((string)$mapping->getAttributeCode());
            if ($variant !== '' && $attribute !== '') {
                $this->mapCache[$variant] = $attribute;
            }
        }

        return $this->mapCache;
    }

    /**
     * Atributos fixos já mapeados no Helper\Config.
     *
     * @param string $upperVariantName
     * @return string|null
     */
    private function getKnownAttribute(string $upperVariantName): ?string
    {
        switch ($upperVariantName) {
            case 'COR':
                return $this->configHelper->getAttrVariantColor();
            case 'TAMANHO':
                return $this->configHelper->getAttrVariantTamanho();
            case 'EMBALAGEM':
                return $this->configHelper->getAttrVariantEmbalagem();
            case 'SELLER':
                return $this->configHelper->getAttrVariantSeller();
            default:
                return null;
        }
    }
}
