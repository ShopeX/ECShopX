<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

/**
 * TC-DB-COMPANY-01：同一 company_id 第二条插入触发唯一冲突（与迁移 uk_shuyun_op_company_id 语义一致，使用 SQLite 最小复现）。
 */
class CompanyShuyunOpenPlatformConfigUniqueConstraintTest extends TestCase
{
    public function testSecondRowWithSameCompanyIdViolatesUniqueConstraint(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE company_shuyun_open_platform_config (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL,
                auth_value TEXT NOT NULL,
                UNIQUE (company_id),
                UNIQUE (auth_value)
            )'
        );
        $pdo->exec("INSERT INTO company_shuyun_open_platform_config (company_id, auth_value) VALUES (42, 'auth-a')");

        $this->expectException(\PDOException::class);
        $pdo->exec("INSERT INTO company_shuyun_open_platform_config (company_id, auth_value) VALUES (42, 'auth-b')");
    }
}
