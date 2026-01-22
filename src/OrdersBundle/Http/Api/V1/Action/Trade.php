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

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\TradeService;
use DistributionBundle\Services\DistributorService;

class Trade extends Controller
{
    /**
     * @SWG\Get(
     *     path="/trade",
     *     summary="获取交易列表",
     *     tags={"订单"},
     *     description="获取交易列表",
     *     operationId="getTradelist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="根据店铺筛选", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="根据状态筛选", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="9543", description="总记录条数"),
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
     *                           @SWG\Property(property="tradeState", type="string", example="SUCCESS", description="交易状态"),
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
     *                           @SWG\Property(property="tradeSourceType", type="string", example="normal_pointsmall", description="交易单来源类型。可选值有 membercard-会员卡购买;normal-实体订单购买;servers-服务订单购买;normal_community-社区订单购买;diposit-预存款购买;order_pay-买单购买;"),
     *                           @SWG\Property(property="couponFee", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="couponInfo", type="string", example="", description="优惠券信息json结构"),
     *                           @SWG\Property(property="initalRequest", type="string", example="", description="统一下单原始请求json结构"),
     *                           @SWG\Property(property="initalResponse", type="string", example="", description="支付结果通知json结构"),
     *                           @SWG\Property(property="payDate", type="string", example="2021-02-05 15:25:01", description="支付时间"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getTradelist(Request $request)
    {
        $tradeService = new TradeService();
        $filter = array();

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($request->input('status')) {
            $filter['trade_state'] = strtoupper($request->input('status'));
        }

        if ($request->input('mobile', false)) {
            if (strlen($request->input('mobile')) == 11) {
                $filter['mobile'] = $request->input('mobile');
            } else {
                $filter['trade_id'] = $request->input('mobile');
            }
        }

        if ($request->input('orderId', false)) {
            $filter['order_id'] = $request->input('orderId');
        }

        if ($request->input('time_start_begin')) {
            $filter['time_start_begin'] = $request->input('time_start_begin');
            $filter['time_start_end'] = $request->input('time_start_end');
        }

        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            $filter['shop_id'] = array_column($shopIds, 'shop_id');
        }

        if ($request->input('shop_id', false)) {
            $filter['shop_id'] = $request->input('shop_id');
        }

        if ($request->get('distributor_id', 0)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        $distributorListSet = app('auth')->user()->get('distributor_ids');
        if (!empty($distributorListSet)) {
            $distributorIdSet = array_column($distributorListSet, 'distributor_id');
            if (isset($filter['distributor_id']) && $filter['distributor_id']) {
                if (!in_array($filter['distributor_id'], $distributorIdSet)) {
                    unset($filter['distributor_id']);
                }
            } else {
                $filter['distributor_id'] = $distributorIdSet;
            }
        }

        if ($request->input('order_type') == 'service') {
            $filter['trade_source_type'] = ['service', 'groups', 'seckill'];
        } elseif ($request->input('order_type') == 'normal') {
            $filter['trade_source_type'] = ['normal', 'normal_groups', 'normal_seckill', 'normal_community'];
        } elseif ($request->input('order_type') == 'diposit') {
            $filter['trade_source_type'] = 'diposit';
        } elseif ($request->input('order_type') == 'order_pay') {
            $filter['trade_source_type'] = 'order_pay';
        }

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $orderBy = ['time_start' => 'DESC'];
        $data = $tradeService->getTradeList($filter, $orderBy, $pageSize, $page);

        if ($data['list']) {
            $distributorService = new DistributorService();
            $distributorList = $distributorService->lists(['distributor_id' => array_column($data['list'], 'distributorId')], null, count($data['list']), 1, false, 'distributor_id,name');
            $distributor = array_column($distributorList['list'], 'name', 'distributor_id');
        }
   
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $data['datapass_block'] = $datapassBlock;
        if ($data['list']) {
            foreach ($data['list'] as $key => $value) {
                if (isset($distributor[$value['distributorId']])) {
                    $data['list'][$key]['distributor_name'] = $distributor[$value['distributorId']];
                }
                if ($datapassBlock) {
                    $data['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/merchant/trade",
     *     summary="获取商家付款交易列表",
     *     tags={"订单"},
     *     description="获取商家付款交易列表",
     *     operationId="getMerchantTradelist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="支付状态", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
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
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getMerchantTradelist(Request $request)
    {
        $tradeService = new TradeService();
        $filter = array();

        $filter['company_id'] = app('auth')->user()->get('company_id');

        if ($request->input('status')) {
            $filter['status'] = strtoupper($request->input('status'));
        }

        if ($request->input('mobile', false)) {
            if (strlen($request->input('mobile')) == 11) {
                $filter['mobile'] = $request->input('mobile');
            } else {
                $filter['merchant_trade_id'] = $request->input('mobile');
            }
        }

        if ($request->input('distributor_id')) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $orderBy = ['create_time' => 'DESC'];

        $data = $tradeService->lists($filter, $orderBy, $pageSize, $page);
        return $this->response->array($data);
    }
}
