<?php
declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Resource model do de-para variante Omnik -> atributo Magento.
 */
class VariantAttributeMap extends AbstractDb
{
    const TABLE_NAME = 'omnik_variant_attribute_map';
    const PRIMARY_KEY = 'entity_id';

    /**
     * @param Context $context
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }

    /**
     * Retorna o attribute_code mapeado para uma variante Omnik (ou null).
     *
     * @param string $omnikVariant
     * @return string|null
     */
    public function getAttributeCodeByVariant(string $omnikVariant): ?string
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['attribute_code'])
            ->where('omnik_variant = ?', $omnikVariant)
            ->where('is_active = ?', 1)
            ->limit(1);

        $result = $connection->fetchOne($select);

        return $result !== false && $result !== '' ? (string)$result : null;
    }
}
