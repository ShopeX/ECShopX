<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use AdaPayBundle\Services\AlipayIndustryCategoryService;

class Version00000000000015 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        //获取支付宝行业类目
        $this->getAlipayIndustryCategory();
    }

    public function getAlipayIndustryCategory() {
        $dataSource = 'https://cdn.cloudpnr.com/adapayresource/documents/Adapay%E6%9E%9A%E4%B8%BE%E6%95%B0%E6%8D%AE%E8%A1%A8.xlsx';
        $localPath = 'adapay/category_local.xlsx';

        $dataPath = storage_path($localPath);
        $categoryData = file_get_contents($dataSource);
        file_put_contents($dataPath, $categoryData);

        $sheets = app('excel')->toArray(new \stdClass(), $dataPath);

        if (!$sheets[0]) {
            return false;
        }

        foreach ($sheets[0] as $val) {
            if (!is_numeric($val[4])) {
                continue;
            }
            $alipayService = new AlipayIndustryCategoryService();
            $data = [
                'category_name' => $val[0],
                'parent_id' => 0,
                'category_level' => 1,
            ];
            $lv1 = $alipayService->getInfo($data);
            if (!$lv1) {
                $lv1 = $alipayService->create($data);
            }

            $data = [
                'category_name' => $val[1],
                'parent_id' => $lv1['id'],
                'category_level' => 2,
            ];
            $lv2 = $alipayService->getInfo($data);
            if (!$lv2) {
                $lv2 = $alipayService->create($data);
            }

            $data = [
                'category_name' => $val[2],
                'parent_id' => $lv2['id'],
                'category_level' => 3,
            ];
            $lv3 = $alipayService->getInfo($data);
            if (!$lv3) {
                $data['alipay_cls_id'] = $val[4];
                $data['alipay_category_id'] = $val[5];
                $lv3 = $alipayService->create($data);
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
