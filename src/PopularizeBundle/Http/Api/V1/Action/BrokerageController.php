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

namespace PopularizeBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PopularizeBundle\Services\BrokerageService;
use PopularizeBundle\Services\TaskBrokerageService;
use PopularizeBundle\Services\PromoterCountService;
use PopularizeBundle\Services\CashWithdrawalService;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;
use EspierBundle\Traits\GetExportServiceTraits;

class BrokerageController extends Controller
{
    use GetExportServiceTraits;

    /**
     * @SWG\Put(
     *     path="/popularize/cash_withdrawals/{cash_withdrawal_id}",
     *     summary="处理推广员佣金提现申请",
     *     tags={"分销推广"},
     *     description="处理推广员佣金提现申请 cash_withdrawal_id 为申请提现id",
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function processCashWithdrawal($cash_withdrawal_id, Request $request)
    {
        // ShopEx EcShopX Service Component
        $processType = $request->input('process_type');
        $companyId = app('auth')->user()->get('company_id');

        $cashWithdrawalService = new CashWithdrawalService();
        if ($processType == 'argee') {
            $status = $cashWithdrawalService->processCashWithdrawal($companyId, $cash_withdrawal_id);
        } elseif ($processType == 'reject') {
            $remarks = $request->input('remarks', null);
            $status = $cashWithdrawalService->rejectCashWithdrawal($companyId, $cash_withdrawal_id, $processType, $remarks);
        } else {
            throw new ResourceException('参数错误');
        }

        return $this->response->array(['status' => $status]);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/cashWithdrawals",
     *     summary="获取佣金提现列表",
     *     tags={"分销推广"},
     *     description="获取佣金提现列表",
     *     operationId="getCashWithdrawalList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
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
     *                           @SWG\Property(property="status", type="string", example="reject", description="提现状态"),
     *                           @SWG\Property(property="remarks", type="string", example="q", description="备注"),
     *                           @SWG\Property(property="pay_type", type="string", example="wechat", description="提现支付类型"),
     *                           @SWG\Property(property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="提现的小程序appid"),
     *                           @SWG\Property(property="created", type="integer", example="1608030099", description=""),
     *                           @SWG\Property(property="updated", type="integer", example="1608030401", description=""),
     *                           @SWG\Property(property="created_date", type="string", example="2020-12-15 19:01:39", description=""),
     *                 ),
     *               ),
     *              @SWG\Property(property="count", type="object", description="",
     *                   @SWG\Property(property="apply", type="integer", example="12200", description=""),
     *                   @SWG\Property(property="success", type="integer", example="15500", description=""),
     *                   @SWG\Property(property="userCount", type="integer", example="12", description=""),
     *                   @SWG\Property(property="all", type="string", example="234865", description=""),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getCashWithdrawalList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:100','每页最多查询100条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        if ($request->input('mobile', false)) {
            $filter['mobile'] = $request->input('mobile');
        }
        if ($request->input('status', false)) {
            $filter['status'] = $request->input('status');
        }

        $cashWithdrawalService = new CashWithdrawalService();
        $data = $cashWithdrawalService->lists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);
        $datapassBlock = $request->get('x-datapass-block');
        foreach ($data['list'] as $key => $value) {
            if ($datapassBlock) {
                $data['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
            }
        }
        $data['count'] = $cashWithdrawalService->cashWithdrawalCount($companyId);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/cashWithdrawal/payinfo/{cash_withdrawal_id}",
     *     summary="获取佣金提现支付信息",
     *     tags={"分销推广"},
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getMerchantTradeList($cash_withdrawal_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $cashWithdrawalService = new CashWithdrawalService();
        $data = $cashWithdrawalService->getMerchantTradeList($companyId, $cash_withdrawal_id);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/brokerage/count",
     *     summary="获取佣金统计",
     *     tags={"分销推广"},
     *     description="获取佣金统计",
     *     operationId="brokerageCount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="推广员user_id 如果传人", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="payedRebate", type="integer", example="0", description="已提现"),
     *               @SWG\Property(property="itemTotalPrice", type="integer", example="20", description="营业额"),
     *               @SWG\Property(property="cashWithdrawalRebate", type="integer", example="0", description="可提现金额"),
     *               @SWG\Property(property="noCloseRebate", type="integer", example="24", description="未结算金额"),
     *               @SWG\Property(property="rebateTotal", type="integer", example="24", description="推广费总金额"),
     *               @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="0", description="冻结金额"),
     *               @SWG\Property(property="pointTotal", type="integer", example="0", description="积分总额"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function brokerageCount(Request $request)
    {
        $promoterCountService = new PromoterCountService();

        $companyId = app('auth')->user()->get('company_id');

        $userId = $request->input('user_id');
        $distributor_id = $request->input('distributor_id', 0);
        if ($distributor_id) {
            $brokerageService = new BrokerageService();
            $filter['dIds']    = [$distributor_id]; 
            $filter['user_id'] = $userId; 
            $countDataShop = $brokerageService->getSalesmanBrokerageCount($filter, 1 ,1000);
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listBrokerage:". json_encode($distributor_id));
        }

        if ($userId) {
            $countData = $promoterCountService->getPromoterCount($companyId, $userId);
        } else {
            $countData = $promoterCountService->getCount($companyId);
        }
        
        if (!$distributor_id) {
            $countDataShop = [
                'rebate_sum_noclose' => $countData['noCloseRebate'] ?? 0,//未结算
                'price_sum' => $countData['itemTotalPrice'] ?? 0,//商品总额
                'rebate_sum' => $countData['rebateTotal'],//佣金总额
            ];
        }

        //已提现
        $data['payedRebate'] = $countData['payedRebate'];
        // 营业额
        $data['itemTotalPrice'] = $countData['itemTotalPrice'];
        // 可提现金额
        $data['cashWithdrawalRebate'] = $countData['cashWithdrawalRebate'];
        // 未结算金额
        $data['noCloseRebate'] = $countData['noCloseRebate'];
        // 推广费总金额
        $data['rebateTotal'] = $countData['rebateTotal'];
        // 冻结金额
        $data['freezeCashWithdrawalRebate'] = $countData['freezeCashWithdrawalRebate'];
        // 积分总额
        $data['pointTotal'] = $countData['pointTotal'] ?? 0;

        $data['countDataShop'] = $countDataShop ?? [];
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/brokerage/logs",
     *     summary="获取佣金记录",
     *     tags={"分销推广"},
     *     description="获取佣金记录",
     *     operationId="getBrokerageList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="推广员user_id，如果有值则返回指定推广员的记录", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="1673", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="列表数据",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="1719", description="ID"),
     *                           @SWG\Property(property="brokerage_type", type="string", example="first_level", description="佣金类型"),
     *                           @SWG\Property(property="order_id", type="string", example="3337701000270078", description="订单号"),
     *                           @SWG\Property(property="user_id", type="string", example="20261", description=""),
     *                           @SWG\Property(property="buy_user_id", type="string", example="20078", description=""),
     *                           @SWG\Property(property="source", type="string", example="order", description="佣金来源 订单,邀请等"),
     *                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="price", type="integer", example="1", description="销售金额,单位为‘分’"),
     *                           @SWG\Property(property="is_close", type="string", example="", description="是否已结算"),
     *                           @SWG\Property(property="plan_close_time", type="integer", example="1700127117", description="计划结算时间"),
     *                           @SWG\Property(property="plan_close_date", type="string", example="2023-11-16 17:31:57", description=""),
     *                           @SWG\Property(property="commission_type", type="string", example="money", description="返佣类型 money 金额 point 积分"),
     *                           @SWG\Property(property="rebate", type="integer", example="2", description="返佣金额,单位为‘分’"),
     *                           @SWG\Property(property="rebate_point", type="string", example="0", description="返佣金额,单位为‘分’"),
     *                           @SWG\Property(property="detail", type="object", description="",
     *                             @SWG\Property(property="ratio_type", type="string", example="order_money", description=""),
     *                             @SWG\Property(property="ratio", type="string", example="20", description=""),
     *                             @SWG\Property(property="total_fee", type="integer", example="1", description=""),
     *                             @SWG\Property(property="cost_fee", type="integer", example="0", description=""),
     *                             @SWG\Property(property="rebate_detail", type="string", description="",),
     *                           ),
     *                           @SWG\Property(property="created", type="integer", example="1613727117", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getBrokerageList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        if ($request->input('user_id', false)) {
            $filter['user_id'] = $request->input('user_id');
        }

        if ($request->input('is_close', false)) {
            $filter['is_close'] = $request->input('is_close') == 'true' ? true : false;
        }

        $brokerageService = new BrokerageService();
        if ($request->input('distributor_id', false)) {
            $distributor_id = $request->input('distributor_id', false);
            $filter['dIds'] = [$distributor_id]; 
            $data = $brokerageService->getSalesmanBrokeragelistsBySql($filter, $params['pageSize'] ,$params['page']);
        }else{
            $data = $brokerageService->getBrokerageDbList($filter, $params['page'], $params['pageSize']);
        }

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/taskBrokerage/logs",
     *     summary="获取任务制佣金记录",
     *     tags={"分销推广"},
     *     description="获取任务制佣金记录",
     *     operationId="getTaskBrokerageList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="推广员user_id，如果有值则返回指定推广员的记录", required=false, type="string"),
     *     @SWG\Parameter( name="plan_date", in="query", description="账期时间", required=false, type="string"),
     *     @SWG\Parameter( name="time_start", in="query", description="查询开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="time_end", in="query", description="查询结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="10", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="10", description="ID"),
     *                           @SWG\Property(property="item_id", type="string", example="5012", description="商品id"),
     *                           @SWG\Property(property="user_id", type="string", example="20261", description="会员id"),
     *                           @SWG\Property(property="order_id", type="string", example="3162918000120263", description="订单号"),
     *                           @SWG\Property(property="buy_user_id", type="string", example="20263", description=""),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="item_name", type="string", example="花花", description="商品名称"),
     *                           @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                           @SWG\Property(property="price", type="string", example="10", description="价格"),
     *                           @SWG\Property(property="num", type="string", example="1", description="销售数量"),
     *                           @SWG\Property(property="status", type="string", example="close", description="状态"),
     *                           @SWG\Property(property="plan_date", type="string", example="2020-08-31", description="计划结算时间"),
     *                           @SWG\Property(property="created", type="string", example="1598626981", description=""),
     *                           @SWG\Property(property="updated", type="string", example="1598626981", description=""),
     *                           @SWG\Property(property="promoter_mobile", type="string", example="15121097923", description=""),
     *                           @SWG\Property(property="buy_mobile", type="string", example="", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getTaskBrokerageList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        if ($request->input('promoter_mobile', false)) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile(trim($request->input('promoter_mobile')), $companyId);
            if ($userId) {
                $filter['user_id'] = $userId;
            } else {
                $data = [
                    'total_count' => 0,
                    'list' => [],
                ];
                return $this->response->array($data);
            }
        }

        if ($request->input('order_id', false)) {
            $filter['order_id'] = $request->input('order_id');
        }

        if ($request->input('item_name', false)) {
            $filter['item_name|contains'] = trim($request->input('item_name'));
        }

        if ($request->input('status', false)) {
            $filter['status'] = $request->input('status');
        }

        if ($request->input('time_start', null) && $request->input('time_end', null)) {
            $filter['updated|gte'] = strtotime(date('Y-m-d 00:00:00', $request->input('time_start')));
            $filter['updated|lte'] = strtotime(date('Y-m-d 23:59:59', $request->input('time_end')));
        }

        if ($request->input('plan_date', false)) {
            $filter['plan_date'] = date('Y-m-t', strtotime($request->input('plan_date')));
        }

        $taskBrokerageService = new TaskBrokerageService();
        $data = $taskBrokerageService->getTaskBrokerageList($filter, '*', $params['page'], $params['pageSize'], ['created' => 'desc']);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        foreach ($data['list'] as $key => $value) {
            if ($datapassBlock) {
                $data['list'][$key]['promoter_mobile'] = data_masking('mobile', (string) $value['promoter_mobile']);
                $data['list'][$key]['buy_mobile'] = data_masking('mobile', (string) $value['buy_mobile']);
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/taskBrokerage/count",
     *     summary="获取任务制佣金统计",
     *     tags={"分销推广"},
     *     description="获取任务制佣金统计",
     *     operationId="getTaskBrokerageCountList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="推广员user_id，如果有值则返回指定推广员的记录", required=false, type="string"),
     *     @SWG\Parameter( name="time_start", in="query", description="查询开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="time_end", in="query", description="查询结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="plan_date", in="query", description="账期时间", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="5", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="5", description="ID"),
     *                           @SWG\Property(property="rebate_type", type="string", example="total_money", description="返佣模式"),
     *                           @SWG\Property(property="item_id", type="string", example="5012", description="商品id"),
     *                           @SWG\Property(property="total_fee", type="string", example="0", description="已完成的总销售额"),
     *                           @SWG\Property(property="item_name", type="string", example="花花", description="商品名称"),
     *                           @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                           @SWG\Property(property="user_id", type="string", example="20261", description=""),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="rebate_conf", type="object", description="",
     *                                           @SWG\Property(property="type", type="string", example="money", description=""),
     *                                           @SWG\Property(property="value", type="object", description="",
     *                                                @SWG\Property(property="first_level", type="string", example="", description=""),
     *                                                @SWG\Property(property="second_level", type="string", example="", description=""),
     *                                           ),
     *                                           @SWG\Property(property="ratio_type", type="string", example="order_money", description=""),
     *                                           @SWG\Property(property="rebate_task", type="array", description="",
     *                                             @SWG\Items(
     *                                                       @SWG\Property(property="money", type="string", example="10", description=""),
     *                                                       @SWG\Property(property="ratio", type="string", example="", description=""),
     *                                                       @SWG\Property(property="filter", type="string", example="100", description=""),
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="rebate_task_type", type="string", example="money", description=""),
     *                          ),
     *                           @SWG\Property(property="finish_num", type="string", example="0", description="订单已完成数量"),
     *                           @SWG\Property(property="wait_num", type="string", example="0", description="订单已支付，待完成数量"),
     *                           @SWG\Property(property="close_num", type="string", example="1", description="订单已关闭数量，包含取消订单，售后订单"),
     *                           @SWG\Property(property="plan_date", type="string", example="2020-08-31", description="计划结算时间"),
     *                           @SWG\Property(property="created", type="string", example="1598626981", description=""),
     *                           @SWG\Property(property="updated", type="string", example="1598626981", description=""),
     *                           @SWG\Property(property="rebate_money", type="integer", example="0", description="分销奖金"),
     *                           @SWG\Property(property="item_bn", type="string", example="S5F36451C1FE4E", description="商品编号"),
     *                           @SWG\Property(property="status", type="integer", example="0", description=""),
     *                           @SWG\Property(property="promoter_mobile", type="string", example="15121097923", description=""),
     *                           @SWG\Property(property="limit_desc", type="string", example="还差 100 元达标", description=""),
     *                           @SWG\Property(property="orders", type="string", example="", description="订单信息"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getTaskBrokerageCountList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        if ($request->input('promoter_mobile', false)) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile(trim($request->input('promoter_mobile')), $companyId);
            if ($userId) {
                $filter['user_id'] = $userId;
            } else {
                $data = [
                    'total_count' => 0,
                    'list' => [],
                ];
                return $this->response->array($data);
            }
        }

        if ($request->input('item_name', false)) {
            $filter['item_name|contains'] = trim($request->input('item_name'));
        }

        if ($request->input('time_start', null) && $request->input('time_end', null)) {
            $filter['updated|gte'] = strtotime(date('Y-m-d 00:00:00', $request->input('time_start')));
            $filter['updated|lte'] = strtotime(date('Y-m-d 23:59:59', $request->input('time_end')));
        }

        if ($request->input('plan_date', false)) {
            $filter['plan_date'] = date('Y-m-t', strtotime($request->input('plan_date')));
        }

        $taskBrokerageService = new TaskBrokerageService();
        $data = $taskBrokerageService->getTaskBrokerageCountList($filter, '*', $params['page'], $params['pageSize'], ['created' => 'desc']);
        if ($data['list']) {
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            foreach ($data['list'] as $key => $value) {
                if ($datapassBlock) {
                    $data['list'][$key]['promoter_mobile'] = data_masking('mobile', (string) $value['promoter_mobile']);
                }
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/export/taskBrokerage/count",
     *     summary="导出任务奖金统计",
     *     tags={"分销推广"},
     *     description="导出任务奖金统计",
     *     operationId="exportTaskBrokerageCount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="推广员user_id，如果有值则返回指定推广员的记录", required=false, type="string"),
     *     @SWG\Parameter( name="time_start", in="query", description="查询开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="time_end", in="query", description="查询结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="plan_date", in="query", description="账期时间", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=false, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function exportTaskBrokerageCount(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        if ($request->input('promoter_mobile', false)) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile(trim($request->input('promoter_mobile')), $companyId);
            if ($userId) {
                $filter['user_id'] = $userId;
            } else {
                $data = [
                    'total_count' => 0,
                    'list' => [],
                ];
                return $this->response->array($data);
            }
        }

        if ($request->input('item_name', false)) {
            $filter['item_name|contains'] = trim($request->input('item_name'));
        }

        if ($request->input('time_start', null) && $request->input('time_end', null)) {
            $filter['updated|gte'] = strtotime(date('Y-m-d 00:00:00', $request->input('time_start')));
            $filter['updated|lte'] = strtotime(date('Y-m-d 23:59:59', $request->input('time_end')));
        }

        if ($request->input('plan_date', false)) {
            $filter['plan_date'] = date('Y-m-t', strtotime($request->input('plan_date')));
        }

        $exportService = $this->getService('task_brokerage_count');
        // 是否有权限查看加密数据
        $filter['datapass_block'] = $request->get('x-datapass-block');
        $result = $exportService->exportData($filter);
        return response()->json($result);
    }
}
