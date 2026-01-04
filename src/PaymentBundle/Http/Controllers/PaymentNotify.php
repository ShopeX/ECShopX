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

namespace PaymentBundle\Http\Controllers;

use App\Http\Controllers\Controller as Controller;
use DepositBundle\Services\DepositTrade;
use Illuminate\Http\Request;
use PaymentBundle\Services\Payments\AlipayService;
use OrdersBundle\Services\TradeService;

class PaymentNotify extends Controller
{
    /**
     * 接收支付宝支付回调通知
     *
     * @return void
     */
    public function handle(Request $request)
    {
        $data = $request->input();
        app('log')->info('alipay:response:' . var_export($data, 1));
        if (!isset($data['passback_params'])) {
            return [];
        }
        $data = AlipayService::encoding($data, 'utf-8', $data['charset'] ?? 'gb2312');
        // 获取tradeInfo
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['trade_id' => $data['out_trade_no']]);
        $distributorId = $tradeInfo['distributor_id'] ?? 0;
        $alipayService = new AlipayService($distributorId);
        parse_str(urldecode($data['passback_params']), $returnData);
        $alipay = $alipayService->getPayment($returnData['company_id']);
        try {
            $params = $alipay->verify($data); // 是的，验签就这么简单！

            // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
            // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
            // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
            // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
            // 4、验证app_id是否为该商户本身。
            // 5、其它业务逻辑情况
            if ($params->trade_status == 'TRADE_SUCCESS' || $params->trade_status == 'TRADE_FINISHED') {
                $status = 'SUCCESS';
            } else {
                $status = $params->trade_status;
            }
            if (isset($returnData['attach']) && $returnData['attach'] == 'depositRecharge') {
                $depositTrade = new DepositTrade();
                $options['pay_type'] = $returnData['pay_type'];
                $options['transaction_id'] = $params->trade_no;
                $depositTrade->rechargeCallback($params->out_trade_no, $status, $options);
            } else {
                $options['pay_type'] = $returnData['pay_type'];
                $options['transaction_id'] = $params->trade_no;
                $tradeService->updateStatus($params->out_trade_no, $status, $options);
            }

            return $alipay->success()->send();// laravel 框架中请直接 `return $alipay->success()`
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }
}
