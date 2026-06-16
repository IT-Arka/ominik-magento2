<?php
declare(strict_types=1);

namespace Omnik\Core\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Omnik\Core\Model\VariantAttributeMapFactory;
use Omnik\Core\Model\ResourceModel\VariantAttributeMap as VariantMapResource;
use Omnik\Core\Model\ResourceModel\VariantAttributeMap\CollectionFactory as VariantMapCollectionFactory;

/**
 * Backend model que persiste o de-para de variantes na tabela própria.
 */
class VariantAttributeMap extends Value
{
    /**
     * Variantes core tratadas no "Mapeamento de Atributos Core" (fixo).
     * Não são persistidas aqui para evitar conflito de fonte da verdade.
     */
    private const CORE_VARIANTS = ['COR', 'TAMANHO', 'EMBALAGEM', 'SELLER'];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var VariantAttributeMapFactory
     */
    private $mapFactory;

    /**
     * @var VariantMapResource
     */
    private $mapResource;

    /**
     * @var VariantMapCollectionFactory
     */
    private $collectionFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SerializerInterface $serializer
     * @param VariantAttributeMapFactory $mapFactory
     * @param VariantMapResource $mapResource
     * @param VariantMapCollectionFactory $collectionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serializer,
        VariantAttributeMapFactory $mapFactory,
        VariantMapResource $mapResource,
        VariantMapCollectionFactory $collectionFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->mapFactory = $mapFactory;
        $this->mapResource = $mapResource;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _afterLoad()
    {
        $this->setValue($this->loadFromDatabase());
    }

    /**
     * @return array
     */
    private function loadFromDatabase()
    {
        $collection = $this->collectionFactory->create();
        $collection->getActiveMappings()->setOrder('entity_id', 'ASC');

        $rows = [];
        foreach ($collection as $mapping) {
            $rows[] = [
                'omnik_variant' => $mapping->getOmnikVariant(),
                'attribute_code' => $mapping->getAttributeCode()
            ];
        }

        return $rows;
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            $this->setData('_temp_map_data', $value);
            $this->setValue($this->serializer->serialize($value));
        }
        return parent::beforeSave();
    }

    /**
     * @return $this
     */
    public function afterSave()
    {
        $this->persistToDatabase();
        $this->setValue($this->loadFromDatabase());
        return parent::afterSave();
    }

    /**
     * @return void
     */
    private function persistToDatabase()
    {
        $tempData = $this->getData('_temp_map_data');
        $value = $tempData ?: $this->getValue();

        if (is_string($value)) {
            try {
                $rows = $this->serializer->unserialize($value);
            } catch (\Exception $e) {
                $this->_logger->error('Erro ao desserializar variant attribute map: ' . $e->getMessage());
                return;
            }
        } else {
            $rows = $value;
        }

        if (!is_array($rows)) {
            return;
        }

        try {
            // Remove mapeamentos antigos
            $collection = $this->collectionFactory->create();
            foreach ($collection as $mapping) {
                $this->mapResource->delete($mapping);
            }

            $seen = [];
            foreach ($rows as $row) {
                $variant = trim((string)($row['omnik_variant'] ?? ''));
                $attribute = trim((string)($row['attribute_code'] ?? ''));
                if ($variant === '' || $attribute === '' || isset($seen[$variant])) {
                    continue;
                }
                // Variantes core são tratadas no mapeamento fixo — ignora aqui.
                if (in_array(strtoupper($variant), self::CORE_VARIANTS, true)) {
                    continue;
                }
                $seen[$variant] = true;

                $map = $this->mapFactory->create();
                $map->setData([
                    'omnik_variant' => $variant,
                    'attribute_code' => $attribute,
                    'is_active' => 1
                ]);
                $this->mapResource->save($map);
            }
        } catch (\Exception $e) {
            $this->_logger->error(
                'Erro ao salvar variant attribute map: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
