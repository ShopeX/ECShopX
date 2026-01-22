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

namespace SupplierBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\MemberService;
use App\Http\Controllers\Controller;
use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use AdaPayBundle\Services\AdapayDrawCashService;
use Illuminate\Http\Request;
use AdaPayBundle\Services\SubMerchantService;
use SupplierBundle\Services\SupplierService;

class Supplier extends Controller
{
    /**
     * @SWG\Post(
     *     path="/supplier/register",
     *     summary="供应商入驻",
     * )
     */
    public function register(Request $request)
    {
        $params = $request->all();
        $auth = app('auth')->user()->get();
        $params['company_id'] = $auth['company_id'];
        $params['operator_id'] = $auth['operator_id'];
        $params['is_check'] = 0;
        
        $rules = [
            'supplier_name' => ['required', trans('SupplierBundle.supplier_name_required')],
            'contact' => ['required', trans('SupplierBundle.contact_required')],
            'mobile' => ['required|size:11', trans('SupplierBundle.mobile_required')],
            'business_license' => ['required|max:200', trans('SupplierBundle.business_license_error')],
            'wechat_qrcode' => ['required|max:200', trans('SupplierBundle.wechat_qrcode_required')],
            'service_tel' => ['required|max:50', trans('SupplierBundle.service_tel_required')],
            'bank_name' => ['required|max:100', trans('SupplierBundle.bank_name_required')],
            'bank_account' => ['required|max:100', trans('SupplierBundle.bank_account_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        
        //company_id', 'supplier_name', 'contact', 'mobile', 'business_license', 'wechat_qrcode', 'service_tel', 'bank_name', 'bank_account', 'is_check', 'operator_id
        $filter = [
            'company_id' => $params['company_id'],
            'supplier_name' => $params['supplier_name'],
            // 'is_check' => [0, 2],
        ];
        
        $supplierService = new SupplierService();
        $supplierInfo = $supplierService->repository->getInfo($filter);
        if ($supplierInfo) {
            if ($supplierInfo['operator_id'] != $params['operator_id']) {
                throw new ResourceException(trans('SupplierBundle.supplier_name_duplicate'));
            }
            $result = $supplierService->repository->updateOneBy(['id' => $supplierInfo['id']], $params);
        } else {
            $filter = [
                'company_id' => $params['company_id'],
                'operator_id' => $params['operator_id'],
            ];
            $supplierInfo = $supplierService->repository->getInfo($filter);
            if ($supplierInfo) {
                $result = $supplierService->repository->updateOneBy(['id' => $supplierInfo['id']], $params);
            } else {
                $result = $supplierService->repository->create($params);
            }
        }

        return $this->response->array($result);
    }

    /**
     * /supplier/get_supplier_list
     */
    public function getSupplierList(Request $request)
    {
        $auth = app('auth')->user()->get();
        $company_id = $auth['company_id'];
        $is_check = $request->input('is_check', '');
        $supplier_name = trim($request->input('supplier_name', ''));
        $mobile = trim($request->input('mobile', ''));
        $page = intval($request->input('page', 1));
        $pageSize = intval($request->input('pageSize', 20));

        $filter = [];
        $filter['company_id'] = $company_id;
        if ($supplier_name) $filter['supplier_name|like'] = $supplier_name;
        if ($mobile) $filter['mobile|like'] = $mobile;
        if (is_numeric($is_check)) $filter['is_check'] = $is_check;

        $orderBy = ['id' => 'DESC'];

        $operatorsService = new OperatorsService();
        $supplierService = new SupplierService();
        $result = $supplierService->repository->lists($filter, '*', $page, $pageSize, $orderBy);
        foreach ($result['list'] as &$v) {
            switch ($v['is_check']) {
                case 0: $v['check_state'] = trans('SupplierBundle.status_pending_audit');break;
                case 1: $v['check_state'] = trans('SupplierBundle.status_audit_passed');break;
                case 2: $v['check_state'] = trans('SupplierBundle.status_audit_rejected');break;
            }
            
            $operatorInfo = $operatorsService->operatorsRepository->getInfo(['operator_id' => $v['operator_id']]);
            $v['login_name'] = $operatorInfo['login_name'] ?? '?';
        }
        $result['filter'] = $filter;
        return $this->response->array($result);
    }

    /**
     * /supplier/get_supplier_info
     */ 
    public function getSupplierInfo(Request $request)
    {
        $params = $request->all();
        $auth = app('auth')->user()->get();
        $params['company_id'] = $auth['company_id'];
        $params['operator_id'] = $auth['operator_id'];
        
        $filter = [
            'company_id' => $params['company_id'],
            'operator_id' => $params['operator_id'],
        ];        
        $supplierService = new SupplierService();
        $result = $supplierService->repository->getInfo($filter);
        if (!$result) {
            $result = [
                'id' => '',
                'supplier_name' => '',
                'contact' => '',
                'mobile' => '',
                'business_license' => '',
                'wechat_qrcode' => '',
                'service_tel' => '',
                'bank_name' => '',
                'bank_account' => '',
            ];
        }
        return $this->response->array(['supplier_info' => $result, 'filter' => $filter]);        
    }

    /**
     * 审核供应商的资料
     * /supplier/check_supplier
     */
    public function checkSupplier(Request $request)
    {
        $auth = app('auth')->user()->get();
        $company_id = $auth['company_id'];
        $shop_id = intval($request->input('id', 0));
        $is_check = intval($request->input('is_check', 0));
        $audit_remark = trim($request->input('audit_remark', ''));
        $wx_openid = trim($request->input('wx_openid', ''));
        if (!$is_check) {
            throw new ResourceException(trans('SupplierBundle.please_select_audit_result'));
        }

        $supplierService = new SupplierService();
        if ($is_check == 2 && !$audit_remark) {
            throw new ResourceException(trans('SupplierBundle.please_input_audit_remarks'));
        }

        $filter = [
            'company_id' => $company_id,
            'id' => $shop_id,
            // 'is_check' => $supplierShopService::WAIT_CHECK,
        ];
        $saveData = [
            'is_check' => $is_check,
            'audit_remark' => $audit_remark,
            'wx_openid' => $wx_openid,
        ];
        $result = $supplierService->repository->updateOneBy($filter, $saveData);
        return $this->response->array($result);
    }
    
}
