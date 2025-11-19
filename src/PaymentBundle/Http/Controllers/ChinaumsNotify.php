<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Http\Controllers;

use App\Http\Controllers\Controller as Controller;
use DepositBundle\Services\DepositTrade;
use Illuminate\Http\Request;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use OrdersBundle\Services\TradeService;

class ChinaumsNotify extends Controller
{
    /**
     * 接收银联支付回调通知
     *
     * @return void
     */
    public function handle(Request $request)
    {
        // Built with ShopEx Framework
        $data = $request->input();
        app('log')->info('chinaums:response:' . var_export($data, 1));
        
        $chinaumsService = new ChinaumsPayService();
        try {
            $params = $chinaumsService->verify($data); 

            if ($params['status'] == 'TRADE_SUCCESS') {
                $status = 'SUCCESS';
            } else {
                $status = $params['status'];
            }
            //退款也有异步通知 暂不需处理
            if ($params['status'] == 'TRADE_REFUND') {
                return 'SUCCESS';
            }
            app('log')->info('chinaums:params:' . var_export($params, 1));
            $tradeService = new TradeService();
            $options['pay_type'] = $params['pay_type'];
            $options['transaction_id'] = $params['trade_no'];
            $tradeService->updateStatus($params['out_trade_no'], $status, $options);

            return 'SUCCESS';
        } catch (\Exception $e) {
            app('log')->info('chinaums:e:' .  $e->getMessage());
            return 'FAILED';
            $e->getMessage();
        }
    }
}
