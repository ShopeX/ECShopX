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

namespace DistributionBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use DistributionBundle\Services\CashWithdrawalService;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use PopularizeBundle\Services\PromoterService;

class CashWithdrawal extends Controller
{
    /**
     * @SWG\Put(
     *     path="/distribution/cash_withdrawals/{id}",
     *     summary="处理佣金提现申请",
     *     tags={"店铺"},
     *     description="处理佣金提现申请",
     *     operationId="processCashWithdrawal",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="提现id", required=true, type="string"),
     *     @SWG\Parameter( name="process_type", in="query", description="处理类型(reject 拒绝 argee 同意)", required=true, type="string"),
     *     @SWG\Parameter( name="remarks", in="query", description="拒绝描述", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function processCashWithdrawal($id, Request $request)
    {
        $processType = $request->input('process_type');
        $companyId = app('auth')->user()->get('company_id');

        $cashWithdrawalService = new CashWithdrawalService();
        if ($processType == 'argee') {
            $status = $cashWithdrawalService->processCashWithdrawal($companyId, $id);
        } elseif ($processType == 'reject') {
            $remarks = $request->input('remarks', null);
            $status = $cashWithdrawalService->rejectCashWithdrawal($companyId, $id, $processType, $remarks);
        } elseif ($processType == 'success') {
            $remarks = $request->input('remarks', null);
            $status = $cashWithdrawalService->successCashWithdrawal($companyId, $id, $processType, $remarks);
        } else {
            throw new ResourceException(trans('DistributionBundle/Controllers/CashWithdrawal.params_error'));
        }

        return $this->response->array(['status' => $status]);
    }

    /**
     * @SWG\Get(
     *     path="/distribution/cashWithdrawals",
     *     summary="获取佣金提现列表",
     *     tags={"店铺"},
     *     description="获取佣金提现列表",
     *     operationId="getCashWithdrawalList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="list", type="stirng"),
     *                     @SWG\Property(property="total_count", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getCashWithdrawalList(Request $request)
    {
        $params = $request->all('pageSize', 'page');
        $distributor_id = $request->input('distributor_id',0);

        $rules = [
            'page' => ['required|integer|min:1', trans('DistributionBundle/Controllers/CashWithdrawal.page_error')],
            'pageSize' => ['required|integer|min:1|max:50', trans('DistributionBundle/Controllers/CashWithdrawal.pagesize_max')],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        if ($request->input('mobile', false)) {
            $filter['distributor_mobile'] = $request->input('mobile');
        }
        if ($request->input('status', false)) {
            $filter['status'] = $request->input('status');
        }

        if($distributor_id){
            $filter['distributor_id'] = $distributor_id;
        }
        $merchantId = app('auth')->user()->get('merchant_id',0);
        $operatorType = app('auth')->user()->get('operator_type','');
        if ($operatorType == 'merchant') {
            $filter_distributor = array();
            $filter_distributor['company_id'] = $companyId; // 商户端只能获取商户的店铺
            $filter_distributor['merchant_id'] = $merchantId; // 商户端只能获取商户的店铺
            $distributorService = new DistributorService();
            $distributorList =  $distributorService->lists($filter_distributor,['created' => 'desc'],10000,1,false,"distributor_id");
            $shopIds = array_column($distributorList['list'],'distributor_id');
            $filter['distributor_id'] = $shopIds;

        }
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":filter:". json_encode($filter));

        $cashWithdrawalService = new CashWithdrawalService();
        $data = $cashWithdrawalService->lists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);

        $promoterUserIds = array_column($data['list'],'user_id');
        $promoterService = new PromoterService();
        $filter_p = array();
        $filter_p['company_id'] = $companyId; // 商户端只能获取商户的店铺
        $filter_p['user_id'] = $promoterUserIds; // 商户端只能获取商户的推广员

        if($promoterUserIds){
            $data_promoter = $promoterService->getLists($filter_p);
            if( isset($data_promoter['list']) &&  $data_promoter['list']) $promoterInfo = array_column($data_promoter['list'],null,'user_id');
            foreach($data['list'] as $k => &$v){
                $v['alipay_name'] = $promoterInfo[$v['user_id']]['alipay_name'] ?? '';
                $v['alipay_account'] = $promoterInfo[$v['user_id']]['alipay_account'] ?? '';
            }
    
    
        }
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":filter:". json_encode($filter));
    
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/distribution/cashWithdrawal/payinfo/{cash_withdrawal_id}",
     *     summary="获取佣金提现支付信息",
     *     tags={"店铺"},
     *     description="获取佣金提现支付信息",
     *     operationId="getMerchantTradeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="list", type="stirng"),
     *                     @SWG\Property(property="total_count", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getMerchantTradeList($cash_withdrawal_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $cashWithdrawalService = new CashWithdrawalService();
        $data = $cashWithdrawalService->getMerchantTradeList($companyId, $cash_withdrawal_id);

        return $this->response->array($data);
    }
}
