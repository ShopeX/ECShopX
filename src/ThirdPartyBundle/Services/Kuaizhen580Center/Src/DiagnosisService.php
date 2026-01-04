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

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Src;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\ItemsMedicine;
use GoodsBundle\Repositories\ItemsMedicineRepository;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Entities\OrdersDiagnosis;
use OrdersBundle\Entities\OrdersPrescription;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Repositories\OrderAssociationsRepository;
use OrdersBundle\Repositories\OrdersDiagnosisRepository;
use OrdersBundle\Repositories\OrdersPrescriptionRepository;
use ThirdPartyBundle\Services\Kuaizhen580Center\Api\CheckSign;
use ThirdPartyBundle\Services\Kuaizhen580Center\Api\DiagnosisGet;
use ThirdPartyBundle\Services\Kuaizhen580Center\Api\DiagnosisStatus;
use ThirdPartyBundle\Services\Kuaizhen580Center\Api\InitPreDemand;
use ThirdPartyBundle\Services\Kuaizhen580Center\Client\Request;

class DiagnosisService
{
    /** @var ItemsMedicineRepository $itemsMedicineRepository */
    private $itemsMedicineRepository;
    /** @var OrdersDiagnosisRepository $diagnosisRepository */
    private $diagnosisRepository;
    /** @var OrdersPrescriptionRepository $prescriptionRepository */
    private $prescriptionRepository;
    /** @var NormalOrdersRepository $ordersRepository */
    private $ordersRepository;
    /** @var OrderAssociationsRepository $orderAssociationRepository */
    private $orderAssociationRepository;

    public function __construct()
    {
        $this->itemsMedicineRepository = app('registry')->getManager('default')->getRepository(ItemsMedicine::class);
        $this->diagnosisRepository = app('registry')->getManager('default')->getRepository(OrdersDiagnosis::class);
        $this->prescriptionRepository = app('registry')->getManager('default')->getRepository(OrdersPrescription::class);
        $this->ordersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->orderAssociationRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
    }

    /**
     * 获取问诊状态
     * @param $companyId
     * @param $memberId
     * @return false|string
     */
    public function getDiagnosisStatus($companyId, $memberId)
    {
        if (empty($memberId)) {
            return false;
        }

        $requestParams = [
            'memberId' => $memberId,
        ];

        $api = new DiagnosisStatus($requestParams);
        $client = new Request($companyId, $api);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        $result = $resp->result;
        if (empty($result)) {
            return '';
        }
        return $result['locationUrl'] ?? '';
    }

