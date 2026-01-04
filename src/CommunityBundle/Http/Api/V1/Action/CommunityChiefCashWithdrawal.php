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

namespace CommunityBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use CommunityBundle\Services\CommunityChiefCashWithdrawalService;
use CommunityBundle\Services\CommunityChiefService;
use Dingo\Api\Exception\ResourceException;

class CommunityChiefCashWithdrawal extends Controller
{
    /**
     * @SWG\Get(
     *     path="/community/rebate/count",
     *     summary="团长业绩列表",
     *     tags={"社区团管理端"},
     *     description="团长业绩列表",
     *     operationId="getChiefRebateCount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="chief_name", in="query", description="团长姓名", required=false, type="string"),
     *     @SWG\Parameter( name="chief_mobile", in="query", description="团长手机号", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="chief_id", type="string", description="团长id"),
     *                           @SWG\Property(property="chief_name", type="string", description="团长姓名"),
     *                           @SWG\Property(property="chief_mobile", type="string", description="团长手机号"),
     *                           @SWG\Property(property="cash_withdrawal_rebate", type="string", description="可提现"),
     *                           @SWG\Property(property="payed_rebate", type="string", description="已提现"),
     *                           @SWG\Property(property="freeze_cash_withdrawal_rebate", type="string", description="申请提现"),
     *                           @SWG\Property(property="no_close_rebate", type="integer", description="未结算"),
     *                           @SWG\Property(property="rebate_total", type="string", description="佣金总额"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getChiefRebateCount(Request $request)
    {
        $params = $request->all('pageSize', 'page', 'chief_name', 'chief_mobile');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:100','每页最多查询100条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');

        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }

        $filter['company_id'] = $companyId;
        if (isset($params['chief_name']) && trim($params['chief_name'])) {
            $filter['chief_name'] = trim($params['chief_name']);
        }
        if (isset($params['chief_mobile']) && trim($params['chief_mobile'])) {
            $filter['chief_mobile'] = trim($params['chief_mobile']);
        }

        $chiefService = new CommunityChiefService();
        $result = $chiefService->getListByDistributorId($distributorId, $filter, $params['page'], $params['pageSize'], ['created_at' => 'DESC']);
        if (!$result['list']) {
            return $this->response->array($result);
        }

        $chiefIds = array_column($result['list'], 'chief_id');

        $cashWithdrawalService = new CommunityChiefCashWithdrawalService();
        $chiefRebate = $cashWithdrawalService->getChiefRebateCount($companyId, $chiefIds);

        foreach ($result['list'] as &$row) {
            $row['cash_withdrawal_rebate'] = 0; //可提现
            $row['payed_rebate'] = 0; //已提现
            $row['freeze_cash_withdrawal_rebate'] = 0; //申请提现
            $row['no_close_rebate'] = 0; //未结算
            $row['rebate_total'] = 0; //佣金总额

            if (isset($chiefRebate[$row['chief_id']])) {
                $row['cash_withdrawal_rebate'] = $chiefRebate[$row['chief_id']]['cash_withdrawal_rebate'] ?? 0;
                $row['payed_rebate'] = $chiefRebate[$row['chief_id']]['payed_rebate'] ?? 0;
                $row['freeze_cash_withdrawal_rebate'] = $chiefRebate[$row['chief_id']]['freeze_cash_withdrawal_rebate'] ?? 0;
                $row['no_close_rebate'] = $chiefRebate[$row['chief_id']]['no_close_rebate'] ?? 0;
                $row['rebate_total'] = $chiefRebate[$row['chief_id']]['rebate_total'];
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/community/cash_withdrawal/{cash_withdrawal_id}",
     *     summary="处理团长佣金提现申请",
     *     tags={"社区团管理端"},
     *     description="处理团长佣金提现申请 cash_withdrawal_id 为申请提现id",
     *     operationId="processCashWithdrawal",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="process_type", in="query", description="处理类型(reject 拒绝 argee 同意)", required=true, type="string"),
     *     @SWG\Parameter( name="remarks", in="query", description="拒绝描述", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function processCashWithdrawal($cash_withdrawal_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $cashWithdrawalService = new CommunityChiefCashWithdrawalService();
        $processType = $request->input('process_type');
        if ($processType == 'argee') {
            $status = $cashWithdrawalService->processCashWithdrawal($companyId, $cash_withdrawal_id);
        } elseif ($processType == 'reject') {
            $remarks = $request->input('remarks', null);
            $status = $cashWithdrawalService->rejectCashWithdrawal($companyId, $cash_withdrawal_id, $remarks);
        } else {
            throw new ResourceException('参数错误');
        }

        return $this->response->array(['status' => $status]);
    }

    /**
     * @SWG\Get(
     *     path="/community/cash_withdrawal",
     *     summary="获取佣金提现列表",
     *     tags={"社区团管理端"},
     *     description="获取佣金提现列表",
     *     operationId="getCashWithdrawalList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="提现状态:apply->待处理 reject->拒绝 success->提现成功 process->处理中 failed->提现失败", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="26", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="41", description=""),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="user_id", type="string", example="20261", description="推广员userId"),
     *                           @SWG\Property(property="pay_account", type="string", example="oHxgH0TeytApG70umvdz0mNGO69A", description="提现账号 微信为openid 支付宝为，支付账号"),
     *                           @SWG\Property(property="account_name", type="string", example="石头剪了布", description="提现账号姓名"),
     *                           @SWG\Property(property="mobile", type="string", example="15121097923", description="手机号"),
     *                           @SWG\Property(property="money", type="integer", example="1000", description="提现金额，以分为单位"),
     *                           @SWG\Property(property="status", type="string", example="reject", description="提现状态:apply->待处理 reject->拒绝 success->提现成功 process->处理中 failed->提现失败"),
     *                           @SWG\Property(property="remarks", type="string", example="q", description="备注"),
     *                           @SWG\Property(property="pay_type", type="string", example="wechat", description="提现支付类型"),
     *                           @SWG\Property(property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="提现的小程序appid"),
     *                           @SWG\Property(property="created", type="integer", example="1608030099", description=""),
     *                           @SWG\Property(property="updated", type="integer", example="1608030401", description=""),
     *                           @SWG\Property(property="created_date", type="string", example="2020-12-15 19:01:39", description=""),
     *                 ),
     *               ),
     *              @SWG\Property(property="count", type="object", description="",
     *                   @SWG\Property(property="payed_rebate", type="string", description="已提现总额"),
     *                   @SWG\Property(property="freeze_cash_withdrawal_rebate", type="string", description="待处理金额"),
     *                   @SWG\Property(property="rebate_total", type="string", description="佣金总额"),
     *                   @SWG\Property(property="apply_chief_num", type="string", description="佣金总额"),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getCashWithdrawalList(Request $request)
    {
        $params = $request->all('pageSize', 'page', 'mobile', 'status');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:100','每页最多查询100条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');

        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }

        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $distributorId;
        if (isset($params['mobile']) && trim($params['mobile'])) {
            $filter['mobile'] = trim($params['mobile']);
        }
        if (isset($params['status']) && trim($params['status'])) {
            $filter['status'] = trim($params['status']);
        }

        $cashWithdrawalService = new CommunityChiefCashWithdrawalService();
        $data = $cashWithdrawalService->lists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);

        if ($data['list']) {
            $chiefIds = array_column($data['list'], 'chief_id');
            $chiefRebate = $cashWithdrawalService->getChiefRebateCount($companyId, $chiefIds);
        }

        $datapassBlock = $request->get('x-datapass-block');
        foreach ($data['list'] as $key => $value) {
            if ($datapassBlock) {
                $data['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
            }

            $data['list'][$key]['cash_withdrawal_rebate'] = 0;
            if (isset($chiefRebate[$value['chief_id']])) {
                $data['list'][$key]['cash_withdrawal_rebate'] = $chiefRebate[$value['chief_id']]['cash_withdrawal_rebate'] ?? 0;
            }
        }
        $data['count'] = $cashWithdrawalService->cashWithdrawalCount($companyId, $distributorId);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/community/cash_withdrawal/payinfo/{cash_withdrawal_id}",
     *     summary="获取佣金提现支付信息",
     *     tags={"社区团管理端"},
     *     description="获取佣金提现支付信息",
     *     operationId="getMerchantTradeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="1", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="merchant_trade_id", type="string", example="3161773000010258", description="商家支付交易单号"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="rel_scene_id", type="string", example="39", description="关联支付场景ID"),
     *                           @SWG\Property(property="rel_scene_name", type="string", example="popularize_rebate_cash_withdrawal", description="关联支付场景名称"),
     *                           @SWG\Property(property="mch_appid", type="string", example="wx912913df9fef6ddd", description="商户APPId"),
     *                           @SWG\Property(property="mchid", type="string", example="1313844301", description="商户号"),
     *                           @SWG\Property(property="payment_action", type="string", example="WECHAT", description="商户支付方式"),
     *                           @SWG\Property(property="check_name", type="string", example="NO_CHECK", description="是否强验用户姓名"),
     *                           @SWG\Property(property="mobile", type="string", example="", description="支付手机号"),
     *                           @SWG\Property(property="re_user_name", type="string", example="", description="收款用户姓名"),
     *                           @SWG\Property(property="user_id", type="string", example="20258", description="用户id"),
     *                           @SWG\Property(property="open_id", type="string", example="oHxgH0c4Iu9237ygJixVFCVUGaAs", description="用户openId"),
     *                           @SWG\Property(property="amount", type="string", example="100", description="付款金额(分)"),
     *                           @SWG\Property(property="payment_desc", type="string", example="佣金提现", description="付款备注"),
     *                           @SWG\Property(property="spbill_create_ip", type="string", example="127.0.0.1", description="ip地址"),
     *                           @SWG\Property(property="status", type="string", example="SUCCESS", description="支付状态。可选值有 NOT_PAY:未付款;PAYING:付款中;SUCCESS:付款成功;FAIL:付款失败"),
     *                           @SWG\Property(property="payment_no", type="string", example="10100113827902008275907296370285", description="微信支付订单号"),
     *                           @SWG\Property(property="payment_time", type="string", example="2020-08-27 19:19:40", description="微信支付成功时间"),
     *                           @SWG\Property(property="error_code", type="string", example="", description="支付错误code码"),
     *                           @SWG\Property(property="error_desc", type="string", example="", description="支付错误描述"),
     *                           @SWG\Property(property="create_time", type="integer", example="1598527179", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="integer", example="1598527180", description="订单更新时间"),
     *                           @SWG\Property(property="cur_pay_fee", type="string", example="100", description="系统货币支付金额"),
     *                           @SWG\Property(property="cur_fee_symbol", type="string", example="￥", description="系统配置货币符号"),
     *                           @SWG\Property(property="cur_fee_rate", type="integer", example="1", description="系统配置货币汇率"),
     *                           @SWG\Property(property="cur_fee_type", type="string", example="CNY", description="系统配置货币类型"),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="hf_order_id", type="string", example="", description="汇付请求订单号"),
     *                           @SWG\Property(property="hf_order_date", type="string", example="", description="汇付请求日期"),
     *                           @SWG\Property(property="hf_cash_type", type="string", example="", description="汇付取现方式 T0：T0取现; T1：T1取现 D1：D1取现"),
     *                           @SWG\Property(property="user_cust_id", type="string", example="", description="汇付商户客户号"),
     *                           @SWG\Property(property="bind_card_id", type="string", example="", description="汇付取现银行卡id"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getMerchantTradeList($cash_withdrawal_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $cashWithdrawalService = new CommunityChiefCashWithdrawalService();
        $data = $cashWithdrawalService->getMerchantTradeList($companyId, $cash_withdrawal_id);

        return $this->response->array($data);
    }
}
