<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

use Doctrine\DBAL\Connection;

/**
 * 通过 members_associations.unionid 关联 members_wechatusers（无 user_id 列）。
 */
final class HistoricalSyncWechatBindResolver
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array{unionid: string, open_id: string}|null
     */
    public function resolve(int $companyId, int $userId): ?array
    {
        if ($companyId < 1 || $userId < 1) {
            return null;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT w.unionid, w.open_id
             FROM members_associations ma
             INNER JOIN members_wechatusers w
               ON w.company_id = ma.company_id AND w.unionid = ma.unionid
             WHERE ma.company_id = ? AND ma.user_id = ?
             ORDER BY ma.user_type = ? DESC, w.open_id ASC
             LIMIT 1',
            [$companyId, $userId, 'wechat']
        );
        if ($row === false || $row === []) {
            return null;
        }
        $unionid = trim((string) ($row['unionid'] ?? ''));
        $openId = trim((string) ($row['open_id'] ?? ''));
        if ($unionid === '' || $openId === '') {
            return null;
        }

        return ['unionid' => $unionid, 'open_id' => $openId];
    }

    public static function isRegisterAlreadyExistsError(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        if ($msg === '') {
            return false;
        }

        return str_contains($msg, '已被占用')
            || str_contains($msg, 'already exists')
            || str_contains($msg, '已存在');
    }
}