    /**
     * 新增问诊单
     * @param $companyId
     * @param $memberId
     * @return false|string
     */
    public function initPreDemand($companyId, $params)
    {
        // 0x456353686f7058
        $requestParams = [
            'memberId' => $params['order_id'], // 同一个用户同时多个问诊单，如果用userid可能会存在不能进行新的问诊
            'openid' => $params['openid'] ?? '', // 否 用户对应小程序的openid
            'headimgurl' => $params['headimgurl'] ?? '', // 否 问诊人头像
            'storeId' => $params['store_id'], // 是 门店ID（由580提供）
            'serviceType' => $params['service_type'], // 是 服务类型，0为图文，1为视频
            'isExamine' => $params['is_examine'], // 是 是否需要审方（0为不需要，1为需要）
            'isPregnantWoman' => $params['is_pregnant_woman'], // 是 用药人是否孕妇（0为否，1为是）
            'isLactation' => $params['is_lactation'], // 是 用药人是否哺乳期0为否，1为是
            'souceFrom' => $params['source_from'], // 是 来源（0为微信小程序，1为APP，2为H5，3为支付宝小程序）
            'userFamilyName' => $params['user_family_name'], // 是 用药人姓名
            'userFamilyIdCard' => $params['user_family_id_card'], // 是 用药人身份证号
            'userFamilyAge' => $params['user_family_age'], // 是 用药人年龄
            'userFamilyGender' => $params['user_family_gender'], // 是 用药人性别1-男，2-女
            'userFamilyPhone' => $params['user_family_phone'], // 是 用药人手机号码
            'relationship' => $params['relationship'], // 是 用药人与问诊人关系(1本人 2父母 3配偶 4子女 5其他)
            'bizOrderId' => $params['order_id'], // 是 第三方唯一订单号
            'beforeAiDataList' => [ // 是 AI问诊前面题目，传五道题
                [
                    'subjectId' => 1, // 题目ID，第一题存症状，固定为1
                    'answer' => $params['before_ai_result_symptom'] //题目答案。诊断字符串（多个诊断用逗号分隔）
                ],
                [
                    'subjectId' => 2, // 题目ID，第二题存居民用药信息选择，固定为2
                    'answer' => '', // 第二题该值为空
                    'answerMedicine' => is_array($params['before_ai_result_medicines']) ? json_encode($params['before_ai_result_medicines'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : $params['before_ai_result_medicines'] // 存问诊用药信息选择，medicineId为第三方药品ID，number为药品数量。该字段是一字符串。
                ],
                [
                    'subjectId' => 3, // 题目ID，第三题存是否使用过此类药物？，固定为3
                    'answer' => $params['before_ai_result_used_medicine'] // 题目答案：是，否
                ],
                [
                    'subjectId' => 4, // 题目ID，第四题存是否有药物过敏史?，固定为4
                    'answer' => $params['before_ai_result_allergy_history'] // 题目答案：是（青霉素,红霉素等），否
                ],
                [
                    'subjectId' => 5, // 题目ID，第五题存肝肾功能是否有异常?，固定为5
                    'answer' => $params['before_ai_result_body_abnormal'] // 题目答案：是，否
                ],
            ],
            'userFamilyAddr' => $params['user_family_addr'] ?? '', // 否 用药人地址
            'imgList' => $params['img_list'] ?? '', // 否 用药人地址
            'thirdReturnUrl' => $params['third_return_url'] ?? '', // 否 结束问诊后、查看详情按钮或IM页面头部返回按钮跳回三方的地址，字段详细解释见“thirdReturnUrl参数详细说明” 一节
        ];

        $api = new InitPreDemand($requestParams);
        $client = new Request($companyId, $api);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        $result = $resp->result;

        return $result ?: '';
    }

    /**
     * 获取问诊详情
     * @param $companyId
     * @param $orderId
     * @return mixed
     */
    public function getDiagnosisDetail($companyId, $orderId)
    {
        // 0x456353686f7058
        $requestParams = [
            'bizOrderId' => $orderId,
        ];

        $api = new DiagnosisGet($requestParams);
        $client = new Request($companyId, $api);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        $result = $resp->result;
        if (empty($result)) {
            throw new ResourceException('无数据');
        }
        return $result;
    }

    /**
     * 4.10 580推送开方/重开，更新问诊详情
     * @param $params
     * @return array
     */
    public function prescriptionMedication($params)
    {
        $orderId = $params['bizOrderId'];
        $orderInfo = $this->ordersRepository->getInfo(['order_id' => $orderId]);
        if (empty($orderInfo)) {
            throw new ResourceException('订单不存在');
        }

        // 查询问诊单信息
        $diagnosisInfo = $this->diagnosisRepository->getInfo([
            'order_id' => $orderId
        ]);
        if (empty($diagnosisInfo)) {
            throw new ResourceException('问诊单不存在');
        }

        // todo 更新/新增处方单信息
        $prescriptionInfo = $this->prescriptionRepository->getInfo([
            'order_id' => $orderId
        ]);

        $data = [
            'order_id' => $orderInfo['order_id'],
            'user_id' => $orderInfo['user_id'],
            'company_id' => $orderInfo['company_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'hospital_name' => $params['hospitalName'],
            'kuaizhen_store_id' => $params['storeId'],
            'kuaizhen_store_name' => $params['storeName'],
            'kuaizhen_diagnosis_id' => $params['diagnosisId'],
            'diagnosis_id' => $diagnosisInfo['diagnosis_id'],
            'doctor_sign_time' => $params['doctorSignTime'] ? $params['doctorSignTime']/1000 : 0,
            'doctor_office' => $params['doctorOffice'],
            'doctor_id' => $params['doctorId'],
            'doctor_name' => $params['doctorName'],
            'user_family_name' => $params['userFamilyName'],
            'user_family_phone' => $params['userFamilyPhone'],
            'user_family_gender' => $params['userFamilyGender'],
            'user_family_age' => $params['userFamilyAge'],
            'tags' => $params['tags'],
            'status' => $params['status'],
            'memo' => $params['memo'],
            'remarks' => $params['remarks'],
            'reason' => $params['reason'],
            'dst_file_path' => $params['dstFilePath'],
            'serial_no' => $params['serialNo'],
            'drug_rsp_list' => $params['drugRspList'],
        ];

        if ($prescriptionInfo) {
            $this->prescriptionRepository->updateOneBy(['id' => $prescriptionInfo['id']], $data);
        } else {
            $this->prescriptionRepository->create($data);
        }

        return ['success' => true];
    }

    /**
     * 4.11 问诊信息推送接口-第三方提供（580问诊结束时推送）
     * @return true[]
     */
    public function diagnosisFinish($params)
    {
        $orderId = $params['bizOrderId'];
        $orderInfo = $this->ordersRepository->getInfo(['order_id' => $orderId]);
        if (empty($orderInfo)) {
            throw new ResourceException('订单不存在');
        }

        $filter = [
            'order_id' => $orderId
        ];
        $updateData = [
            'status' => $params['status'],
            'end_time' => ($params['endTime'] ?? 0) ? $params['endTime']/1000 : 0,
            'cancel_time' => ($params['cancelTime'] ?? 0) ? $params['cancelTime']/1000 : 0,
            'doctor_office' => $params['doctorOffice'],
            'doctor_name' => $params['doctorName'],
            'hospital_name' => $params['hospitalName'],
            'first_visit_list' => is_array($params['firstVisitList']) ? json_encode($params['firstVisitList']) : $params['firstVisitList'],
        ];

        $this->diagnosisRepository->updateBy($filter, $updateData);

        return ['success' => true];
    }

    /**
     * 4.12 处方作废
     * @param $params
     * @return true[]
     */
    public function prescriptionMedicationDelete($params)
    {
        $orderId = $params['bizOrderId'];
        $orderInfo = $this->ordersRepository->getInfo(['order_id' => $orderId]);
        if (empty($orderInfo)) {
            throw new ResourceException('订单不存在');
        }

        $filter = [
            'order_id' => $orderId
        ];
        $updateData = [
            'status' => 2,
            'is_deleted' => 1,
            'delete_time' => time()
        ];
        // 处方作废
        $this->prescriptionRepository->updateBy($filter, $updateData);

        // 未支付订单，自动取消订单
        if ($orderInfo['pay_status'] == 'NOTPAY' && $orderInfo['order_status'] != 'CANCEL') {
            $this->ordersRepository->updateBy(['order_id' => $orderId], ['order_status' => 'CANCEL']);
            $this->orderAssociationRepository->batchUpdateBy(['order_id' => $orderId], ['order_status' => 'CANCEL']);
        }

        return ['success' => true];
    }

    /**
     * 4.13 处方药师审核
     * @param $params
     * @return void
     */
    public function prescriptionMedicationAudit($params)
    {

    }

    /**
     * 4.15 医生拒绝开方
     * @param $params
     * @return true[]
     */
    public function refusePrescribe($params)
    {
        $orderId = $params['bizOrderId'];
        $orderInfo = $this->ordersRepository->getInfo(['order_id' => $orderId]);
        if (empty($orderInfo)) {
            throw new ResourceException('订单不存在');
        }

        // 查询问诊单信息
        $diagnosisInfo = $this->diagnosisRepository->getInfo([
            'order_id' => $orderId
        ]);
        if (empty($diagnosisInfo)) {
            throw new ResourceException('问诊单不存在');
        }

        $this->diagnosisRepository->updateOneBy(['id' => $diagnosisInfo['id']], [
            'prescription_status' => 3,
            'prescription_refuse_reason' => $params['reason'],
        ]);

        // 自动取消订单
        $this->ordersRepository->updateBy(['order_id' => $orderId], ['order_status' => 'CANCEL']);
        $this->orderAssociationRepository->batchUpdateBy(['order_id' => $orderId], ['order_status' => 'CANCEL']);

        return ['success' => true];
    }

    /**
     * 4.16 处方开具且审核后推送接口
     * @param $params
     * @return array
     */
    public function prescriptionMedicationAndAudit($params)
    {
        $orderId = $params['bizOrderId'];
        $orderInfo = $this->ordersRepository->getInfo(['order_id' => $orderId]);
        if (empty($orderInfo)) {
            throw new ResourceException('订单不存在');
        }

        // 查询问诊单信息
        $diagnosisInfo = $this->diagnosisRepository->getInfo([
            'order_id' => $orderId
        ]);
        if (empty($diagnosisInfo)) {
            throw new ResourceException('问诊单不存在');
        }

        // todo 更新/新增处方单信息
        $prescriptionInfo = $this->prescriptionRepository->getInfo([
            'order_id' => $orderId
        ]);

        if (!empty($params['dstFilePath'])) {
            $storage = 'import-image';
            $filesystem = app('filesystem')->disk($storage);
            $fileName = basename($params['dstFilePath']);
            $filePath = 'order_dst_file_path/' . $orderInfo['company_id'] . '/' . $orderInfo['order_id'] . '/' . $fileName;
            $filesystem->put($filePath, file_get_contents($params['dstFilePath']));
            $fileUlr = $filesystem->url($filePath);
        }

        $data = [
            'order_id' => $orderInfo['order_id'],
            'user_id' => $orderInfo['user_id'],
            'prescription_id' => $params['pid'],
            'company_id' => $orderInfo['company_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'hospital_name' => $params['hospitalName'],
            'kuaizhen_store_id' => $params['storeId'],
            'kuaizhen_store_name' => $params['storeName'],
            'kuaizhen_diagnosis_id' => $params['diagnosisId'],
            'diagnosis_id' => $diagnosisInfo['id'],
            'doctor_sign_time' => $params['doctorSignTime'] ? $params['doctorSignTime']/1000 : 0,
            'doctor_office' => $params['doctorOffice'],
            'doctor_id' => $params['doctorId'],
            'doctor_name' => $params['doctorName'],
            'user_family_name' => $params['userFamilyName'],
            'user_family_phone' => $params['userFamilyPhone'],
            'user_family_gender' => $params['userFamilyGender'],
            'user_family_age' => $params['userFamilyAge'],
            'tags' => $params['tags'],
            'status' => $params['status'],
            'memo' => $params['memo'],
            'remarks' => $params['remarks'],
            'reason' => $params['reason'],
            'dst_file_path' => $fileUlr ?? '', // $params['dstFilePath'],
            'serial_no' => $params['serialNo'],
            'drug_rsp_list' => is_array($params['drugRspList']) ? json_encode($params['drugRspList']) : $params['drugRspList'],
            'audit_time' => $params['auditTime'] ? $params['auditTime']/1000 : 0,
            'audit_status' => $params['auditStatus'],
            'audit_apothecary_name' => $params['auditApothecaryName'],
            'audit_reason' => $params['auditFailReason'],
        ];

        if ($prescriptionInfo) {
            $this->prescriptionRepository->updateOneBy(['id' => $prescriptionInfo['id']], $data);
        } else {
            $this->prescriptionRepository->create($data);
        }

        $this->diagnosisRepository->updateOneBy(['id' => $diagnosisInfo['id']], [
            'prescription_status' => 2,
            'prescription_refuse_reason' => $params['reason'],
        ]);

        // 订单开方状态
        $this->ordersRepository->updateBy(['order_id' => $orderId], ['prescription_status' => 2]);

        // 审方失败，取消订单
        if ($data['audit_status'] == 3) {
            $this->ordersRepository->updateBy(['order_id' => $orderId], ['order_status' => 'CANCEL']);
            $this->orderAssociationRepository->batchUpdateBy(['order_id' => $orderId], ['order_status' => 'CANCEL']);
        }

        return ['success' => true];
    }
}
