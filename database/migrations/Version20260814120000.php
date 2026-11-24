<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

/**
 * ECX-11383: 团长申请表加唯一索引，防止 count-then-create 并发重复申请
 */
class Version20260814120000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 历史重复：每组 (company_id,user_id,distributor_id) 保留最优一条（通过>待审>驳回，同级取更大 apply_id）
        $this->addSql(
            'DELETE t1 FROM community_chief_apply_info t1
            INNER JOIN community_chief_apply_info t2
              ON t1.company_id = t2.company_id
             AND t1.user_id = t2.user_id
             AND t1.distributor_id = t2.distributor_id
             AND (
               FIELD(t1.approve_status, 1, 0, 2) > FIELD(t2.approve_status, 1, 0, 2)
               OR (
                 FIELD(t1.approve_status, 1, 0, 2) = FIELD(t2.approve_status, 1, 0, 2)
                 AND t1.apply_id < t2.apply_id
               )
             )'
        );

        $sm = $this->connection->getSchemaManager();
        $indexes = $sm->listTableIndexes('community_chief_apply_info');
        if (!isset($indexes['uk_company_user_distributor'])) {
            $this->addSql(
                'ALTER TABLE community_chief_apply_info ADD UNIQUE INDEX uk_company_user_distributor (company_id, user_id, distributor_id)'
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $sm = $this->connection->getSchemaManager();
        $indexes = $sm->listTableIndexes('community_chief_apply_info');
        if (isset($indexes['uk_company_user_distributor'])) {
            $this->addSql('ALTER TABLE community_chief_apply_info DROP INDEX uk_company_user_distributor');
        }
    }
}
