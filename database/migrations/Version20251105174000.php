<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251105174000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $conn = $this->connection;
        
        // 确保companys表存在默认company
        $companyId = config('common.system_companys_id', 1);
        
        $companyExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM companys WHERE company_id = ?",
            [$companyId]
        );
            
        if (!$companyExists) {
            // 菜单类型映射：2:'b2c', 3:'platform', 4:'standard', 5:'in_purchase'
            $productModel = config('common.product_model', 'platform');
            $menuTypeMap = [
                'b2c' => 2,
                'platform' => 3,
                'standard' => 4,
                'in_purchase' => 5,
            ];
            $menuType = $menuTypeMap[$productModel] ?? 3; // 默认 platform
            
            // 先检查admin账号是否已存在
            $adminExists = $conn->fetchOne(
                "SELECT COUNT(*) FROM operators WHERE login_name = ? AND operator_type = ?",
                ['admin', 'admin']
            );
            
            $adminOperatorId = null;
            
            if (!$adminExists) {
                // 先创建默认admin账号（密码：Shopex123，已加密）
                // 注意：此时还没有 company_id，先设置为 null
                $conn->insert('operators', [
                    'login_name' => 'admin',
                    'mobile' => 'admin',
                    'password' => password_hash('Shopex123', PASSWORD_DEFAULT),
                    'operator_type' => 'admin',
                    'company_id' => null, // 先不设置，等创建 company 后再更新
                    'created' => time(),
                    'updated' => time(),
                ]);
                $adminOperatorId = $conn->lastInsertId();
            } else {
                // 如果已存在，更新密码为Shopex123（确保密码正确）
                $conn->update('operators', [
                    'password' => password_hash('Shopex123', PASSWORD_DEFAULT),
                    'updated' => time(),
                ], [
                    'login_name' => 'admin',
                    'operator_type' => 'admin',
                ]);
                
                // 获取已存在的 admin operator_id
                $adminOperatorId = $conn->fetchOne(
                    "SELECT operator_id FROM operators WHERE login_name = ? AND operator_type = ?",
                    ['admin', 'admin']
                );
            }
            
            // 创建默认company，使用 admin operator_id
            $conn->insert('companys', [
                'company_id' => $companyId,
                'company_name' => '默认企业',
                'passport_uid' => 'admin',
                'eid' => '',
                'menu_type' => $menuType,
                'company_admin_operator_id' => $adminOperatorId,
                'expiredAt' => strtotime('2037-01-01'),
                'created' => time(),
                'updated' => time(),
            ]);
            
            // 更新 admin operator 的 company_id
            if ($adminOperatorId) {
                $conn->update('operators', [
                    'company_id' => $companyId,
                    'updated' => time(),
                ], [
                    'operator_id' => $adminOperatorId,
                ]);
            }
        } else {
            // 如果 company 已存在，只检查并更新 admin 账号
            $adminExists = $conn->fetchOne(
                "SELECT COUNT(*) FROM operators WHERE login_name = ? AND operator_type = ?",
                ['admin', 'admin']
            );
                
            if (!$adminExists) {
                // 创建默认admin账号（密码：Shopex123，已加密）
                $conn->insert('operators', [
                    'login_name' => 'admin',
                    'mobile' => 'admin',
                    'password' => password_hash('Shopex123', PASSWORD_DEFAULT),
                    'operator_type' => 'admin',
                    'company_id' => $companyId,
                    'created' => time(),
                    'updated' => time(),
                ]);
            } else {
                // 如果已存在，更新密码为Shopex123（确保密码正确）
                $conn->update('operators', [
                    'password' => password_hash('Shopex123', PASSWORD_DEFAULT),
                    'updated' => time(),
                ], [
                    'login_name' => 'admin',
                    'operator_type' => 'admin',
                ]);
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 删除admin账号（可选，一般不回滚）
        // $this->addSql("DELETE FROM operators WHERE login_name = 'admin' AND operator_type = 'admin'");
    }
}

