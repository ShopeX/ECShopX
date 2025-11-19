<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Commands;

use Illuminate\Console\Command;
use ThirdPartyBundle\Services\Kuaizhen580Center\Src\DiagnosisService;
use ThirdPartyBundle\Services\Kuaizhen580Center\Src\GoodsService;
use ThirdPartyBundle\Services\Kuaizhen580Center\Src\StoreService;

class KuaizhenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kuaizhen {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '快诊接口调试';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $type = $this->argument('type');
        switch ($type) {
            case 'sync':
                $this->medicineSync();
                break;
            case 'queryAudit':
                $this->queryMedicineAuditStatus();
                break;
            case 'initPreDemand':
                $this->initPreDemand();
                break;
            case 'diagnosisStatus':
                $this->diagnosisStatus();
                break;
            case 'queryStore':
                $this->queryStore();
                break;
            default:
                break;
        }
    }

    public function medicineSync()
    {
        // HACK: temporary solution
        $itemsData = [
            [
                'medicine_type' => 1,
                'common_name' => '阿莫西林胶囊',
                'name' => '仁和',
                'dosage' => 'g',
                'spec' => '0.25g*40粒',
                'packing_spec' => '10粒/板X4板',
                'manufacturer' => '安徽安科恒益药业有限公司',
                'approval_number' => '国药准字H34023532',
                'unit' => '盒',
                'item_id' => '1400',
                'bar_code' => '',
                'is_prescription' => 1,
                'price' => '',
                'stock' => '',
                'special_common_name' => '',
                'special_spec' => '',
            ]
        ];
        $service = new GoodsService();
        $result = $service->medicineSync(34, $itemsData);

        var_dump($result);
    }

    public function queryMedicineAuditStatus()
    {
        $service = new GoodsService();
        $result = $service->queryMedicineAuditStatus(34, []);

        var_dump($result);
    }

    public function initPreDemand()
    {
        $params = [
            'user_id' => '123',
            'store_id' => '17101',
            'service_type' => '0',
            'is_examine' => '1',
            'is_pregnant_woman' => '0',
            'is_lactation' => '0',
            'source_from' => '0',
            'user_family_name' => '吴星',
            'user_family_id_card' => '341221199806147339',
            'user_family_age' => '30',
            'user_family_gender' => '1',
            'user_family_phone' => '13095585013',
            'relationship' => '1',
            'order_id' => 'order123',
            'before_ai_result_symptom' => '风湿热邪攻目证,呃逆病',
            'before_ai_result_medicines' => [
                [
                    'medicineId' => 1400,
                    'number' => 1
                ]
            ],
            'before_ai_result_used_medicine' => '是',
            'before_ai_result_allergy_history' => '否',
            'before_ai_result_body_abnormal' => '否',
        ];
        $service = new DiagnosisService();
        $result = $service->initPreDemand(34, $params);

        var_dump($result);
    }

    public function diagnosisStatus()
    {
        $service = new DiagnosisService();
        $result = $service->getDiagnosisStatus(34, 123);

        var_dump($result);
    }

    public function queryStore()
    {
        $service = new StoreService();
        $result = $service->queryStore(34, ['name' => '达仁堂测试门店']);

        var_dump($result);
    }
}
