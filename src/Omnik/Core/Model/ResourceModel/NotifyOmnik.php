<?php

declare(strict_types=1);

namespace Omnik\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Omnik\Core\Api\Data\NotifyOmnikInterface;

class NotifyOmnik extends AbstractDb
{
    /**
     * Status value that marks a register as being processed by a worker.
     * Mirrors NotifyProductModerationDataInterface::STATUS_RUNNING.
     */
    private const STATUS_RUNNING = 5;

    private const STATUS_NOT_INTEGRATED = 0;

    public function _construct()
    {
        $this->_init(NotifyOmnikInterface::TABLE_NAME, NotifyOmnikInterface::ENTITY_ID);
    }

    /**
     * Atomically claim a batch of pending registers for a single worker.
     *
     * Marks up to $limit rows matching $criteria (status = NOT_INTEGRATED) with a
     * unique token and the RUNNING status in a single UPDATE, then returns the rows
     * that this worker actually claimed. Because the UPDATE ... LIMIT is atomic per
     * row, concurrent crons never claim the same register — this both partitions the
     * 5 parallel product jobs and prevents duplicate processing.
     *
     * @param string $workerToken unique token identifying this claim
     * @param array $criteria column => value equality filters (e.g. ['resource_type' => 'PRODUCT'])
     * @param int $limit maximum rows to claim
     * @param int|null $maxAttempts when set, only rows with attempts <= maxAttempts are claimed
     * @return array claimed rows as associative arrays
     */
    public function claimBatch(
        string $workerToken,
        array $criteria = [],
        int $limit = 50,
        ?int $maxAttempts = null
    ): array {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $where = ['status = ?' => self::STATUS_NOT_INTEGRATED];
        foreach ($criteria as $column => $value) {
            $where[$connection->quoteIdentifier($column) . ' = ?'] = $value;
        }
        if ($maxAttempts !== null) {
            $where['attempts <= ?'] = $maxAttempts;
        }

        // Build the WHERE clause as a single string with bound params for the UPDATE.
        $conditions = [];
        $bind = [$workerToken];
        foreach ($where as $clause => $value) {
            $conditions[] = $clause;
            $bind[] = $value;
        }
        $whereSql = implode(' AND ', $conditions);

        $sql = sprintf(
            'UPDATE %s SET %s = ?, status = %d WHERE %s ORDER BY %s ASC LIMIT %d',
            $connection->quoteIdentifier($table),
            $connection->quoteIdentifier(NotifyOmnikInterface::WORKER_TOKEN),
            self::STATUS_RUNNING,
            $whereSql,
            $connection->quoteIdentifier(NotifyOmnikInterface::ENTITY_ID),
            $limit
        );

        $affected = $connection->query($sql, $bind)->rowCount();
        if ($affected === 0) {
            return [];
        }

        $select = $connection->select()
            ->from($table)
            ->where(NotifyOmnikInterface::WORKER_TOKEN . ' = ?', $workerToken)
            ->order(NotifyOmnikInterface::ENTITY_ID . ' ASC');

        return $connection->fetchAll($select);
    }

    /**
     * Release a claimed register back to the pending queue (status = NOT_INTEGRATED)
     * and clear its worker token, so it can be retried by a later cron run.
     *
     * @param int $entityId
     * @return void
     */
    public function releaseClaim(int $entityId): void
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getMainTable(),
            [
                'status' => self::STATUS_NOT_INTEGRATED,
                NotifyOmnikInterface::WORKER_TOKEN => null,
            ],
            [NotifyOmnikInterface::ENTITY_ID . ' = ?' => $entityId]
        );
    }
}
