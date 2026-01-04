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

use BsPayBundle\Services\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use BsPayBundle\Services\BspayTradeService;

class Trade extends Controller
{
    /**
     * @SWG\Get(
     *     path="/bspay/trade/list",
     *     summary="获取交易列表",
     *     tags={"汇付斗拱"},
     *     description="获取斗拱交易列表",
     *     operationId="getTradelist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=false, type="string"),
     *     @SWG\Parameter( name="trade_id", in="query", description="交易单号", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="distributor_name", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="pay_channel", in="query", description="wx_lite 微信小程序支付", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="结束时间", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="交易状态: SUCCESS—支付完成;PARTIAL_REFUND—部分退款;FULL_REFUND—全额退款;", type="string"),
     *     @SWG\Parameter( name="adapay_div_status", in="query", description="分账状态:NOTDIV — 未分账;DIVED - 已分账", type="string"),
     *     @SWG\Parameter( name="adapay_fee_mode", in="query", description="手续费扣费方式", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="9543", description="总记录条数"),
     *               @SWG\Property(property="total", type="array", description="总计",
     *                   @SWG\Items(
     *                        @SWG\Property(property="totalFee", type="integer", example="0", description="交易总额"),
     *                        @SWG\Property(property="adapayFee", type="integer", example="0", description="手续费"),
     *                        @SWG\Property(property="divFee", type="string", example="0", description="分账总额"),
     *                        @SWG\Property(property="payFee", type="integer", example="1", description="支付总额"),
     *                   ),
     *               ),
     *               @SWG\Property(property="list", type="array", description="数据集合",
     *                 @SWG\Items(
     *                           @SWG\Property(property="tradeId", type="string", example="3323616000180399", description="交易单号"),
     *                           @SWG\Property(property="orderId", type="string", example="3323616000160399", description="订单号"),
     *                           @SWG\Property(property="shopId", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="userId", type="string", example="20399", description="会员id"),
     *                           @SWG\Property(property="mobile", type="string", example="18530870713", description="购买用户手机号"),
     *                           @SWG\Property(property="openId", type="string", example="", description="openId"),
     *                           @SWG\Property(property="discountInfo", type="string", description="优惠信息"),
     *                           @SWG\Property(property="mchId", type="string", example="", description="支付账户"),
     *                           @SWG\Property(property="totalFee", type="integer", example="0", description="总金额"),
     *                           @SWG\Property(property="discountFee", type="integer", example="0", description="优惠金额"),
     *                           @SWG\Property(property="feeType", type="string", example="CNY", description="运费金额"),
     *                           @SWG\Property(property="payFee", type="integer", example="1", description="支付金额"),
     *                           @SWG\Property(property="refundedFee", type="integer", example="1", description="退款金额"),
     *                           @SWG\Property(property="tradeState", type="string", example="SUCCESS", description="交易状态: SUCCESS—支付完成;PARTIAL_REFUND—部分退款;FULL_REFUND—全额退款"),
     *                           @SWG\Property(property="payType", type="string", example="point", description="支付类型"),
     *                           @SWG\Property(property="transactionId", type="string", example="", description="支付渠道交易单号"),
     *                           @SWG\Property(property="wxaAppid", type="string", example="", description="微信appid"),
     *                           @SWG\Property(property="bankType", type="string", example="积分", description="银行类型"),
     *                           @SWG\Property(property="body", type="string", example="积分品牌测试1...", description="交易商品简单描述"),
     *                           @SWG\Property(property="detail", type="string", example="积分品牌测试1...", description="交易商品详情"),
     *                           @SWG\Property(property="timeStart", type="string", example="1612509901", description="交易开始时间"),
     *                           @SWG\Property(property="timeExpire", type="string", example="1612509901", description="交易截止时间"),
     *                           @SWG\Property(property="companyId", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="authorizerAppid", type="string", example="", description="authorizerAppid"),
     *                           @SWG\Property(property="curFeeType", type="string", example="CNY", description="系统配置货币类型"),
     *                           @SWG\Property(property="curFeeRate", type="integer", example="1", description="系统配置货币汇率"),
     *                           @SWG\Property(property="curFeeSymbol", type="string", example="￥", description="系统配置货币符号"),
     *                           @SWG\Property(property="curPayFee", type="integer", example="1", description="系统货币支付金额"),
     *                           @SWG\Property(property="distributorId", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="distributor_name", type="string", example="0", description="店铺名称"),
     *                           @SWG\Property(property="tradeSourceType", type="string", example="normal_pointsmall", description="交易单来源类型。可选值有 membercard-会员卡购买;normal-实体订单购买;servers-服务订单购买;normal_community-社区订单购买;diposit-预存款购买;order_pay-买单购买;"),
     *                           @SWG\Property(property="couponFee", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="couponInfo", type="string", example="", description="优惠券信息json结构"),
     *                           @SWG\Property(property="initalRequest", type="string", example="", description="统一下单原始请求json结构"),
     *                           @SWG\Property(property="initalResponse", type="string", example="", description="支付结果通知json结构"),
     *                           @SWG\Property(property="payDate", type="string", example="2021-02-05 15:25:01", description="支付时间"),
     *                           @SWG\Property(property="adapayFeeMode", type="string", example="2021-02-05 15:25:01", description="手续费扣费方式"),
     *                           @SWG\Property(property="adapayFee", type="integer", example="", description="分账手续费"),
     *                           @SWG\Property(property="divFee", type="integer", example="0", description="分账金额"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function getTradelist(Request $request)
    {
        $tradeService = new BsPayTradeService();
        $filter = array();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $user = app('auth')->user();
        if ($request->input('status')) {
            $filter['status'] = strtoupper($request->input('status'));
        }
        if ($request->input('can_div')) {
            $filter['can_div'] = $request->input('can_div') === 'true';
        }
        if ($request->input('bspay_fee_mode')) {
            $filter['bspay_fee_mode'] = strtoupper($request->input('bspay_fee_mode'));
        }
        if ($request->input('bspay_div_status')) {
            $filter['bspay_div_status'] = strtoupper($request->input('bspay_div_status'));
        }

        if ($request->input('pay_channel', false)) {
            $filter['pay_channel'] = $request->input('pay_channel');
        }

        if ($request->input('order_id', false)) {
            $filter['order_id'] = $request->input('order_id');
        }
        if ($request->input('trade_id', false)) {
            $filter['trade_id'] = $request->input('trade_id');
        }

        if ($request->input('time_start_begin')) {
            $filter['time_start|gte'] = substr($request->input('time_start_begin'), 0, 10);
            $filter['time_start|lte'] = substr($request->input('time_start_end'), 0, 10);
            $timeRange = 3 * 30 * 24 * 3600;
            if ($filter['time_start|lte'] - $filter['time_start|gte'] > $timeRange) {
                $filter['time_start|gte'] = $filter['time_start|lte'] - $timeRange;
            }
        }
        $trade_result = ['total' => ['totalFee' => 0, 'payFee' => 0, 'divFee' => 0, 'bspayFee' => 0], 'list' => [],'total_count' => 0];
        if ($user->get('operator_type') == 'distributor') { //店铺端
            $filter['distributor_id'] = $user->get('distributor_id');
            if (!$filter['distributor_id']) {
                return $this->response->array($trade_result);
            }
        } elseif ($user->get('operator_type') == 'merchant') { //商户
            $userService = new UserService();
            $operator = $userService->getOperator();
            $filter['merchant_id'] = $operator['operator_id'];
            if (!$filter['merchant_id']) {
                return $this->response->array($trade_result);
            }
        } elseif ($user->get('operator_type') == 'supplier') { //供应商
            $filter['supplier_id'] = $user->get('operator_id');
        }
        // elseif ($user->get('operator_type') == 'dealer') { //经销商端
        //     $userService = new UserService();
        //     $operator = $userService->getOperator();
        //     $filter['dealer_id'] = $operator['operator_id'];
        //     if (!$filter['dealer_id']) {
        //         return $this->response->array($trade_result);
        //     }
        // } 

        if ($request->get('distributor_name', 0)) { //主商户端/经销商端 根据店铺字段筛选
            $distributorFilter = ['name|contains' => $request->get('distributor_name')];
            $distributorFilter['company_id'] = $filter['company_id'];
            $distributors = $tradeService->getDistributors($distributorFilter);
            if (!$distributors) {
                return $this->response->array($trade_result);
            }
            $filter['distributor_id'] = array_column($distributors, 'distributor_id'); //覆盖distributor_id条件
        }
        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);
        $trade_result = $tradeService->getTradeList($filter, $pageSize, $page);
        return $this->response->array($trade_result);
    }


    /**
     * @SWG\Get(
     *     path="/bspay/distributor/list",
     *     summary="获取店铺列表",
     *     tags={"汇付斗拱"},
     *     description="获取店铺列表",
     *     operationId="getTradeInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="list", type="array", description="",
     *                          @SWG\Items(
     *                               @SWG\Property(property="distributor_id", type="string", example="0", description="店铺id"),
     *                               @SWG\Property(property="name", type="string", example="0", description="店铺名"),
     *
     *                          )
     *                     ),
     *                   )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    //获取店铺列表
    public function getDistributorList()
    {
        //商家端 and 经销商端 列表页
        $user = app('auth')->user();
        $operatorType = $user->get('operator_type');
        $list = [];
        if ($operatorType == 'dealer') { //经销商端
            $list = $user->get('distributor_ids');
        } elseif ($operatorType == 'admin') { //商家端
            $tradeService = new BspayTradeService();
            $list = $tradeService->getDistributors(['company_id' => $user->get('company_id')]);
        }
        return $this->response->array(['list' => $list]);
    }

    /**
     * @SWG\Get(
     *     path="/bspay/trade/info/{trade_id}",
     *     summary="获取斗拱交易单详情",
     *     tags={"汇付斗拱"},
     *     description="获取交易单详情",
     *     operationId="getTradeInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="trade_id", in="query", description="交易单号", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="tradeInfo", type="object",
     *                           @SWG\Property(property="trade_id", type="string", example="3323616000180399", description="交易流水号"),
     *                           @SWG\Property(property="transaction_id", type="string", example="3323616000180399", description="支付流水号"),
     *                           @SWG\Property(property="order_id", type="string", example="3323616000160399", description="订单号"),
     *                           @SWG\Property(property="trade_state", type="string", example="0", description="交易状态"),
     *                           @SWG\Property(property="adapay_div_status", type="string", example="20399", description="分账状态"),
     *                           @SWG\Property(property="mer_name", type="string", example="18530870713", description="商户名称"),
     *                           @SWG\Property(property="distributor_name", type="string", example="", description="店铺名称"),
     *                           @SWG\Property(property="time_start", type="integer", example="0", description="支付时间"),
     *                           @SWG\Property(property="pay_channel", type="integer", example="wx_lite", description="支付方式"),
     *                           @SWG\Property(property="username", type="integer", example="0", description="支付对象"),
     *                           @SWG\Property(property="pay_fee", type="integer", example="1", description="支付金额"),
     *                           @SWG\Property(property="total_fee", type="integer", example="1", description="订单总额"),
     *                           @SWG\Property(property="adapay_fee_mode", type="string", example="I", description="手续费扣费方式"),
     *                           @SWG\Property(property="adapay_fee", type="integer", example="", description="分账手续费"),
     *                           @SWG\Property(property="div_fee_info", type="object", description="",
     *                                @SWG\Property(property="total_div_fee", type="string", example="0", description="分账总金额"),
     *                                @SWG\Property(property="create_time", type="string", example="", description="分账时间"),
     *                                @SWG\Property(property="list", type="array", example="", description="分账详情",
     *                                     @SWG\Items(
     *                                         @SWG\Property(property="username", type="string", example="", description="分账用户"),
     *                                         @SWG\Property(property="div_fee", type="string", example="", description="分账金额"),
     *                                     )
     *                                )
     *
     *
     *                           ),
     *                           @SWG\Property(property="refund_list", type="array", description="",
     *                                @SWG\Items(
     *                                     @SWG\Property(property="refund_bn", type="string", example="0", description="退款流水号"),
     *                                     @SWG\Property(property="order_id", type="string", example="0", description="退款订单号"),
     *                                     @SWG\Property(property="refunded_fee", type="string", example="0", description="退款金额"),
     *                                     @SWG\Property(property="create_time", type="string", example="", description="分账时间"),
     *
     *                                )
     *                           ),
     *                           @SWG\Property(property="payType", type="string", example="wxpay", description="支付类型"),
     *                         )
     *                     ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function getTradeInfo($trade_id)
    {
        $tradeService = new BspayTradeService();
        $filter = array();
        $data = $tradeService->getTradeInfo($trade_id);
        return $this->response->array($data);
    }
}
