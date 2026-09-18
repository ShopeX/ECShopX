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
use Illuminate\Http\Request;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use PaymentBundle\Support\ChinaumsNotifyValidator;

class ChinaumsNotify extends Controller
{
    /**
     * 接收银联支付回调通知
     *
     * @return string
     */
    public function handle(Request $request)
    {
        $data = $request->input();
        app('log')->info('chinaums:response:' . var_export($data, 1));

        try {
            $merOrderId = $data['merOrderId'] ?? '';
            if (! is_string($merOrderId) || $merOrderId === '') {
                throw new \InvalidArgumentException('missing merOrderId');
            }

            $tradeId = ChinaumsNotifyValidator::extractTradeIdFromMerOrderId($merOrderId);

            $tradeService = new TradeService();
            $tradeInfo = $tradeService->getInfo(['trade_id' => $tradeId]);
            ChinaumsNotifyValidator::assertTradeExists($tradeInfo);
            ChinaumsNotifyValidator::assertNotifyAmountMatchesTrade($data, $tradeInfo);

            $chinaumsService = new ChinaumsPayService();
            $params = $chinaumsService->verify($data);

            if ($params['status'] === 'TRADE_REFUND') {
                return 'SUCCESS';
            }

            if (! ChinaumsNotifyValidator::shouldUpdateTradeToSuccess($params['status'], $tradeInfo)) {
                return 'SUCCESS';
            }

            app('log')->info('chinaums:params:' . var_export($params, 1));
            $tradeService->updateStatus($tradeId, 'SUCCESS', [
                'pay_type' => $params['pay_type'],
                'transaction_id' => $params['trade_no'],
            ]);

            return 'SUCCESS';
        } catch (\Exception $e) {
            app('log')->info('chinaums:e:' . $e->getMessage());

            return 'FAILED';
        }
    }
}
