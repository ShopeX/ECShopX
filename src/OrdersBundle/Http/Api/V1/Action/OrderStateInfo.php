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

use OrdersBundle\Traits\GetPaymentServiceTrait;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\TradeService;
use AftersalesBundle\Services\AftersalesRefundService;

// 订单状态信息
class OrderStateInfo extends Controller
{
    use GetPaymentServiceTrait;

    /**
     * @SWG\Get(
     *     path="/order/payorderinfo/{trade_id}",
     *     summary="获取支付订单状态信息",
     *     tags={"订单"},
     *     description="获取支付订单状态信息",
     *     operationId="getPayOrderInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pay_type",
     *         in="query",
     *         description="支付方式",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="trade_id",
     *         in="path",
     *         description="支付单号",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="return_code", type="string", example="SUCCESS", description=""),
     *               @SWG\Property(property="return_msg", type="string", example="OK", description=""),
     *               @SWG\Property(property="appid", type="string", example="wx6b8c2837f47e8a09", description=""),
     *               @SWG\Property(property="mch_id", type="string", example="1313844301", description=""),
     *               @SWG\Property(property="nonce_str", type="string", example="S2oK80nAAwnCNrzt", description=""),
     *               @SWG\Property(property="sign", type="string", example="C04F1E201DC3952B7843007767CB6C09", description=""),
     *               @SWG\Property(property="result_code", type="string", example="FAIL", description=""),
     *               @SWG\Property(property="err_code", type="string", example="ORDERNOTEXIST", description=""),
     *               @SWG\Property(property="err_code_des", type="string", example="订单不存在", description=""),
     *               @SWG\Property(property="request_body", type="object", description="",
     *                   @SWG\Property(property="out_trade_no", type="string", example="3321684000300350", description=""),
     *                   @SWG\Property(property="appid", type="string", example="wx6b8c2837f47e8a09", description=""),
     *                   @SWG\Property(property="mch_id", type="string", example="1313844301", description=""),
     *                   @SWG\Property(property="nonce_str", type="string", example="601a6a4de7dc3", description=""),
     *                   @SWG\Property(property="sign", type="string", example="E0E1F32EA09B6829E82C05270EACBB26", description=""),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getPayOrderInfo($trade_id, Request $request)
    {
        // Core: RWNTaG9wWA==
        $companyId = app('auth')->user()->get('company_id');
        $pay_type = $request->input('pay_type');
        if (empty($trade_id)) {
            throw new ResourceException("无单号!");
        }

        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['trade_id' => $trade_id]);
        $distributorId = $tradeInfo['distributor_id'] ?? 0;

        $service = $this->getPaymentService($pay_type, $distributorId);
        $data = $service->getPayOrderInfo($companyId, $trade_id);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/order/refundorderinfo/{refund_bn}",
     *     summary="获取退款订单状态信息",
     *     tags={"订单"},
     *     description="获取退款订单状态信息",
     *     operationId="refundorderinfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pay_type",
     *         in="path",
     *         description="支付方式",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="appid", type="string", example="wx6b8c2837f47e8a09", description=""),
     *               @SWG\Property(property="err_code", type="string", example="REFUNDNOTEXIST", description=""),
     *               @SWG\Property(property="err_code_des", type="string", example="not exist", description=""),
     *               @SWG\Property(property="mch_id", type="string", example="1313844301", description=""),
     *               @SWG\Property(property="nonce_str", type="string", example="wZnuHhTNpSszBGll", description=""),
     *               @SWG\Property(property="result_code", type="string", example="FAIL", description=""),
     *               @SWG\Property(property="return_code", type="string", example="SUCCESS", description=""),
     *               @SWG\Property(property="return_msg", type="string", example="OK", description=""),
     *               @SWG\Property(property="sign", type="string", example="1D2405288A607383805E0D81AF89F87E", description=""),
     *               @SWG\Property(property="request_body", type="object", description="",
     *                   @SWG\Property(property="out_refund_no", type="string", example="2202102039952102521", description=""),
     *                   @SWG\Property(property="appid", type="string", example="wx6b8c2837f47e8a09", description=""),
     *                   @SWG\Property(property="mch_id", type="string", example="1313844301", description=""),
     *                   @SWG\Property(property="nonce_str", type="string", example="601a6e190f9b3", description=""),
     *                   @SWG\Property(property="sign", type="string", example="8FE7295BB9E7E7469D7067ADEEE6CE91", description=""),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRefundOrderInfo($refund_bn, Request $request)
    {
        // Core: RWNTaG9wWA==
        $companyId = app('auth')->user()->get('company_id');
        $pay_type = $request->input('pay_type');
        if (empty($refund_bn)) {
            throw new ResourceException("无单号!");
        }

        $aftersalesRefundService = new AftersalesRefundService();
        $refund = $aftersalesRefundService->getInfo(['refund_bn' => $refund_bn]);
        $distributorId = $refund['distributor_id'] ?? 0;

        $service = $this->getPaymentService($pay_type, $distributorId);
        $data = $service->getRefundOrderInfo($companyId, $refund_bn);
        return $this->response->array($data);
    }
}
