<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use SuperAdminBundle\Services\LogisticsService;

class Version00000000000004 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_companyid_userid ON popularize_promoter (company_id, user_id)');
        
        $this->initLogistics();
    }

    private function initLogistics()
    {
        try {
            $json_str = file_get_contents(storage_path('static/kuaidi.json'));
        } catch (\Exception $e) {
            throw new ResourceException('文件不存在！');
        }

        $corp = json_decode($json_str, true);

        if (empty($corp)) {
            throw new ResourceException('文件无数据！');
        }
        $logisticsService = new LogisticsService();

        $logisticsService->clearLogistics();

        foreach ($corp as $v) {
            if ($v['corp_code'] == '' || $v['corp_name'] == '') {
                continue;
            }
            $data = [
                'corp_code' => $v['corp_code'],
                'kuaidi_code' => $v['kuaidi_code'],
                'full_name' => $v['corp_name'],
                'corp_name' => $v['corp_name'],
                'custom' => 'false',
                'logo' => $v['logo'],
                'phone' => $v['phone'],
            ];
            $logisticsService->createLogistics($data);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
