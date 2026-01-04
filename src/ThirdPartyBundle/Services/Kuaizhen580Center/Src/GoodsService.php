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
use ThirdPartyBundle\Services\Kuaizhen580Center\Api\CheckSign;
use ThirdPartyBundle\Services\Kuaizhen580Center\Api\MedicineQueryAuditStatus;
use ThirdPartyBundle\Services\Kuaizhen580Center\Api\MedicineSync;
use ThirdPartyBundle\Services\Kuaizhen580Center\Client\Request;

class GoodsService
{
    /** @var ItemsMedicineRepository $itemsMedicineRepository */
    private $itemsMedicineRepository;

    public function __construct()
    {
        $this->itemsMedicineRepository = app('registry')->getManager('default')->getRepository(ItemsMedicine::class);
    }

    /**
     * 药品信息同步接口
     * 第三方的药品信息需要遵守相应的通用标准，具体标准请查看文档 https://docs.qq.com/doc/DR0hmWlpZeWVsdkhw 同步药品信息规范指引
     * @param $companyId
     * @param $medicines array 药品数据
     * @return bool
     */
    public function medicineSync($companyId, array $medicines): bool
    {
        $medicineList = [];
        foreach ($medicines as $medicine) {
            $medicineList[] = [
                'categoryId' => $medicine['medicine_type'], // 是 药品分类:0为西药，1为中成药，3为其他
                'commonName' => $medicine['common_name'], // 是 通用名（重要，具体请查看《同步药品信息规范指引》文档）
                'name' => $medicine['name'] ?? '', // 否 商品名
                'dosage' => $medicine['dosage'] ?? '', // 否 剂型
                'spec' => $medicine['spec'], // 是 规格（重要，具体请查看《同步药品信息规范指引》文档）
                'packingSpec' => $medicine['packing_spec'] ?? '', // 否 包装规格
                'manufacturer' => $medicine['manufacturer'], // 是 生产厂家
                'approvalNumber' => $medicine['approval_number'], // 是 批准文号
                'unit' => $medicine['unit'], // 是 最小售卖单位
                'medicineId' => $medicine['item_id'], // 是 第三方药品编码（唯一）
                'barCode' => $medicine['bar_code'] ?? '', // 否 商品条形码值
                'isPrescription' => intval(!$medicine['is_prescription']), // 是 是否处方药（0为是，1为否）
                'price' => $medicine['price'] ?? '', // 否 价格
                'stock' => $medicine['stock'] ?? '', // 否 库存（不传默认为有库存）
                'specialCommonName' => $medicine['special_common_name'] ?? '', // 否 特殊通用名
                'specialSpec' => $medicine['special_spec'] ?? '', // 否 特殊规格
            ];
        }
        app('log')->debug('medicineSync--->>>medicineList' . json_encode($medicineList));

        if (empty($medicineList)) {
            return false;
        }

        $requestParams = [
            'medicineList' => $medicineList,
            'callbackUrl' => env('API_BASE_URL', '') . 'third/kuaizhen/medicineAuditResult', // 药品同步结果通知地址
        ];

        $api = new MedicineSync($requestParams);
        $client = new Request($companyId, $api);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            app('log')->debug('medicineSync--->>>error' . json_encode($resp->msg));
            throw new ResourceException($resp->msg);
        }

        app('log')->debug('medicineSync--->>>result' . json_encode($resp->result));

        return true;
    }

    /**
     * 查询药品审核信息
     * @param $companyId
     * @param $medicineIds array 三方药品ID集合，根据三方提供的药品编码查询审核状态。如果List为空，则查询全部。
     * @return array
     */
    public function queryMedicineAuditStatus($companyId, array $medicineIds): array
    {
        $medicineIds = array_values($medicineIds);
        $requestParams = [];
        if ($medicineIds) {
            $requestParams['medicineIdList'] = $medicineIds;
        }

        $api = new MedicineQueryAuditStatus($requestParams);
        $client = new Request($companyId, $api);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        return $resp->result;
    }

    /**
     * 更新药品审核状态
     * @param $auditResult
     * @return bool
     */
    public function updateMedicineAuditResult($auditResult): bool
    {
        $filter = [
            'item_id' => $auditResult['medicineId'],
        ];
        $data = [
            'audit_status' => $auditResult['auditStatus'] == 0 ? 3 : 2, // 3为审核不通过，2为审核通过
            'audit_reason' => $auditResult['auditMsg'] ?? '',
        ];
        $this->itemsMedicineRepository->updateBy($filter, $data);

        return true;
    }
}
