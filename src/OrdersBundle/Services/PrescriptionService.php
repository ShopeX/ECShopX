<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OrdersBundle\Services;

use CompanysBundle\Services\SettingService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Repositories\DistributorRepository;
use MembersBundle\Entities\MedicationPersonnel;
use MembersBundle\Repositories\MedicationPersonnelRepository;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\OrdersDiagnosis;
use OrdersBundle\Entities\OrdersPrescription;
use OrdersBundle\Repositories\NormalOrdersItemsRepository;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Repositories\OrdersPrescriptionRepository;
use OrdersBundle\Repositories\OrdersDiagnosisRepository;
use ThirdPartyBundle\Entities\CompanyRelKuaizhen;
use ThirdPartyBundle\Repositories\CompanyRelKuaizhenRepository;
use ThirdPartyBundle\Services\Kuaizhen580Center\Src\DiagnosisService;

class PrescriptionService
{
    /** @var OrdersPrescriptionRepository $prescriptionRepository */
    public $prescriptionRepository;
    /** @var OrdersDiagnosisRepository $diagnosisRepository */
    public $diagnosisRepository;
    /** @var NormalOrdersRepository $ordersRepository */
    public $ordersRepository;
    /** @var DistributorRepository $distributorRepository */
    public $distributorRepository;
    /** @var MedicationPersonnelRepository $medicationPersonnelRepository */
    public $medicationPersonnelRepository;
    /** @var NormalOrdersItemsRepository $ordersItemsRepository */
    public $ordersItemsRepository;
    /** @var CompanyRelKuaizhenRepository $relKuaizhenRepository */
    public $relKuaizhenRepository;

