<?php
declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class StatusMapping
 * Status mapping resource model
 */
class StatusMapping extends AbstractDb
{
    /**
     * Table name
     */
    const TABLE_NAME = 'omnik_core_status_mapping';

    /**
     * Primary key field name
     */
    const PRIMARY_KEY = 'entity_id';

    /**
     * Constructor
     *
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
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }

    /**
     * Get status mapping by Omnik status
     *
     * @param string $omnikStatus
     * @return array
     */
    public function getByOmnikStatus($omnikStatus)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('omnik_status = ?', $omnikStatus)
            ->where('is_active = ?', 1)
            ->limit(1);

        return $connection->fetchRow($select);
    }

    /**
     * Get status mapping by Adobe status
     *
     * @param string $adobeStatus
     * @return array
     */
    public function getByAdobeStatus($adobeStatus)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('adobe_status = ?', $adobeStatus)
            ->where('is_active = ?', 1)
            ->limit(1);

        return $connection->fetchRow($select);
    }

    /**
     * Get all active mappings
     *
     * @return array
     */
    public function getAllActiveMappings()
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('is_active = ?', 1)
            ->order('entity_id ASC');

        return $connection->fetchAll($select);
    }

    /**
     * Check if mapping exists
     *
     * @param string $omnikStatus
     * @param string $adobeStatus
     * @return bool
     */
    public function mappingExists($omnikStatus, $adobeStatus)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['COUNT(*)'])
            ->where('omnik_status = ?', $omnikStatus)
            ->where('adobe_status = ?', $adobeStatus)
            ->where('is_active = ?', 1);

        return (bool) $connection->fetchOne($select);
    }

    /**
     * Delete mapping by Omnik status
     *
     * @param string $omnikStatus
     * @return int
     */
    public function deleteByOmnikStatus($omnikStatus)
    {
        $connection = $this->getConnection();
        return $connection->delete(
            $this->getMainTable(),
            ['omnik_status = ?' => $omnikStatus]
        );
    }
}
