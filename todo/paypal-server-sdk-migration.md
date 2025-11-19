# PayPal SDK 迁移指南

本文档详细说明了从旧版 PayPal REST API SDK (`paypal/rest-api-sdk-php`) 迁移到新版 PayPal Server SDK (`paypal/paypal-server-sdk:1.1.0`) 的步骤和变更。

## 变更概述

1. **SDK 包变更**：
   - 旧版：`paypal/rest-api-sdk-php`
   - 新版：`paypal/paypal-server-sdk:1.1.0`

2. **API 架构变更**：
   - 旧版：使用 `ApiContext` 和各种模型类直接调用 API
   - 新版：使用控制器模式，通过 `OrdersController`、`PaymentsController` 等控制器调用 API

3. **认证方式变更**：
   - 旧版：使用 `OAuthTokenCredential` 和 `ApiContext`
   - 新版：使用 `ClientCredentialsAuthCredentialsBuilder` 和 `PaypalServerSdkClientBuilder`

4. **Webhook 验证变更**：
   - 旧版：使用 `VerifyWebhookSignature` 类
   - 新版：不再直接支持 Webhook 验证，需要手动实现或使用 cURL 调用 API

## 迁移步骤

### 1. 安装新版 SDK

```bash
composer require paypal/paypal-server-sdk:1.1.0
```

### 2. 更新 PaypalManager 类

#### 2.1 导入新的命名空间

```php
use PaypalServerSdkLib\PaypalServerSdkClient;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use Psr\Log\LogLevel;
use PaypalServerSdkLib\Logging\LoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\RequestLoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\ResponseLoggingConfigurationBuilder;
```

#### 2.2 更新客户端初始化

旧版：
```php
public function getApiContext()
{
    if ($this->apiContext !== null) {
        return $this->apiContext;
    }

    $this->apiContext = new ApiContext(
        new OAuthTokenCredential(
            $this->clientId,
            $this->clientSecret
        )
    );

    $this->apiContext->setConfig([
        'mode' => $this->sandbox ? 'sandbox' : 'live',
        'log.LogEnabled' => true,
        'log.FileName' => storage_path('logs/paypal.log'),
        'log.LogLevel' => 'INFO'
    ]);

    return $this->apiContext;
}
```

新版：
```php
public function getClient()
{
    if ($this->client !== null) {
        return $this->client;
    }

    // 创建客户端
    $this->client = PaypalServerSdkClientBuilder::init()
        ->clientCredentialsAuthCredentials(
            ClientCredentialsAuthCredentialsBuilder::init(
                $this->clientId,
                $this->clientSecret
            )
        )
        ->environment($this->sandbox ? Environment::SANDBOX : Environment::PRODUCTION)
        ->loggingConfiguration(
            LoggingConfigurationBuilder::init()
                ->level(LogLevel::INFO)
                ->requestConfiguration(RequestLoggingConfigurationBuilder::init()->body(true))
                ->responseConfiguration(ResponseLoggingConfigurationBuilder::init()->headers(true))
        )
        ->build();

    return $this->client;
}
```

#### 2.3 更新 Webhook 验证

由于新版 SDK 不再直接支持 Webhook 验证，我们需要手动实现：

```php
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
```

#### 2.4 更新支付相关方法

##### 创建支付

旧版：
```php
public function createPayment($paymentData)
{
    try {
        $payment = new \PayPal\Api\Payment();
        $payment->fromArray($paymentData);
        $payment->create($this->getApiContext());
        return $payment;
    } catch (\Exception $e) {
        app('log')->error('PayPal 创建支付失败: ' . $e->getMessage());
        throw $e;
    }
}
```

新版：
```php
public function createPayment($paymentData)
{
    try {
        $client = $this->getClient();
        $ordersController = $client->getOrdersController();
        
        $collect = [
            'body' => $paymentData,
            'prefer' => 'return=representation'
        ];
        
        $response = $ordersController->createOrder($collect);
        
        if ($response->isSuccess()) {
            return $response->getResult();
        } else {
            app('log')->error('PayPal 创建支付失败: ' . json_encode($response->getBody()));
            throw new BadRequestHttpException('PayPal 创建支付失败');
        }
    } catch (\Exception $e) {
        app('log')->error('PayPal 创建支付失败: ' . $e->getMessage());
        throw $e;
    }
}
```

##### 执行支付

