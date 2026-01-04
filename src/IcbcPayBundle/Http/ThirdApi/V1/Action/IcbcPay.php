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

namespace IcbcPayBundle\Http\ThirdApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use DepositBundle\Services\DepositTrade;
use HfPayBundle\Entities\HfpayBankCard;
use HfPayBundle\Entities\HfpayCashRecord;
use HfPayBundle\Entities\HfpayEnterapply;
use HfPayBundle\Events\HfPayDistributorWithdrawSuccessEvent;
use HfPayBundle\Services\src\Kernel\HfSign;
use Illuminate\Http\Request;
use OrdersBundle\Entities\MerchantPaymentTrade;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Services\Payments\IcbcPayService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IcbcPay extends Controller
{
    /**
     * @SWG\Post(
     *     path="/icbcpay/notify",
     *     summary="接收工商银行异步推送消息",
     *     tags={"hfpay"},
     *     description="接收工商银行异步推送消息",
     *     operationId="notify",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="RECV_ORD_ID_", type="string", description="响应结果"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfpayErrorRespones") ) )
     * )
     */
    public function notify(Request $request)
    {
        // ShopEx EcShopX Core Module
        $icbcPayService = new IcbcPayService();
        $input = $request->input();
        $biz_content = $input['biz_content'] ?? [];
        $tradeId = $biz_content['out_trade_no']??'';
        $attach = $biz_content['attach']??[];
        if(empty($biz_content) || empty($tradeId) || empty($attach)){
            $res =  ['return_code'=>'4', 'return_msg'=>'非法请求或参数错误',];
            return json_encode($res,JSON_UNESCAPED_UNICODE);
        }
        $attach = json_encode($attach,true);
        $companyId = $attach['company_id'];
        $transaction_id = $biz_content['order_id'] ?? null;
        $options['transaction_id'] = $transaction_id ;
        if(!$icbcPayService->verifySignature($companyId,$input,$errMsg)){
            return $icbcPayService->responseSucc($companyId,['return_code'=>'1', 'return_msg'=>$errMsg]);
        }
        if ($biz_content['return_code'] == '0') {
            $status = 'SUCCESS';
        } else {
            $status = 'PAYERROR';
        }
        try {
            $tradeService = new TradeService();
            $tradeService->updateOneBy(['trade_id' => $tradeId], ['inital_response' => json_encode($biz_content)]);
            $tradeService->updateStatus($tradeId, $status, $options);
        } catch (BadRequestHttpException $e) {
            // 订单不存在，或者已更新 不需要在处理
        }

        return $icbcPayService->responseSucc($companyId);
    }


}
