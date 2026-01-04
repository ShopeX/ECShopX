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

namespace OrdersBundle\Http\FrontApi\V1\Action;

// use PaymentBundle\Services\Payments\AdaPaymentService;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;

use OrdersBundle\Services\TradeService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Payment extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/trade/detail",
     *     summary="获取支付单详情",
     *     tags={"订单"},
     *     description="获取支付单详情",
     *     operationId="getTradeDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="trade_id", description="交易单号id", in="query", type="string", required=true),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="trade_id", type="string", example="abc12QQ3285746000090134", description="交易单号"),
     *               @SWG\Property(property="order_id", type="string", example="3285746000070134", description="订单号"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *               @SWG\Property(property="distributor_id", type="string", example="21", description="门店ID"),
     *               @SWG\Property(property="trade_source_type", type="string", example="normal", description="交易单来源类型。可选值有 membercard-会员卡购买;normal-实体订单购买;servers-服务订单购买;normal_community-社区订单购买;diposit-预存款购买;order_pay-买单购买;"),
     *               @SWG\Property(property="user_id", type="string", example="20134", description="购买用户"),
     *               @SWG\Property(property="mobile", type="string", example="13095920688", description="购买用户手机号"),
     *               @SWG\Property(property="open_id", type="string", example="oHxgH0VoBefaGAdSKm_wqeeVRceQ", description="用户open_id"),
     *               @SWG\Property(property="discount_info", type="string", example="", description="优惠金额，优惠金额，优惠原因json结构"),
     *               @SWG\Property(property="mch_id", type="string", example="", description="商户号，微信支付"),
     *               @SWG\Property(property="total_fee", type="integer", example="1100", description="应付总金额,以分为单位"),
     *               @SWG\Property(property="discount_fee", type="integer", example="100", description="订单优惠金额"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="pay_fee", type="integer", example="1100", description="支付金额"),
     *               @SWG\Property(property="trade_state", type="string", example="NOTPAY", description="交易状态。可选值有 SUCCESS—支付成功;REFUND—转入退款;NOTPAY—未支付;CLOSED—已关闭;REVOKED—已撤销;PAYERROR--支付失败(其他原因，如银行返回失败)"),
     *               @SWG\Property(property="pay_type", type="string", example="hfpay", description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
     *               @SWG\Property(property="transaction_id", type="string", example="", description="支付订单号"),
     *               @SWG\Property(property="authorizer_appid", type="string", example="wx6b8c2837f47e8a09", description="公众号的appid"),
     *               @SWG\Property(property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="支付小程序的appid"),
     *               @SWG\Property(property="bank_type", type="string", example="", description="付款银行"),
     *               @SWG\Property(property="body", type="string", example="手机...", description="交易商品简单描述"),
     *               @SWG\Property(property="detail", type="string", example="手机...", description="交易商品详情"),
     *               @SWG\Property(property="time_start", type="string", example="1609238408", description="交易起始时间"),
     *               @SWG\Property(property="time_expire", type="string", example="", description="交易结束时间"),
     *               @SWG\Property(property="cur_pay_fee", type="integer", example="1100", description="系统货币支付金额"),
     *               @SWG\Property(property="cur_fee_symbol", type="string", example="￥", description="系统配置货币符号"),
     *               @SWG\Property(property="cur_fee_rate", type="integer", example="1", description="系统配置货币汇率"),
     *               @SWG\Property(property="cur_fee_type", type="string", example="CNY", description="系统配置货币类型"),
     *               @SWG\Property(property="coupon_fee", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_info", type="string", example="", description="优惠券信息json结构"),
     *               @SWG\Property(property="inital_request", type="string", example="", description="统一下单原始请求json结构"),
     *               @SWG\Property(property="inital_response", type="string", example="", description="支付结果通知json结构"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getTradeDetail(Request $request)
    {
        $params = $request->all('trade_id');
        $rules = [
            'trade_id' => ['required', '支付单ID异常'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new BadRequestHttpException($errorMessage);
        }
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];

        $tradeService = new TradeService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'trade_id' => $params['trade_id'],
        ];
        $data = $tradeService->getInfo($filter);

        return $this->response->array($data);
    }
}
