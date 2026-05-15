<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EmployeePurchaseBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class ActivityEnterpriseParticipateUserRepository extends EntityRepository
{
    public $table = 'employee_purchase_activity_enterprise_participate_user';

    public function existsForUser(int $companyId, int $activityId, int $enterpriseId, int $userId): bool
    {
        if ($companyId <= 0 || $activityId <= 0 || $enterpriseId <= 0 || $userId <= 0) {
            return false;
        }
        $conn = app('registry')->getConnection('default');
        $sql = 'SELECT 1 AS ok FROM '.$this->table
            .' WHERE company_id = ? AND activity_id = ? AND enterprise_id = ? AND user_id = ? LIMIT 1';
        $row = $conn->fetchAssoc($sql, [$companyId, $activityId, $enterpriseId, $userId]);

        return !empty($row);
    }

    /**
     * 插入已占用名额用户；已存在则忽略（幂等）。
     */
    public function insertIgnore(int $companyId, int $activityId, int $enterpriseId, int $userId): void
    {
        if ($companyId <= 0 || $activityId <= 0 || $enterpriseId <= 0 || $userId <= 0) {
            return;
        }
        $conn = app('registry')->getConnection('default');
        $conn->executeUpdate(
            'INSERT IGNORE INTO '.$this->table.' (company_id, activity_id, enterprise_id, user_id, created) VALUES (?,?,?,?,?)',
            [$companyId, $activityId, $enterpriseId, $userId, time()]
        );
    }
}
