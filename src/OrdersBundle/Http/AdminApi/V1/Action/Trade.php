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

namespace OrdersBundle\Http\AdminApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\TradeService;

class Trade extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/trade",
     *     summary="获取交易列表",
     *     tags={"订单"},
     *     description="获取交易列表",
     *     operationId="getTradelist",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="status", in="query", description="根据状态筛选", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="28", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="tradeId", type="string", example="abc12QQ2927722000200160", description=""),
     *                           @SWG\Property(property="orderId", type="string", example="2927722000130160", description=""),
     *                           @SWG\Property(property="shopId", type="string", example="9", description=""),
     *                           @SWG\Property(property="userId", type="string", example="160", description=""),
     *                           @SWG\Property(property="mobile", type="string", example="18625741686", description="购买用户手机号"),
     *                           @SWG\Property(property="openId", type="string", example="owGAQ0eeKDjaaXbmjwA4JLe9RxOI", description=""),
     *                           @SWG\Property(property="discountInfo", type="string", example="", description=""),
     *                           @SWG\Property(property="mchId", type="string", example="1313844301", description=""),
     *                           @SWG\Property(property="totalFee", type="integer", example="1", description=""),
     *                           @SWG\Property(property="discountFee", type="integer", example="0", description=""),
     *                           @SWG\Property(property="feeType", type="string", example="CNY", description=""),
     *                           @SWG\Property(property="payFee", type="integer", example="1", description=""),
     *                           @SWG\Property(property="tradeState", type="string", example="SUCCESS", description=""),
     *                           @SWG\Property(property="payType", type="string", example="wxpay", description=""),
     *                           @SWG\Property(property="transactionId", type="string", example="4200000490202001067529693548", description=""),
     *                           @SWG\Property(property="wxaAppid", type="string", example="wx0ca44b76031f147e", description=""),
     *                           @SWG\Property(property="bankType", type="string", example="OTHERS", description=""),
     *                           @SWG\Property(property="body", type="string", example="兔兔2...", description="交易商品简单描述"),
     *                           @SWG\Property(property="detail", type="string", example="兔兔2...", description="交易商品详情"),
     *                           @SWG\Property(property="timeStart", type="string", example="1578305028", description=""),
     *                           @SWG\Property(property="timeExpire", type="string", example="1578305043", description=""),
     *                           @SWG\Property(property="companyId", type="string", example="1", description=""),
     *                           @SWG\Property(property="authorizerAppid", type="string", example="wxe4d71857568b84f5", description=""),
     *                           @SWG\Property(property="curFeeType", type="string", example="CNY", description=""),
     *                           @SWG\Property(property="curFeeRate", type="integer", example="1", description=""),
     *                           @SWG\Property(property="curFeeSymbol", type="string", example="￥", description=""),
     *                           @SWG\Property(property="curPayFee", type="integer", example="1", description=""),
     *                           @SWG\Property(property="distributorId", type="string", example="20", description=""),
     *                           @SWG\Property(property="tradeSourceType", type="string", example="normal_community", description=""),
     *                           @SWG\Property(property="couponFee", type="integer", example="0", description=""),
     *                           @SWG\Property(property="couponInfo", type="string", example="", description=""),
     *                           @SWG\Property(property="initalRequest", type="string", example="", description=""),
     *                           @SWG\Property(property="initalResponse", type="string", example="", description=""),
     *                           @SWG\Property(property="trade_id", type="string", example="abc12QQ2927722000200160", description="交易单号"),
     *                           @SWG\Property(property="order_id", type="string", example="2927722000130160", description="订单号"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                           @SWG\Property(property="shop_id", type="string", example="9", description="门店ID"),
     *                           @SWG\Property(property="distributor_id", type="string", example="20", description="门店ID"),
     *                           @SWG\Property(property="trade_source_type", type="string", example="normal_community", description="交易单来源类型。可选值有 membercard-会员卡购买;normal-实体订单购买;servers-服务订单购买;normal_community-社区订单购买;diposit-预存款购买;order_pay-买单购买;"),
     *                           @SWG\Property(property="user_id", type="string", example="160", description="购买用户"),
     *                           @SWG\Property(property="open_id", type="string", example="owGAQ0eeKDjaaXbmjwA4JLe9RxOI", description="用户open_id"),
     *                           @SWG\Property(property="discount_info", type="string", example="", description="优惠金额，优惠金额，优惠原因json结构"),
     *                           @SWG\Property(property="mch_id", type="string", example="1313844301", description="商户号，微信支付"),
     *                           @SWG\Property(property="total_fee", type="integer", example="1", description="应付总金额,以分为单位"),
     *                           @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额"),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="pay_fee", type="integer", example="1", description="支付金额"),
     *                           @SWG\Property(property="trade_state", type="string", example="SUCCESS", description="交易状态。可选值有 SUCCESS—支付成功;REFUND—转入退款;NOTPAY—未支付;CLOSED—已关闭;REVOKED—已撤销;PAYERROR--支付失败(其他原因，如银行返回失败)"),
     *                           @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
     *                           @SWG\Property(property="transaction_id", type="string", example="4200000490202001067529693548", description="支付订单号"),
     *                           @SWG\Property(property="authorizer_appid", type="string", example="wxe4d71857568b84f5", description="公众号的appid"),
     *                           @SWG\Property(property="wxa_appid", type="string", example="wx0ca44b76031f147e", description="支付小程序的appid"),
     *                           @SWG\Property(property="bank_type", type="string", example="OTHERS", description="付款银行"),
     *                           @SWG\Property(property="time_start", type="string", example="1578305028", description="交易起始时间"),
     *                           @SWG\Property(property="time_expire", type="string", example="1578305043", description="交易结束时间"),
     *                           @SWG\Property(property="cur_fee_type", type="string", example="CNY", description="系统配置货币类型"),
     *                           @SWG\Property(property="cur_fee_rate", type="integer", example="1", description="系统配置货币汇率"),
     *                           @SWG\Property(property="cur_fee_symbol", type="string", example="￥", description="系统配置货币符号"),
     *                           @SWG\Property(property="cur_pay_fee", type="integer", example="1", description="系统货币支付金额"),
     *                           @SWG\Property(property="coupon_fee", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_info", type="string", example="", description="优惠券信息json结构"),
     *                           @SWG\Property(property="inital_request", type="string", example="", description="统一下单原始请求json结构"),
     *                           @SWG\Property(property="inital_response", type="string", example="", description="支付结果通知json结构"),
     *                           @SWG\Property(property="payDate", type="string", example="2020-01-06 18:04:03", description=""),
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
        // Powered by ShopEx EcShopX
        $tradeService = new TradeService();
        $filter = array();

        $authInfo = $this->auth->user();

        $shopId = $request->input('shop_id');
        if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
            $result = ['list' => [], 'total_count' => 0];
            return $this->response->array($result);
        }

        $distributorId = $request->input('distributor_id');
        if ($authInfo['distributor_ids'] && $distributorId && !in_array($distributorId, $authInfo['distributor_ids'])) {
            $result = ['list' => [], 'total_count' => 0];
            return $this->response->array($result);
        }
        if ($shopId) {
            $filter['shop_id'] = $shopId;
        }

        if ($distributorId) {
            $filter['distributor_id'] = $distributorId;
        }
        $filter['trade_state'] = $request->input('status', 'SUCCESS');

        $start = strtotime(date("Y-m-d"));
        $end = strtotime(date('Ymd 23:59:59'));
        $filter['time_start_begin'] = $request->input('time_start_begin', $start);
        $filter['time_start_end'] = $request->input('time_start_end', $end);

        $pageSize = $request->input('page_size', $request->input('pageSize', 20));
        $page = $request->input('page', 1);

        $orderBy = ['time_start' => 'DESC'];

        $data = $tradeService->getTradeList($filter, $orderBy, $pageSize, $page);
        return $this->response->array($data);
    }
}
