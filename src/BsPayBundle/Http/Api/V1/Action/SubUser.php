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

namespace BsPayBundle\Http\Api\V1\Action;

use BsPayBundle\Services\SubUserService;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;

/**
 * 用户
 */
class SubUser extends Controller
{
    /**
     * 斗拱子商户审批列表
     * path = "/bspay/sub_approve/list"
     */
    public function subApproveLists(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $params = $request->all('status', 'user_name', 'address', 'time_start', 'time_end');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $subUserService = new SubUserService();
        $result = $subUserService->getSubApproveLists($companyId, $params, $page, $pageSize);
        return $this->response->array($result);
    }
    
    public function subApproveInfo($id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $subUserService = new SubUserService();
        $result = $subUserService->getSubApproveInfo($companyId, $id);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        if ($datapassBlock) {
            $result['entry_info']['tel_no'] = data_masking('mobile', (string) $result['entry_info']['tel_no']);
            if ($result['entry_info']['member_type'] == 'corp') {
                $result['entry_info']['legal_person'] = data_masking('truename', (string) $result['entry_info']['legal_person']);
                $result['entry_info']['card_no'] = data_masking('bankcard', (string) $result['entry_info']['card_no']);
                $result['entry_info']['legal_cert_id'] = data_masking('idcard', (string) $result['entry_info']['legal_cert_id']);
            } else {
                $result['entry_info']['user_name'] = data_masking('truename', (string) $result['entry_info']['user_name']);
                $result['entry_info']['cert_id'] = data_masking('idcard', (string) $result['entry_info']['cert_id']);
                $result['entry_info']['bank_card_name'] = data_masking('truename', (string) $result['entry_info']['bank_card_name']);
                $result['entry_info']['bank_tel_no'] = data_masking('mobile', (string) $result['entry_info']['bank_tel_no']);
                $result['entry_info']['bank_card_id'] = data_masking('bankcard', (string) $result['entry_info']['bank_card_id']);
                $result['entry_info']['bank_cert_id'] = data_masking('idcard', (string) $result['entry_info']['bank_cert_id']);
            }
            if (isset($result['entry_apply_info']) && $result['entry_apply_info']) {
                $result['entry_apply_info']['user_name'] = data_masking('truename', (string) $result['entry_apply_info']['user_name']);
            }
            if (isset($result['dealer_info']) && $result['dealer_info']) {
                $result['dealer_info']['mobile'] = data_masking('mobile', (string) $result['dealer_info']['mobile']);
            }
            if (isset($result['distributor_info']) && $result['distributor_info']) {
                $result['distributor_info']['mobile'] = data_masking('mobile', (string) $result['distributor_info']['mobile']);
                // $result['distributor_info']['store_address'] = data_masking('detailedaddress', (string) $result['distributor_info']['store_address']);
            }
        }
        return $this->response->array($result);
    }

    /**
     * 斗拱子商户审批保存
     * path = "/bspay/sub_approve/save_audit"
     */
    public function saveAudit(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('split_ledger_info','operator_type', 'save_id', 'status', 'comments', 'id', 'save_id');

        $subUserService = new SubUserService();
        $result = $subUserService->saveAudit($companyId, $params);

        return $this->response->array($result);
    }
}
