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

use App\Http\Controllers\Controller;
use DepositBundle\Services\DepositTrade;
use Illuminate\Http\Request;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Services\Payments\PaypalService;
use PaymentBundle\Manager\PaypalManager;

class PaypalNotify extends Controller
{
    /**
     * 处理 PayPal 支付成功回调
     */
    public function handle(Request $request)
    {
        // 获取请求参数
        $paymentId = $request->input('paymentId');
        $token = $request->input('token');
        $payerId = $request->input('PayerID');
        $returnUrl = $request->input('return_url', '/payment/success');
        $cancelUrl = $request->input('cancel_url', '/payment/failed');
        
        app('log')->info('PayPal支付回调: ' . json_encode([
            'paymentId' => $paymentId,
            'token' => $token,
            'payerId' => $payerId,
            'returnUrl' => $returnUrl,
            'cancelUrl' => $cancelUrl,
            'request' => $request->all(),
            'request_uri' => $request->getRequestUri(),
            'path_info' => $request->getPathInfo(),
            'url' => $request->url(),
            'full_url' => $request->fullUrl()
        ]));
        
        // 如果没有paymentId但有token，使用token作为paymentId
        if (empty($paymentId) && !empty($token)) {
            $paymentId = $token;
            app('log')->info('PayPal支付回调: 使用token作为paymentId: ' . $token);
        }
        
        if (empty($paymentId) || empty($payerId)) {
            app('log')->error('PayPal支付回调: 参数不完整');
            return redirect($cancelUrl);
        }
        
        // 获取PayPal配置
        $paypalConfig = config('paypal', []);
        $defaultCompanyId = $paypalConfig['default_company_id'] ?? 1;
        
        // 查找交易记录
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['transaction_id' => $paymentId]);
        
        // 如果找不到交易记录，尝试使用token查询
        if (!$tradeInfo && $token !== $paymentId) {
            app('log')->info('PayPal支付回调: 尝试使用token查询交易记录: ' . $token);
            $tradeInfo = $tradeService->getInfo(['transaction_id' => $token]);
        }
        
        // 如果交易不存在，尝试从PayPal获取订单信息并创建交易
        if (!$tradeInfo) {
            app('log')->warning('PayPal支付回调: 未找到交易信息，尝试从PayPal获取订单信息，paymentId=' . $paymentId);
            
            try {
                // 创建PayPal服务
                $paypalService = new PaypalService();
                
                // 执行支付并获取订单信息
                $result = $paypalService->executePayment($defaultCompanyId, $paymentId, $payerId);
                $result = json_encode($result);
                app('log')->info('PayPal支付执行结果(交易不存在): ' . $result);
                $resultArray = json_decode($result,true);
                if ($resultArray['status'] === 'SUCCESS') {
                    $invoiceNumber = $result['invoice_number'] ?? '';
                    $custom = $result['custom'] ?? [];
                    
                    // 如果有invoice_number，创建交易记录
                    if (!empty($invoiceNumber)) {
                        // 构建交易数据
                        $tradeData = [
                            'trade_id' => $invoiceNumber,
                            'company_id' => $custom['company_id'] ?? $defaultCompanyId,
                            'distributor_id' => $custom['distributor_id'] ?? 0,
                            'pay_type' => 'paypal',
                            'transaction_id' => $paymentId,
                            'trade_state' => 'SUCCESS',
                            'trade_state_desc' => '支付成功',
                            'user_id' => $custom['user_id'] ?? 0,
                            'total_fee' => $custom['total_fee'] ?? 0,
                            'cash_fee' => $custom['total_fee'] ?? 0,
                            'time_end' => time(),
                            'attach' => $custom['attach'] ?? '',
                            'body' => $custom['body'] ?? 'PayPal支付',
                            'detail' => $custom['detail'] ?? '',
                        ];
                        
                        app('log')->info('PayPal支付回调: 创建交易记录: ' . json_encode($tradeData));
                        
                        // 创建交易记录
                        $tradeId = $tradeService->create($tradeData);
                        app('log')->info('PayPal支付回调: 创建交易记录成功，tradeId=' . $tradeId);
                        
                        // 更新交易状态
                        if (isset($custom['attach']) && $custom['attach'] === 'depositRecharge') {
                            $depositTrade = new DepositTrade();
                            $options['pay_type'] = 'paypal';
                            $options['transaction_id'] = $paymentId;
                            $depositTrade->rechargeCallback($invoiceNumber, 'SUCCESS', $options);
                        } else {
                            $options['pay_type'] = 'paypal';
                            $options['transaction_id'] = $paymentId;
                            $tradeService->updateStatus($invoiceNumber, 'SUCCESS', $options);
                        }
                        
                        return redirect($returnUrl);
                    } else {
                        app('log')->error('PayPal支付回调: 无法获取订单号，paymentId=' . $paymentId);
                    }
                } else {
                    app('log')->error('PayPal支付执行失败(交易不存在): ' . ($result['error_msg'] ?? '未知错误'));
                }
            } catch (\Exception $e) {
                app('log')->error('PayPal支付回调处理异常(交易不存在): ' . $e->getMessage());
            }
            
            return redirect($cancelUrl);
        }
        
        app('log')->info('PayPal支付回调: 找到交易记录: ' . json_encode($tradeInfo));
        
        $companyId = $tradeInfo['company_id'];
        $distributorId = $tradeInfo['distributor_id'] ?? 0;
        
        $paypalService = new PaypalService($distributorId);
        $result = $paypalService->executePayment($companyId, $paymentId, $payerId);
        
        app('log')->info('PayPal支付执行结果: ' . json_encode($result));
        
