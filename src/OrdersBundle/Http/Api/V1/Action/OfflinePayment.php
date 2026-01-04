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

namespace OrdersBundle\Http\Api\V1\Action;

use AftersalesBundle\Services\AftersalesRefundService;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use MembersBundle\Services\MemberService;
use OrdersBundle\Services\DeliveryProcessLogServices;
use OrdersBundle\Services\OfflinePaymentService;
use OrdersBundle\Services\OrderDeliveryService;

class OfflinePayment extends Controller
{
    private function __getFilter($params, &$filter = []) 
    {
        if (isset($params['check_status']) && $params['check_status'] !== '') {
            $filter['check_status'] = intval($params['check_status']);
        }
        foreach ($params as $key => $value) {
            if (!$value) continue;
            switch ($key) {
                case 'order_id':
                    $filter['order_id'] = $value;
                    break;
                case 'begin_date':
                    $value = strtotime($value);
                    if ($value) {
                        $filter['create_time|gte'] = $value;
                    }
                    break;
                case 'end_date':
                    $value = strtotime($value);
                    if ($value) {
                        $filter['create_time|lte'] = $value;
                    }
                    break;
                case 'user_mobile':
                    $memberService = new MemberService();
                    $userInfo = $memberService->getInfoByMobile($filter['company_id'], $value);
                    $filter['user_id'] = 0;
                    if ($userInfo) {
                        $filter['user_id'] = $userInfo['user_id'];
                    }
                    break;
                case 'pay_sn':
                    $filter['pay_sn'] = $value;
                    break;
                case 'pay_account_no':
                    $filter['pay_account_no'] = $value;
                    break;
                case 'pay_account_bank':
                    $filter['pay_account_bank'] = $value;
                    break;
                case 'bank_account_name':
                    $filter['bank_account_name'] = $value;
                    break;
            }
        }
    }
    
    /**
     * path="/order/offline_payment/get_list",
     */
    public function getList(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $operator_id = app('auth')->user()->get('operator_id');
        $page = intval($request->input('page', 1));
        $pageSize = intval($request->input('pageSize', 20));
        $params = $request->all();

        $orderBy = ['id' => 'DESC'];
        $filter = ['company_id' => $company_id];
        $this->__getFilter($params, $filter);
        $offlinePaymentService = new OfflinePaymentService();
        $result = $offlinePaymentService->repository->lists($filter, '*', $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * path="/order/offline_payment/get_info",
     * @SWG\Get(
     *     path="/order/offline_payment/get_info",
     *     tags={"线下转账"},
     *     summary="线下转账详情",
     *     @SWG\Parameter(name="id", in="query", description="线下转账ID", required=true, type="integer"),  
     *     @SWG\Response(response=200, description="成功返回结构", @SWG\Schema(type="object", @SWG\Property(property="status", type="string", description="状态"))),
     * )
     */
    public function getInfo(Request $request)
    {
        $params = $request->all();
        $rules = [
            'id' => ['required', 'ID缺少！'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $offlinePaymentService = new OfflinePaymentService();
        $result = $offlinePaymentService->getDetail($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/order/offline_payment/do_check",
     *     tags={"线下转账"},
     *     summary="线下转账审核",
     *     @SWG\Parameter(name="id", in="formData", description="线下转账ID", required=true, type="integer"),
     *     @SWG\Parameter(name="order_id", in="formData", description="订单ID", required=true, type="string"),
     *     @SWG\Parameter(name="check_status", in="formData", description="审核状态", required=true, type="integer"),
     *     @SWG\Parameter(name="remark", in="formData", description="审核备注", required=false, type="string"),
     *     @SWG\Response(response=200, description="成功返回结构", @SWG\Schema(type="object", @SWG\Property(property="status", type="string", description="状态"))),
     * )
     */
    public function doCheck(Request $request)
    {        
        $params = $request->all();
        $params['id'] = intval($params['id'] ?? 0);
        $params['order_id'] = trim($params['order_id'] ?? 0);
        $params['check_status'] = intval($params['check_status'] ?? 0);
        $params['remark'] = trim($params['remark'] ?? '');
        $authInfo = app('auth')->user();
        $params['company_id'] = $authInfo->get('company_id');
        $params['operator_name'] = $authInfo->get('mobile');
        if (!in_array($params['check_status'], [1, 2])) {
            throw new ResourceException('审核状态错误');
        }     
        if (!$params['id'] or !$params['order_id']) {
            throw new ResourceException('审核参数错误');
        }
        if ($params['check_status'] == 2) {
            if (!$params['remark']) {
                throw new ResourceException('请输入审核说明');
            }
            $params['remark'] = mb_substr($params['remark'], 0, 500);
        }
        $needParams = [
            'bank_account_id' => '收款账户id',
            // 'bank_account_name' => '收款账户名',
            // 'bank_name' => '收款银行名称',
            // 'bank_account_no' => '收款银行账号',
            // 'china_ums_no' => '收款银联号',
        ];
        foreach ($needParams as $key => $label) {
            if (!isset($params[$key]) or !$params[$key]) {
                throw new ResourceException('请输入' . $label);
            }
        }
        if (!is_numeric($params['pay_fee'])) throw new ResourceException('转账金额必须是数字');

        $redis = app('redis')->connection('companys');
        $redisKey = 'offline_pay_check:' . $params['id'];
        if ($redis->get($redisKey)) {
            throw new ResourceException('系统繁忙，请稍后再试');
        }
        $redis->set($redisKey, 1, 'EX', 3);
        
        $offlinePaymentService = new OfflinePaymentService();
        $operator = [
            'operator_id' => $authInfo->get('operator_id'),
            'operator_type' => $authInfo->get('operator_type'),
        ];
        $result = $offlinePaymentService->doCheck($params, $operator);
        $redis->set($redisKey, 0, 'EX', 3);
        
        return $this->response->array(['result' => $result]);
    }

    /**
     * path="/order/offline_payment/export_data",
     */
    public function exportData(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $params = $request->all();

        $orderBy = ['id' => 'DESC'];
        $filter = ['company_id' => $company_id];
        $this->__getFilter($params, $filter);

        $offlinePaymentService = new OfflinePaymentService();
        $count = $offlinePaymentService->repository->count($filter);
        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }
        if ($count > 15000) {
            throw new resourceexception('导出有误，最高导出15000条数据');
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

        $gotoJob = (new ExportFileJob('offline_payment', $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

}
