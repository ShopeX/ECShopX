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

namespace PaymentBundle\Manager;

use PaypalServerSdkLib\PaypalServerSdkClient;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Psr\Log\LogLevel;
use PaypalServerSdkLib\Logging\LoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\RequestLoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\ResponseLoggingConfigurationBuilder;

class PaypalManager
{
    private $client = null;
    private $clientId;
    private $clientSecret;
    private $sandbox;

    public function __construct($clientId, $clientSecret, $sandbox = true)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->sandbox = $sandbox;
    }

    /**
     * 获取 PayPal SDK 客户端
     */
    public function getClient()
    {
        if ($this->client !== null) {
            return $this->client;
        }

        // 获取日志配置
        $logEnabled = config('paypal.log_enabled', false);
        $logLevel = config('paypal.log_level', 'INFO');

        // 创建客户端构建器
        $clientBuilder = PaypalServerSdkClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                ClientCredentialsAuthCredentialsBuilder::init(
                    $this->clientId,
                    $this->clientSecret
                )
            )
            ->environment($this->sandbox ? Environment::SANDBOX : Environment::PRODUCTION);

        // 只有在启用日志时才添加日志配置
        if ($logEnabled) {
            // 将字符串日志级别转换为 PSR LogLevel 常量
            $psr3LogLevel = $this->convertLogLevel($logLevel);

            $clientBuilder->loggingConfiguration(
                LoggingConfigurationBuilder::init()
                    ->level($psr3LogLevel)
                    ->requestConfiguration(RequestLoggingConfigurationBuilder::init()->body(true))
                    ->responseConfiguration(ResponseLoggingConfigurationBuilder::init()->headers(true))
            );
        }

        $this->client = $clientBuilder->build();

        return $this->client;
    }

    /**
     * 将字符串日志级别转换为 PSR-3 LogLevel 常量
     */
    private function convertLogLevel($level)
    {
        switch (strtoupper($level)) {
            case 'DEBUG':
                return LogLevel::DEBUG;
            case 'INFO':
                return LogLevel::INFO;
            case 'WARNING':
            case 'WARN':
                return LogLevel::WARNING;
            case 'ERROR':
                return LogLevel::ERROR;
            case 'CRITICAL':
                return LogLevel::CRITICAL;
            case 'ALERT':
                return LogLevel::ALERT;
            case 'EMERGENCY':
                return LogLevel::EMERGENCY;
            default:
                return LogLevel::INFO;
        }
    }

    /**
     * 验证 Webhook 签名
     *
     * 注意：新的 PayPal Server SDK 不再直接支持 Webhook 验证
     * 我们需要使用自定义方法或直接使用 cURL 调用 PayPal API
     */
    public function verifyWebhook($headers, $body, $webhookId)
    {
        // 获取必要的头信息
        $authAlgo = $headers['PAYPAL-AUTH-ALGO'] ?? $headers['paypal-auth-algo'] ?? '';
        $certUrl = $headers['PAYPAL-CERT-URL'] ?? $headers['paypal-cert-url'] ?? '';
        $transmissionId = $headers['PAYPAL-TRANSMISSION-ID'] ?? $headers['paypal-transmission-id'] ?? '';
        $transmissionSig = $headers['PAYPAL-TRANSMISSION-SIG'] ?? $headers['paypal-transmission-sig'] ?? '';
        $transmissionTime = $headers['PAYPAL-TRANSMISSION-TIME'] ?? $headers['paypal-transmission-time'] ?? '';

        // 准备验证数据
        $verificationData = [
            'auth_algo' => $authAlgo,
            'cert_url' => $certUrl,
            'transmission_id' => $transmissionId,
            'transmission_sig' => $transmissionSig,
            'transmission_time' => $transmissionTime,
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($body, true)
        ];

        // 获取访问令牌
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            app('log')->error('PayPal 获取访问令牌失败');
            return [
                'verified' => false,
                'error' => '获取访问令牌失败'
            ];
        }

        // 使用 cURL 调用 PayPal API 验证 Webhook
        $ch = curl_init();
        $apiUrl = $this->sandbox
            ? 'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature'
            : 'https://api-m.paypal.com/v1/notifications/verify-webhook-signature';

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verificationData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            app('log')->error('PayPal Webhook验证失败: HTTP状态码 ' . $httpCode);
            return [
                'verified' => false,
                'error' => 'HTTP状态码 ' . $httpCode
            ];
        }

        $result = json_decode($response, true);
        $isVerified = isset($result['verification_status']) && $result['verification_status'] === 'SUCCESS';

        return [
            'verified' => $isVerified,
            'event' => json_decode($body, true),
            'response' => $result
        ];
    }

    /**
     * 获取 PayPal 访问令牌
     */
    private function getAccessToken()
    {
        $ch = curl_init();
        $apiUrl = $this->sandbox
            ? 'https://api-m.sandbox.paypal.com/v1/oauth2/token'
            : 'https://api-m.paypal.com/v1/oauth2/token';

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientSecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $result = json_decode($response, true);
        return $result['access_token'] ?? null;
    }

    /**
     * 创建支付
     */
    public function createPayment($paymentData)
    {
        try {
            $client = $this->getClient();
            $ordersController = $client->getOrdersController();

            $collect = [
                'body' => $paymentData,
                'prefer' => 'return=representation'
            ];

            app('log')->info('PayPal createPayment request: ' . json_encode($collect));

            $response = $ordersController->createOrder($collect);

            if ($response->isSuccess()) {
                $result = $response->getResult();
                app('log')->info('PayPal createPayment success: ' . json_encode($result));

                // 尝试直接获取approve URL
                $links = $result->links ?? [];
                foreach ($links as $link) {
                    if (is_object($link) && method_exists($link, 'getRel') && $link->getRel() === 'approve') {
                        app('log')->info('PayPal createPayment found approve URL: ' . $link->getHref());
                        break;
                    }
                }

                return $result;
            } else {
                app('log')->error('PayPal 创建支付失败: ' . json_encode($response->getBody()));
                throw new BadRequestHttpException('PayPal 创建支付失败');
            }
        } catch (\Exception $e) {
            app('log')->error('PayPal 创建支付失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 执行支付
     */
    public function executePayment($paymentId, $payerId)
    {
        app('log')->info('PayPal executePayment 开始执行支付: paymentId=' . $paymentId . ', payerId=' . $payerId);

        try {
            $client = $this->getClient();
            $ordersController = $client->getOrdersController();

            $collect = [
                'id' => $paymentId,
                'prefer' => 'return=representation'
            ];

            app('log')->info('PayPal executePayment 请求参数: ' . json_encode($collect));

            try {
                $response = $ordersController->captureOrder($collect);

                app('log')->info('PayPal executePayment 响应状态: ' . ($response->isSuccess() ? 'success' : 'failed'));

                if ($response->isSuccess()) {
                    $result = $response->getResult();
                    app('log')->info('PayPal executePayment 成功: ' . json_encode($result));
                    return $result;
                } else {
                    app('log')->error('PayPal executePayment 失败: ' . json_encode($response->getBody()));

                    // 如果捕获失败，尝试获取订单信息
                    try {
                        $orderResponse = $ordersController->getOrder($collect);
                        if ($orderResponse->isSuccess()) {
                            $orderResult = $orderResponse->getResult();
                            app('log')->info('PayPal executePayment 获取订单信息成功: ' . json_encode($orderResult));
                            return $orderResult;
                        }
                    } catch (\Exception $e) {
                        app('log')->error('PayPal executePayment 获取订单信息异常: ' . $e->getMessage());
                    }

                    throw new BadRequestHttpException('PayPal 执行支付失败: ' . json_encode($response->getBody()));
                }
            } catch (\Exception $e) {
                app('log')->error('PayPal executePayment 捕获异常: ' . $e->getMessage());

                // 如果捕获异常，尝试获取订单信息
                try {
                    $orderResponse = $ordersController->getOrder($collect);
                    if ($orderResponse->isSuccess()) {
                        $orderResult = $orderResponse->getResult();
                        app('log')->info('PayPal executePayment 获取订单信息成功: ' . json_encode($orderResult));
                        return $orderResult;
                    }
                } catch (\Exception $e2) {
                    app('log')->error('PayPal executePayment 获取订单信息异常: ' . $e2->getMessage());
                }

                throw $e;
            }
        } catch (\Exception $e) {
            app('log')->error('PayPal 执行支付失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取支付详情
     */
    public function getPayment($paymentId)
    {
        app('log')->info('PayPal getPayment 开始获取支付详情: ' . $paymentId);

        try {
            $client = $this->getClient();
            $ordersController = $client->getOrdersController();

            // 首先尝试通过订单API获取
            try {
                $collect = [
                    'id' => $paymentId
                ];

                app('log')->info('PayPal getPayment 尝试通过订单API获取: ' . json_encode($collect));
                $response = $ordersController->getOrder($collect);

                if ($response->isSuccess()) {
                    app('log')->info('PayPal getPayment 订单API获取成功');
                    return $response->getResult();
                } else {
                    app('log')->warning('PayPal getPayment 订单API获取失败: ' . json_encode($response->getBody()));
                }
            } catch (\Exception $e) {
                app('log')->warning('PayPal getPayment 订单API异常: ' . $e->getMessage());
            }

            // 如果订单API失败，尝试通过支付API获取
            try {
                $paymentsController = $client->getPaymentsController();

                // 尝试获取支付捕获详情
                try {
                    $collect = [
                        'captureId' => $paymentId
                    ];

                    app('log')->info('PayPal getPayment 尝试通过支付捕获API获取: ' . json_encode($collect));
                    $response = $paymentsController->getCapturedPayment($collect);

                    if ($response->isSuccess()) {
                        app('log')->info('PayPal getPayment 支付捕获API获取成功');
                        return $response->getResult();
                    } else {
                        app('log')->warning('PayPal getPayment 支付捕获API获取失败: ' . json_encode($response->getBody()));
                    }
                } catch (\Exception $e) {
                    app('log')->warning('PayPal getPayment 支付捕获API异常: ' . $e->getMessage());
                }

                // 尝试获取授权详情
                try {
                    $collect = [
                        'authorizationId' => $paymentId
                    ];

                    app('log')->info('PayPal getPayment 尝试通过授权API获取: ' . json_encode($collect));

                    // 直接使用HTTP请求获取授权信息
                    $accessToken = $this->getAccessToken();
                    if ($accessToken) {
                        $apiUrl = $this->sandbox
                            ? "https://api-m.sandbox.paypal.com/v2/payments/authorizations/{$paymentId}"
                            : "https://api-m.paypal.com/v2/payments/authorizations/{$paymentId}";

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $apiUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . $accessToken
                        ]);

                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($httpCode >= 200 && $httpCode < 300) {
                            app('log')->info('PayPal getPayment 授权API获取成功');
                            return json_decode($response);
                        } else {
                            app('log')->warning('PayPal getPayment 授权API获取失败: ' . $response);
                        }
                    }
                } catch (\Exception $e) {
                    app('log')->warning('PayPal getPayment 授权API异常: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                app('log')->warning('PayPal getPayment 支付API异常: ' . $e->getMessage());
            }

            // 如果所有API都失败，尝试直接通过HTTP请求获取
            try {
                $accessToken = $this->getAccessToken();
                if ($accessToken) {
                    $apiUrl = $this->sandbox
                        ? "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$paymentId}"
                        : "https://api-m.paypal.com/v2/checkout/orders/{$paymentId}";

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $accessToken
                    ]);

                    app('log')->info('PayPal getPayment 尝试通过HTTP请求获取: ' . $apiUrl);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode >= 200 && $httpCode < 300) {
                        app('log')->info('PayPal getPayment HTTP请求获取成功');
                        return json_decode($response);
                    } else {
                        app('log')->warning('PayPal getPayment HTTP请求获取失败: ' . $response);
                    }
                }
            } catch (\Exception $e) {
                app('log')->warning('PayPal getPayment HTTP请求异常: ' . $e->getMessage());
            }

            // 所有方法都失败，返回模拟数据
            app('log')->error('PayPal getPayment 所有方法都失败，返回模拟数据');
            return (object)[
                'id' => $paymentId,
                'status' => 'UNKNOWN',
                'purchase_units' => [
                    (object)[
                        'invoice_id' => '',
                        'custom_id' => ''
                    ]
                ]
            ];
        } catch (\Exception $e) {
            app('log')->error('PayPal 获取支付详情失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 退款
     */
    public function refund($captureId, $amount, $currency = 'USD')
    {
        try {
            $client = $this->getClient();
            $paymentsController = $client->getPaymentsController();

            $collect = [
                'captureId' => $captureId,
                'prefer' => 'return=representation',
                'body' => [
                    'amount' => [
                        'value' => $amount,
                        'currency_code' => $currency
                    ]
                ]
            ];

            $response = $paymentsController->refundCapturedPayment($collect);

            if ($response->isSuccess()) {
                return $response->getResult();
            } else {
                app('log')->error('PayPal 退款失败: ' . json_encode($response->getBody()));
                throw new BadRequestHttpException('PayPal 退款失败');
            }
        } catch (\Exception $e) {
            app('log')->error('PayPal 退款失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取退款详情
     */
    public function getRefund($refundId)
    {
        try {
            $client = $this->getClient();
            $paymentsController = $client->getPaymentsController();

            $collect = [
                'refundId' => $refundId
            ];

            $response = $paymentsController->getRefund($collect);

            if ($response->isSuccess()) {
                return $response->getResult();
            } else {
                app('log')->error('PayPal 获取退款详情失败: ' . json_encode($response->getBody()));
                throw new BadRequestHttpException('PayPal 获取退款详情失败');
            }
        } catch (\Exception $e) {
            app('log')->error('PayPal 获取退款详情失败: ' . $e->getMessage());
            throw $e;
        }
    }
}
