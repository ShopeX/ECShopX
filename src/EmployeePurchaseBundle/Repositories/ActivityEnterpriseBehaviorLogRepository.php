<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EmployeePurchaseBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class ActivityEnterpriseBehaviorLogRepository extends EntityRepository
{
    public $table = 'employee_purchase_activity_enterprise_behavior_log';

    /**
     * @param array<string,mixed> $row
     */
    public function insertRow(array $row)
    {
        if (!isset($row['created'])) {
            $row['created'] = time();
        }
        if (isset($row['extra']) && is_array($row['extra'])) {
            $row['extra'] = json_encode($row['extra'], JSON_UNESCAPED_UNICODE);
        }
        $conn = app('registry')->getConnection('default');
        $conn->insert($this->table, $row);

        return (int) $conn->lastInsertId();
    }

    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            if (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }

        return $qb;
    }

    /**
     * @param array<string,mixed> $filter
     * @param string              $cols
     * @param array<string,string> $orderBy
     * @return array<int,array<string,mixed>>
     */
    public function getLists($filter, $cols = '*', $orderBy = ['id' => 'ASC'])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        foreach ($orderBy as $field => $val) {
            $qb->addOrderBy($field, $val);
        }

        return $qb->execute()->fetchAll();
    }

    /**
     * 是否存在指定绑定方式的 bind 流水（MySQL JSON：`extra.bind_channel`）。
     *
     * @param string $bindChannel 与员工认证 `auth_type` 一致，扫码为 `qr_code`
     */
    public function existsBindLogWithBindChannel(int $companyId, int $activityId, int $enterpriseId, int $userId, string $bindChannel): bool
    {
        $conn = app('registry')->getConnection('default');
        if ($conn->getDatabasePlatform()->getName() !== 'mysql') {
            return false;
        }
        $sql = 'SELECT 1 AS ok FROM '.$this->table
            .' WHERE company_id = ? AND activity_id = ? AND enterprise_id = ? AND user_id = ? AND behavior_type = ?'
            ." AND JSON_UNQUOTE(JSON_EXTRACT(extra, '$.bind_channel')) = ? LIMIT 1";
        $row = $conn->fetchAssoc($sql, [
            $companyId,
            $activityId,
            $enterpriseId,
            $userId,
            'bind',
            $bindChannel,
        ]);

        return !empty($row);
    }

    /**
     * 活动+企业维度 bind 流水条数（与一次成功绑定一一对应，用于名额校准）。
     */
    public function countBindEventsForActivityEnterprise(int $companyId, int $activityId, int $enterpriseId): int
    {
        $conn = app('registry')->getConnection('default');
        $sql = 'SELECT COUNT(*) AS c FROM '.$this->table
            .' WHERE company_id = ? AND activity_id = ? AND enterprise_id = ? AND behavior_type = ?';
        $row = $conn->fetchAssoc($sql, [$companyId, $activityId, $enterpriseId, 'bind']);

        return (int) ($row['c'] ?? 0);
    }
}
