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

namespace PaymentBundle\Services\Payments;

use PaymentBundle\Interfaces\Payment as PaymentInterface;
use PaymentBundle\Manager\PaypalManager;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaypalService implements PaymentInterface
{
    private $distributorId = 0; // 店铺ID
    private $getDefault = true; // 是否取平台默认配置

    public function __construct($distributorId = 0, $getDefault = true)
    {
        $this->distributorId = $distributorId;
        $this->getDefault = $getDefault;
    }

    /**
     * 设置 PayPal 支付配置
     */
    public function setPaymentSetting($companyId, $data)
    {
        return app('redis')->set($this->genRedisId($companyId), json_encode($data));
    }

    /**
     * 获取支付方式配置
     */
    public function getPaymentSetting($companyId, $distributorId = 0)
    {
        $data = app('redis')->get($this->genRedisId($companyId));

        // 不存在店铺配置取平台的配置
        if (!$data && $this->getDefault && $this->distributorId > 0) {
            $this->distributorId = 0;
            $data = app('redis')->get($this->genRedisId($companyId));
        }

        $data = json_decode($data, true);
        if ($data) {
            return $data;
        } else {
            return [];
        }
    }

    /**
     * 获取 redis 存储的 ID
     */
    private function genRedisId($companyId)
    {
        $key = 'paypalPaymentSetting:' . sha1($companyId);
        return ($this->distributorId ? ($this->distributorId . $key) : $key);
    }

    /**
     * 获取 PayPal Manager 实例
     */
    private function getPaypalManager($companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);

        if (empty($paymentSetting) || empty($paymentSetting['client_id']) || empty($paymentSetting['client_secret'])) {
            throw new BadRequestHttpException('PayPal 配置信息不完整，请联系商家');
        }

        $sandbox = config('paypal.sandbox');

        return new PaypalManager(
            $paymentSetting['client_id'],
            $paymentSetting['client_secret'],
            $sandbox
        );
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        // 构建自定义参数
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'paypal',
            'attach' => 'depositRecharge',
            'user_id' => $data['user_id'] ?? 0,
            'distributor_id' => $data['distributor_id'] ?? 0,
            'total_fee' => $data['money'],
            'body' => $data['shop_name'] . '充值',
            'detail' => $data['shop_name'] . '充值',
            'order_id' => $data['deposit_trade_id']
        ];

        // 记录自定义参数
        app('log')->info('PayPal depositRecharge passbackParams: ' . json_encode($passbackParams));


        // 构建支付数据
        $amount = bcdiv($data['money'], 100, 2);
        $paymentData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => $amount,
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'USD',
                                'value' => $amount
                            ]
                        ]
                    ],
                    'description' => $data['shop_name'] . '充值',
                    'invoice_id' => $data['deposit_trade_id'],
                    'custom_id' => http_build_query($passbackParams),
                    'items' => [
                        [
                            'name' => $data['shop_name'] . '充值',
                            'quantity' => '1',
                            'unit_amount' => [
                                'currency_code' => 'USD',
                                'value' => $amount
                            ]
                        ]
                    ]
                ]
            ],
            'application_context' => [
                'return_url' => env('APP_URL') . '/api/paypal/return',
                'cancel_url' => env('APP_URL') . '/api/paypal/cancel',
                'brand_name' => isset($data['brand_name']) ? $data['brand_name'] : config('app.name', 'ECSHOPX'),
                'locale' => 'en-US',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW'
            ]
        ];

        try {
            $paypalManager = $this->getPaypalManager($data['company_id']);

            // 设置return_url和cancel_url
            $returnUrl = $data['return_url'] ?? env('APP_URL') . '/api/paypal/return';
            $cancelUrl = $data['cancel_url'] ?? env('APP_URL') . '/api/paypal/cancel';

            // 更新application_context
            $paymentData['application_context']['return_url'] = $returnUrl;
            $paymentData['application_context']['cancel_url'] = $cancelUrl;

            $result = $paypalManager->createPayment($paymentData);

            // 获取支付 URL
            $approvalUrl = $this->getPaypalApproveUrl($result);

            // 添加return_url和cancel_url参数到支付URL
            if (!empty($approvalUrl)) {
                $urlParts = parse_url($approvalUrl);
                $query = [];
                if (isset($urlParts['query'])) {
                    parse_str($urlParts['query'], $query);
                }
                $query['return_url'] = urlencode($returnUrl);
                $query['cancel_url'] = urlencode($cancelUrl);

                $urlParts['query'] = http_build_query($query);
                $approvalUrl = $this->buildUrl($urlParts);
            }

            app('log')->info('PayPal depositRecharge result: ' . json_encode([
                'pay_type' => 'paypal',
                'pay_url' => $approvalUrl,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ]));

            return [
                'pay_type' => 'paypal',
                'pay_info' => (array)$result,
                'pay_url' => $approvalUrl,
                'debug_info' => [
                    'result_id' => $result->id ?? null,
                    'result_status' => $result->status ?? null,
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl
                ]
            ];
        } catch (\Exception $e) {
            throw new BadRequestHttpException('PayPal 支付创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 进行支付
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        // 构建自定义参数
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'paypal',
            'attach' => 'orderPay',
            'user_id' => $data['user_id'] ?? 0,
            'distributor_id' => $data['distributor_id'] ?? 0,
            'total_fee' => $data['pay_fee'],
            'body' => $data['body'] ?? '',
            'detail' => $data['detail'] ?? '',
            'order_id' => $data['order_id']
        ];

        // 记录自定义参数
        app('log')->info('PayPal doPay passbackParams: ' . json_encode($passbackParams));


        // 构建支付数据
        $amount = bcdiv($data['pay_fee'], 100, 2);
        $paymentData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => $amount,
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'USD',
                                'value' => $amount
                            ]
                        ]
                    ],
                    'description' => $data['detail'],
                    'invoice_id' => $data['order_id'],
                    'custom_id' => http_build_query($passbackParams),
                    'items' => [
                        [
                            'name' => $data['body'],
                            'quantity' => '1',
                            'unit_amount' => [
                                'currency_code' => 'USD',
                                'value' => $amount
                            ]
                        ]
                    ]
                ]
            ],
            'application_context' => [
                'return_url' =>  env('APP_URL') . '/api/paypal/success' ,
                'cancel_url' => env('APP_URL') . '/api/paypal/cancel',
                'brand_name' => isset($data['brand_name']) ? $data['brand_name'] : config('app.name', 'ECSHOPX'),
                'locale' => 'en-US',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW'
            ]
        ];

        try {
            $paypalManager = $this->getPaypalManager($data['company_id']);

            // 设置return_url和cancel_url
            $returnUrl = $data['return_url'] ?? (env('APP_URL') . '/api/paypal/success' );
            $cancelUrl = $data['cancel_url'] ?? (env('APP_URL') . '/api/paypal/cancel');

            // 更新application_context
            $paymentData['application_context']['return_url'] = $returnUrl;
            $paymentData['application_context']['cancel_url'] = $cancelUrl;

            $result = $paypalManager->createPayment($paymentData);

            // 获取支付 URL
            $approvalUrl = $this->getPaypalApproveUrl($result);

            // 添加return_url和cancel_url参数到支付URL
            if (!empty($approvalUrl)) {
                $urlParts = parse_url($approvalUrl);
                $query = [];
                if (isset($urlParts['query'])) {
                    parse_str($urlParts['query'], $query);
                }
                $query['return_url'] = urlencode($returnUrl);
                $query['cancel_url'] = urlencode($cancelUrl);

                $urlParts['query'] = http_build_query($query);
                $approvalUrl = $this->buildUrl($urlParts);
            }

            app('log')->info('PayPal doPay result: ' . json_encode([
                'pay_type' => 'paypal',
                'pay_url' => $approvalUrl,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ]));

            return [
                'pay_type' => 'paypal',
                'pay_info' => (array)$result,
                'pay_url' => $approvalUrl,
                'debug_info' => [
                    'result_id' => $result->id ?? null,
                    'result_status' => $result->status ?? null,
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl
                ]
            ];
        } catch (\Exception $e) {
            throw new BadRequestHttpException('PayPal 支付创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 退款
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        try {
            $paypalManager = $this->getPaypalManager($companyId);
            $result = $paypalManager->refund(
                $data['transaction_id'],
                bcdiv($data['refund_fee'], 100, 2),
                'USD'
            );
            $result = json_encode($result);
            // 记录退款结果
            app('log')->info('PayPal doRefund 结果: ' . json_encode($result));

            // 将结果转换为数组
            $resultArray = json_decode($result,true);

            return [
                'refund_id' => $resultArray['id'] ?? '',
                'refund_status' => $resultArray['status'] ?? '',
            ];
        } catch (\Exception $e) {
            throw new BadRequestHttpException('PayPal 退款失败: ' . $e->getMessage());
        }
    }

    /**
     * 查询支付状态
     */
    public function query($data)
    {
        try {
            $paypalManager = $this->getPaypalManager($data['company_id']);
            $payment = $paypalManager->getPayment($data['transaction_id']);
            $payment = json_encode($payment);
            // 记录原始结果
            app('log')->info('PayPal query 原始结果: ' . $payment);

            // 将结果转换为数组
            $paymentArray = json_decode($payment,true);

            $status = 'FAIL';
            $paymentStatus = strtoupper($paymentArray['status'] ?? '');

            // 根据不同的状态判断
            if (in_array($paymentStatus, ['COMPLETED', 'APPROVED', 'SUCCESS', 'CAPTURED'])) {
                $status = 'SUCCESS';
            } elseif (in_array($paymentStatus, ['CREATED', 'SAVED', 'APPROVED', 'PAYER_ACTION_REQUIRED'])) {
                $status = 'NOTPAY';
            } elseif (in_array($paymentStatus, ['VOIDED', 'DECLINED'])) {
                $status = 'CLOSED';
            }

            app('log')->info('PayPal query 结果: ' . json_encode([
                'status' => $status,
                'transaction_id' => $paymentArray['id'] ?? '',
                'pay_type' => 'paypal',
            ]));

            return [
                'status' => $status,
                'transaction_id' => $paymentArray['id'] ?? '',
                'pay_type' => 'paypal',
            ];
        } catch (\Exception $e) {
            app('log')->error('PayPal query 异常: ' . $e->getMessage());
            return [
                'status' => 'FAIL',
                'error_msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取支付订单状态信息
     */
    public function getPayOrderInfo($companyId, $trade_id)
    {
        try {
            $paypalManager = $this->getPaypalManager($companyId);
            $payment = $paypalManager->getPayment($trade_id);

            app('log')->info('PayPal getPayOrderInfo 结果: ' . json_encode($payment));
            return (array)$payment;
        } catch (\Exception $e) {
            app('log')->error('PayPal getPayOrderInfo 异常: ' . $e->getMessage());
            return [
                'id' => $trade_id,
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $data)
    {
        try {
            $paypalManager = $this->getPaypalManager($companyId);
            $refund = $paypalManager->getRefund($data['refund_id']);

            app('log')->info('PayPal getRefundOrderInfo 结果: ' . json_encode($refund));
            return (array)$refund;
        } catch (\Exception $e) {
            app('log')->error('PayPal getRefundOrderInfo 异常: ' . $e->getMessage());
            return [
                'id' => $data['refund_id'],
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 执行支付（支付成功回调时调用）
     */
    public function executePayment($companyId, $paymentId, $payerId)
    {
        try {
            app('log')->info('PayPal executePayment 开始: companyId=' . $companyId . ', paymentId=' . $paymentId . ', payerId=' . $payerId);

            $paypalManager = $this->getPaypalManager($companyId);

            // 尝试执行支付
            try {
                $result = $paypalManager->executePayment($paymentId, $payerId);
            } catch (\Exception $e) {
                app('log')->error('PayPal executePayment 执行失败，尝试获取订单信息: ' . $e->getMessage());

                // 如果执行失败，可能订单已经被处理过，尝试直接获取订单信息
                $result = $paypalManager->getPayment($paymentId);
            }

            // 记录原始结果
            app('log')->info('PayPal executePayment 原始结果: ' . json_encode($result));

            // 将结果转换为数组
            $resultArray = (array)$result;

            // 获取订单状态
            $status = strtoupper($resultArray['status'] ?? '');
            app('log')->info('PayPal executePayment 订单状态: ' . $status);

            // 检查订单状态
            $validStatus = ['COMPLETED', 'APPROVED', 'SUCCESS', 'CAPTURED'];
            if (!in_array($status, $validStatus)) {
                app('log')->error('PayPal executePayment 订单状态无效: ' . $status);
                return [
                    'status' => 'FAIL',
                    'error_msg' => '订单状态无效: ' . $status,
                ];
            }

            // 获取交易信息
            $purchaseUnits = $resultArray['purchase_units'] ?? [];
            $purchaseUnit = $purchaseUnits[0] ?? null;
            if (!$purchaseUnit) {
                app('log')->error('PayPal executePayment 无法获取交易信息: ' . json_encode($resultArray));

                // 尝试从其他字段获取信息
                $invoiceNumber = $resultArray['invoice_id'] ?? '';
                $customId = $resultArray['custom_id'] ?? '';

                if (!empty($invoiceNumber) || !empty($customId)) {
                    $custom = [];
                    if (!empty($customId)) {
                        parse_str($customId, $custom);
                    }

                    return [
                        'status' => 'SUCCESS',
                        'transaction_id' => $resultArray['id'] ?? $paymentId,
                        'invoice_number' => $invoiceNumber,
                        'custom' => $custom,
                    ];
                }

                return [
                    'status' => 'FAIL',
                    'error_msg' => '无法获取交易信息',
                ];
            }

            // 将purchaseUnit转换为数组
            $purchaseUnit = (array)$purchaseUnit;
            app('log')->info('PayPal executePayment purchaseUnit: ' . json_encode($purchaseUnit));

            $invoiceNumber = $purchaseUnit['invoice_id'] ?? '';

            // 如果没有invoice_id，尝试从reference_id获取
            if (empty($invoiceNumber) && isset($purchaseUnit['reference_id'])) {
                $invoiceNumber = $purchaseUnit['reference_id'];
                app('log')->info('PayPal executePayment 使用reference_id作为invoice_number: ' . $invoiceNumber);
            }

            // 解析自定义参数
            $custom = [];
            $customId = $purchaseUnit['custom_id'] ?? '';
            app('log')->info('PayPal executePayment customId: ' . $customId);

            if ($customId) {
                // 尝试多种方式解析customId
                // 1. 标准的URL查询字符串格式
                parse_str($customId, $custom);

                // 2. 如果解析结果为空，尝试JSON格式
                if (empty($custom) && $this->isJson($customId)) {
                    $custom = json_decode($customId, true);
                }

                // 3. 如果还是为空，尝试键值对格式 (key1=value1,key2=value2)
                if (empty($custom) && strpos($customId, '=') !== false) {
                    $pairs = explode(',', $customId);
                    foreach ($pairs as $pair) {
                        $parts = explode('=', $pair, 2);
                        if (count($parts) == 2) {
                            $custom[trim($parts[0])] = trim($parts[1]);
                        }
                    }
                }
            }

            // 如果没有invoice_number，尝试从custom中获取
            if (empty($invoiceNumber) && isset($custom['order_id'])) {
                $invoiceNumber = $custom['order_id'];
                app('log')->info('PayPal executePayment 使用custom.order_id作为invoice_number: ' . $invoiceNumber);
            }

            app('log')->info('PayPal executePayment 解析后的custom: ' . json_encode($custom));
            app('log')->info('PayPal executePayment 最终invoice_number: ' . $invoiceNumber);

            return [
                'status' => 'SUCCESS',
                'transaction_id' => $resultArray['id'] ?? $paymentId,
                'invoice_number' => $invoiceNumber,
                'custom' => $custom,
            ];
        } catch (\Exception $e) {
            app('log')->error('PayPal executePayment 异常: ' . $e->getMessage());
            return [
                'status' => 'FAIL',
                'error_msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查字符串是否为有效的JSON
     */
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 获取PayPal支付URL
     */
    private function getPaypalApproveUrl($result)
    {
        $approvalUrl = '';

        // 记录原始结果
        app('log')->info('PayPal getPaypalApproveUrl original result: ' . json_encode($result));

        // 尝试从反射中获取links属性
        try {
            $reflectionClass = new \ReflectionClass($result);
            $linksProperty = $reflectionClass->getProperty('links');
            $linksProperty->setAccessible(true);
            $links = $linksProperty->getValue($result);

            app('log')->info('PayPal getPaypalApproveUrl links from reflection: ' . json_encode($links));

            if (is_array($links)) {
                foreach ($links as $link) {
                    $reflectionLink = new \ReflectionClass($link);
                    $relProperty = $reflectionLink->getProperty('rel');
                    $relProperty->setAccessible(true);
                    $rel = $relProperty->getValue($link);

                    if ($rel === 'approve') {
                        $hrefProperty = $reflectionLink->getProperty('href');
                        $hrefProperty->setAccessible(true);
                        $approvalUrl = $hrefProperty->getValue($link);
                        app('log')->info('PayPal getPaypalApproveUrl found URL via reflection: ' . $approvalUrl);
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            app('log')->error('PayPal getPaypalApproveUrl reflection error: ' . $e->getMessage());
        }

        // 如果通过反射无法获取，尝试通过JSON序列化获取
        if (empty($approvalUrl)) {
            try {
                $jsonResult = json_decode(json_encode($result), true);
                app('log')->info('PayPal getPaypalApproveUrl JSON result: ' . json_encode($jsonResult));

                if (isset($jsonResult['links']) && is_array($jsonResult['links'])) {
                    foreach ($jsonResult['links'] as $link) {
                        if (isset($link['rel']) && $link['rel'] === 'approve' && isset($link['href'])) {
                            $approvalUrl = $link['href'];
                            app('log')->info('PayPal getPaypalApproveUrl found URL via JSON links: ' . $approvalUrl);
                            break;
                        }
                    }
                }

                // 尝试查找所有可能的links结构
                foreach ($jsonResult as $key => $value) {
                    if (strpos($key, 'links') !== false && is_array($value)) {
                        app('log')->info('PayPal getPaypalApproveUrl found links array in key: ' . $key);
                        foreach ($value as $link) {
                            if (is_array($link)) {
                                foreach ($link as $linkKey => $linkValue) {
                                    if (strpos($linkKey, 'rel') !== false && $linkValue === 'approve') {
                                        foreach ($link as $hrefKey => $hrefValue) {
                                            if (strpos($hrefKey, 'href') !== false) {
                                                $approvalUrl = $hrefValue;
                                                app('log')->info('PayPal getPaypalApproveUrl found URL via key search: ' . $approvalUrl);
                                                break 3;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                app('log')->error('PayPal getPaypalApproveUrl JSON error: ' . $e->getMessage());
            }
        }

        // 如果还是无法获取，尝试直接从返回对象中获取
        if (empty($approvalUrl) && method_exists($result, 'getLinks')) {
            try {
                $links = $result->getLinks();
                app('log')->info('PayPal getPaypalApproveUrl links from getLinks(): ' . json_encode($links));

                foreach ($links as $link) {
                    if (method_exists($link, 'getRel') && $link->getRel() === 'approve') {
                        $approvalUrl = $link->getHref();
                        app('log')->info('PayPal getPaypalApproveUrl found URL via getLinks(): ' . $approvalUrl);
                        break;
                    }
                }
            } catch (\Exception $e) {
                app('log')->error('PayPal getPaypalApproveUrl getLinks error: ' . $e->getMessage());
            }
        }

        app('log')->info('PayPal getPaypalApproveUrl final URL: ' . $approvalUrl);
        return $approvalUrl;
    }

    /**
     * 从URL部分重建完整URL
     */
    private function buildUrl($parts)
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'] . '://';
        }

        if (isset($parts['user'])) {
            $url .= $parts['user'];
            if (isset($parts['pass'])) {
                $url .= ':' . $parts['pass'];
            }
            $url .= '@';
        }

        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }

        if (isset($parts['port'])) {
            $url .= ':' . $parts['port'];
        }

        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }

        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }
}