    public function __construct()
    {
        $this->ordersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->ordersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $this->prescriptionRepository = app('registry')->getManager('default')->getRepository(OrdersPrescription::class);
        $this->diagnosisRepository = app('registry')->getManager('default')->getRepository(OrdersDiagnosis::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $this->medicationPersonnelRepository = app('registry')->getManager('default')->getRepository(MedicationPersonnel::class);
        $this->relKuaizhenRepository = app('registry')->getManager('default')->getRepository(CompanyRelKuaizhen::class);
    }

    /**
     * 创建问诊单
     * @param $params
     * @return array
     */
    public function createPrescription($params)
    {
        // 查询订单信息
        $orderFilter = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
        ];
        if ($params['prescription_order_random'] ?? '') {
            // 验证扫码订单
            $random = app('redis')->get('dianwu_prescription_order_random:' . $params['order_id'] . $params['prescription_order_random']);
            if (!$random) {
                // throw new ResourceException('二维码已失效');
            }
            unset($orderFilter['user_id']);
        }
        $orderInfo = $this->ordersRepository->getInfo($orderFilter);
        if (empty($orderInfo)) {
            throw new ResourceException('订单不存在');
        }
        $orderItems = $this->ordersItemsRepository->getList([
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
            'user_id' => $orderInfo['user_id'],
        ])['list'];
        $medicacines = [];
        foreach ($orderItems as $orderItem) {
            if ($orderItem['is_prescription']) {
                $medicacines[] = [
                    'medicineId' => $orderItem['item_id'],
                    'number' => $orderItem['num'],
                ];
            }
        }

        // 查询用药人信息
        $medicationPersonnelInfo = $this->medicationPersonnelRepository->getInfo([
            'id' => $params['medication_personnel_id'],
            'company_id' => $orderInfo['company_id'],
            'user_id' => $params['user_id'],
        ]);

        // 门店信息
        if ($orderInfo['distributor_id'] == 0) {
            $distributorInfo = $this->distributorRepository->getInfo([
                'company_id' => $orderInfo['company_id'],
                'distributor_self' => 1,
            ]);
        } else {
            $distributorInfo = $this->distributorRepository->getInfo([
                'distributor_id' => $orderInfo['distributor_id'],
                'company_id' => $orderInfo['company_id'],
            ]);
        }
        /*if (empty($distributorInfo)) {
            throw new ResourceException('门店不存在');
        }*/

        // 店铺没有配置580门店，使用默认配置的门店
        if (empty($distributorInfo['kuaizhen_store_id'])) {
            // 580配置的门店ID
            $medicineSetting = (new SettingService())->getMedicineSetting($orderInfo['company_id']);
            if (empty($medicineSetting)) {
                throw new ResourceException('缺少配置');
            }
            $kuaizhenStoreId = $medicineSetting['kuaizhen580_config']['kuaizhen_store_id'] ?? 0;
            if (!$kuaizhenStoreId) {
                throw new ResourceException('缺少配置');
            }
        } else {
            $kuaizhenStoreId = $distributorInfo['kuaizhen_store_id'];
        }

        // 症状
        $symptomArr = [];
        foreach ($params['before_ai_result_symptom'] as $value) {
            $symptomArr = array_merge($symptomArr, $value['value']);
        }
        $symptomArr = array_unique($symptomArr);

        $requestParams = [
            'user_id' => $orderInfo['user_id'],
            'openid' => '', // 否 用户对应小程序的openid
            'headimgurl' => '', // 否 问诊人头像
            'store_id' => $kuaizhenStoreId, // 是 门店ID（由580提供）
            'service_type' => $params['service_type'] ?? 0, // 是 服务类型，0为图文，1为视频
            'is_examine' => 1, // 是 是否需要审方（0为不需要，1为需要）
            'is_pregnant_woman' => $params['is_pregnant_woman'], // 是 用药人是否孕妇（0为否，1为是）
            'is_lactation' => $params['is_lactation'], // 是 用药人是否哺乳期0为否，1为是
            'source_from' => $params['source_from'] ?? 0, // 是 来源（0为微信小程序，1为APP，2为H5，3为支付宝小程序）
            'user_family_name' => $medicationPersonnelInfo['user_family_name'], // 是 用药人姓名
            'user_family_id_card' => $medicationPersonnelInfo['user_family_id_card'], // 是 用药人身份证号
            'user_family_age' => $medicationPersonnelInfo['user_family_age'], // 是 用药人年龄
            'user_family_gender' => $medicationPersonnelInfo['user_family_gender'], // 是 用药人性别1-男，2-女
            'user_family_phone' => $medicationPersonnelInfo['user_family_phone'], // 是 用药人手机号码
            'relationship' => $medicationPersonnelInfo['relationship'], // 是 用药人与问诊人关系(1本人 2父母 3配偶 4子女 5其他)
            'order_id' => $orderInfo['order_id'], // 是 第三方唯一订单号
            'before_ai_result_symptom' => implode(',', $symptomArr), // 第一题 1症状
            'before_ai_result_medicines' => $medicacines, // 第二题 药品列表
            'before_ai_result_used_medicine' => $params['before_ai_result_used_medicine'] ? '是' : '否', // 第三题存是否使用过此类药物？
            'before_ai_result_allergy_history' => $params['before_ai_result_allergy_history'] ?: '否', // 第四题存是否有药物过敏史?
            'before_ai_result_body_abnormal' => $params['before_ai_result_body_abnormal'] ? '是' : '否', // 第五题存肝肾功能是否有异常?
            'user_family_addr' => $params['user_family_addr'] ?? '', // 否 用药人地址
            'img_list' => $params['img_list'] ?? '', // 否 图片附件（url形式，最多三个），多个以英文逗号,隔开
            'third_return_url' => $params['third_return_url'] ?? '', // 结束问诊后、查看详情按钮或IM页面头部返回按钮跳回三方的地址
        ];

        $diagnosisService = new DiagnosisService();
        $result = $diagnosisService->initPreDemand($params['company_id'], $requestParams);

        if ($result) {
            $result .= '&thirdPlatform=0';
            $beforeAiDataList = [
                'before_ai_result_symptom' => $params['before_ai_result_symptom'],
                'before_ai_result_medicines' => $medicacines,
                'before_ai_result_used_medicine' => $params['before_ai_result_used_medicine'],
                'before_ai_result_allergy_history' => $params['before_ai_result_allergy_history'],
                'before_ai_result_body_abnormal' => $params['before_ai_result_body_abnormal'],
            ];
            $this->diagnosisRepository->create([
                'order_id' => $orderInfo['order_id'],
                'user_id' => $orderInfo['user_id'],
                'company_id' => $orderInfo['company_id'],
                'kuaizhen_store_id' => $kuaizhenStoreId,
                'distributor_id' => $distributorInfo['distributor_id'] ?? 0,
                'service_type' => $requestParams['service_type'],
                'is_examine' => $requestParams['is_examine'],
                'is_pregnant_woman' => $requestParams['is_pregnant_woman'],
                'is_lactation' => $requestParams['is_lactation'],
                'source_from' => $requestParams['source_from'] ?? 0,
                'user_family_name' => $requestParams['user_family_name'],
                'user_family_id_card' => $requestParams['user_family_id_card'],
                'user_family_age' => $requestParams['user_family_age'],
                'user_family_gender' => $requestParams['user_family_gender'],
                'user_family_phone' => $requestParams['user_family_phone'],
                'relationship' => $requestParams['relationship'],
                'before_ai_data_list' => json_encode($beforeAiDataList),
                'location_url' => $result,
            ]);
        } else {
            throw new ResourceException('问诊单创建失败');
        }

        return ['url' => $result];
    }

    /**
     * 检查订单是否已开处方且审核通过
     * @param $orderInfo
     * @return array
     */
    public function checkOrderPrescriptionStatus($orderInfo)
    {
        $prescriptionInfo = $this->getOrderPrescriptionInfo($orderInfo);

        if (empty($prescriptionInfo)) {
            throw new ResourceException('处方药商品请先开方');
        }
        if ($prescriptionInfo['status'] != 1) {
            throw new ResourceException('处方单已作废');
        }
        if ($prescriptionInfo['audit_status'] == 1) {
            throw new ResourceException('处方单待审核');
        }
        if ($prescriptionInfo['audit_status'] == 3) {
            throw new ResourceException('处方单审核未通过');
        }

        return $prescriptionInfo;
    }

    /**
     * 获取订单处方单信息
     * @param $orderInfo
     * @return array
     */
    public function getOrderPrescriptionInfo($orderInfo)
    {
        $result = $this->prescriptionRepository->getInfo([
            'order_id' => $orderInfo['order_id'],
            'company_id' => $orderInfo['company_id'],
            'user_id' => $orderInfo['user_id'],
        ]);

        return $result;
    }
}