        if ($result['status'] === 'SUCCESS') {
            $custom = $result['custom'] ?? [];
            $invoiceNumber = $result['invoice_number'] ?? $tradeInfo['trade_id'];
            
            if (isset($custom['attach']) && $custom['attach'] === 'depositRecharge') {
                $depositTrade = new DepositTrade();
                $options['pay_type'] = 'paypal';
                $options['transaction_id'] = $paymentId;
                $depositTrade->rechargeCallback($invoiceNumber, 'SUCCESS', $options);
            } else {
                $options['pay_type'] = 'paypal';
                $options['transaction_id'] = $paymentId;
                $tradeService->updateStatus($invoiceNumber, 'SUCCESS', $options);
            }
            
            return redirect($returnUrl);
        } else {
            app('log')->error('PayPal支付执行失败: ' . ($result['error_msg'] ?? '未知错误'));
            return redirect($cancelUrl);
        }
    }
    
    /**
     * 处理支付取消
     */
    public function cancel(Request $request)
    {
        $cancelUrl = $request->input('cancel_url', '/payment/cancelled');
        
        app('log')->info('PayPal支付取消: ' . json_encode([
            'cancelUrl' => $cancelUrl,
            'request' => $request->all()
        ]));
        
        return redirect($cancelUrl);
    }
    
    /**
     * 处理 PayPal Webhook 事件
     */
    public function webhook(Request $request)
    {
        $headers = getallheaders();
        $body = $request->getContent();
        
        app('log')->info('PayPal Webhook请求: ' . $body);
        app('log')->info('PayPal Webhook头信息: ' . json_encode($headers));
        
        // 获取PayPal配置
        $paypalConfig = config('paypal', []);
        if (empty($paypalConfig) || empty($paypalConfig['webhook_id'])) {
            app('log')->error('PayPal Webhook: 配置不完整');
            return response()->json(['status' => 'CONFIG_ERROR'], 500);
        }
        
        // 验证 Webhook 签名
        $paypalManager = new PaypalManager(
            $paypalConfig['client_id'] ?? '',
            $paypalConfig['client_secret'] ?? '',
            $paypalConfig['sandbox'] ?? true
        );
        
        $result = $paypalManager->verifyWebhook($headers, $body, $paypalConfig['webhook_id']);
        
        if (!$result['verified']) {
            app('log')->error('PayPal Webhook验证失败: ' . ($result['error'] ?? '未知错误'));
            return response()->json(['status' => 'VERIFICATION_FAILED'], 400);
        }
        
        $event = json_decode($body, true);
        $eventType = $event['event_type'] ?? '';
        
        app('log')->info('PayPal Webhook事件类型: ' . $eventType);
        
        // 处理不同类型的事件
        switch ($eventType) {
            case 'PAYMENT.SALE.COMPLETED':
                // 支付完成事件处理
                $this->handlePaymentCompleted($event);
                break;
                
            case 'PAYMENT.SALE.REFUNDED':
                // 退款事件处理
                $this->handlePaymentRefunded($event);
                break;
                
            case 'PAYMENT.SALE.REVERSED':
                // 支付撤销事件处理
                $this->handlePaymentReversed($event);
                break;
                
            default:
                app('log')->info('PayPal Webhook: 未处理的事件类型 ' . $eventType);
                break;
        }
        
        return response()->json(['status' => 'SUCCESS']);
    }
    
    /**
     * 处理支付完成事件
     */
    private function handlePaymentCompleted($event)
    {
        $resource = $event['resource'] ?? [];
        $parentPayment = $resource['parent_payment'] ?? '';
        
        if ($parentPayment) {
            $tradeService = new TradeService();
            $tradeInfo = $tradeService->getInfo(['transaction_id' => $parentPayment]);
            
            if ($tradeInfo && $tradeInfo['trade_state'] !== 'SUCCESS') {
                app('log')->info('PayPal Webhook: 更新订单状态为成功，订单ID=' . $tradeInfo['trade_id']);
                
                $options['pay_type'] = 'paypal';
                $options['transaction_id'] = $parentPayment;
                $tradeService->updateStatus($tradeInfo['trade_id'], 'SUCCESS', $options);
            }
        }
    }
    
    /**
     * 处理退款事件
     */
    private function handlePaymentRefunded($event)
    {
        $resource = $event['resource'] ?? [];
        $parentPayment = $resource['parent_payment'] ?? '';
        $refundId = $resource['id'] ?? '';
        
        if ($parentPayment && $refundId) {
            $tradeService = new TradeService();
            $tradeInfo = $tradeService->getInfo(['transaction_id' => $parentPayment]);
            
            if ($tradeInfo) {
                app('log')->info('PayPal Webhook: 处理退款事件，订单ID=' . $tradeInfo['trade_id'] . '，退款ID=' . $refundId);
                
                // 这里可以添加退款处理逻辑
                // 例如更新订单退款状态等
            }
        }
    }
    
    /**
     * 处理支付撤销事件
     */
    private function handlePaymentReversed($event)
    {
        $resource = $event['resource'] ?? [];
        $parentPayment = $resource['parent_payment'] ?? '';
        
        if ($parentPayment) {
            $tradeService = new TradeService();
            $tradeInfo = $tradeService->getInfo(['transaction_id' => $parentPayment]);
            
            if ($tradeInfo) {
                app('log')->info('PayPal Webhook: 处理支付撤销事件，订单ID=' . $tradeInfo['trade_id']);
                
                // 这里可以添加支付撤销处理逻辑
                // 例如更新订单状态为已撤销等
            }
        }
    }
} 