旧版：
```php
public function executePayment($paymentId, $payerId)
{
    try {
        $payment = \PayPal\Api\Payment::get($paymentId, $this->getApiContext());
        
        $execution = new \PayPal\Api\PaymentExecution();
        $execution->setPayerId($payerId);
        
        $result = $payment->execute($execution, $this->getApiContext());
        return $result;
    } catch (\Exception $e) {
        app('log')->error('PayPal 执行支付失败: ' . $e->getMessage());
        throw $e;
    }
}
```

新版：
```php
public function executePayment($paymentId, $payerId)
{
    try {
        $client = $this->getClient();
        $ordersController = $client->getOrdersController();
        
        $collect = [
            'id' => $paymentId,
            'prefer' => 'return=representation'
        ];
        
        $response = $ordersController->captureOrder($collect);
        
        if ($response->isSuccess()) {
            return $response->getResult();
        } else {
            app('log')->error('PayPal 执行支付失败: ' . json_encode($response->getBody()));
            throw new BadRequestHttpException('PayPal 执行支付失败');
        }
    } catch (\Exception $e) {
        app('log')->error('PayPal 执行支付失败: ' . $e->getMessage());
        throw $e;
    }
}
```

##### 退款

旧版：
```php
public function refund($saleId, $amount, $currency = 'USD')
{
    try {
        $sale = new \PayPal\Api\Sale();
        $sale->setId($saleId);
        
        $refundRequest = new \PayPal\Api\RefundRequest();
        $amountObj = new \PayPal\Api\Amount();
        $amountObj->setCurrency($currency)
            ->setTotal($amount);
        
        $refundRequest->setAmount($amountObj);
        
        return $sale->refund($refundRequest, $this->getApiContext());
    } catch (\Exception $e) {
        app('log')->error('PayPal 退款失败: ' . $e->getMessage());
        throw $e;
    }
}
```

新版：
```php
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
```

### 3. 更新 PaypalService 类

由于 PaypalService 类主要是通过 PaypalManager 类调用 PayPal API，所以只需要确保 PaypalService 类中调用 PaypalManager 类的方法名称和参数正确即可。

### 4. 更新配置文件

确保 `config/paypal.php` 文件包含所有必要的配置项：

```php
<?php

return [
    // PayPal API 凭证
    'client_id' => env('PAYPAL_CLIENT_ID', ''),
    'client_secret' => env('PAYPAL_SECRET', ''),
    
    // 环境设置
    'sandbox' => env('PAYPAL_SANDBOX', true),
    
    // Webhook 配置
    'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),
    
    // 货币设置
    'currency' => env('PAYPAL_CURRENCY', 'USD'),
    
    // 回调 URL
    'return_url' => env('PAYPAL_RETURN_URL', '/payment/paypal/success'),
    'cancel_url' => env('PAYPAL_CANCEL_URL', '/payment/paypal/cancel'),
    'webhook_url' => env('PAYPAL_WEBHOOK_URL', '/payment/paypal/webhook'),
    
    // 前端跳转 URL
    'success_url' => env('PAYPAL_SUCCESS_URL', '/payment/success'),
    'fail_url' => env('PAYPAL_FAIL_URL', '/payment/failed'),
    'cancelled_url' => env('PAYPAL_CANCELLED_URL', '/payment/cancelled'),
    
    // 日志设置
    'log_enabled' => env('PAYPAL_LOG_ENABLED', true),
    'log_level' => env('PAYPAL_LOG_LEVEL', 'INFO'),
    'log_file' => storage_path('logs/paypal.log'),
];
```

## 注意事项

1. **API 结构变化**：新版 SDK 使用了不同的 API 结构，需要通过控制器访问 API。

2. **支付流程变化**：
   - 旧版：创建 Payment -> 执行 Payment
   - 新版：创建 Order -> 捕获 Order

3. **退款流程变化**：
   - 旧版：通过 Sale ID 退款
   - 新版：通过 Capture ID 退款

4. **Webhook 验证**：新版 SDK 不再直接支持 Webhook 验证，需要手动实现。

5. **错误处理**：新版 SDK 使用 `isSuccess()` 方法检查响应是否成功，使用 `getResult()` 获取结果，使用 `getBody()` 获取原始响应。

6. **参数格式**：新版 SDK 的参数格式有所变化，需要使用 `collect` 数组传递参数。

## 测试步骤

1. 确保已安装新版 SDK：`composer require paypal/paypal-server-sdk:1.1.0`
2. 更新 PaypalManager 类
3. 测试基本功能：
   - 创建支付
   - 执行支付
   - 查询支付
   - 退款
   - Webhook 验证
4. 检查日志，确保没有错误 