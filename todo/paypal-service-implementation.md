# PayPal 支付服务实现详细设计

本文档详细描述了 PayPal 支付服务类的实现方案，包括关键方法的代码示例。

## PaypalService 类设计

`PaypalService` 类将实现 `Payment` 接口，提供 PayPal 支付的核心功能。

### 类结构

```php
<?php

namespace PaymentBundle\Services\Payments;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Refund;
use PayPal\Api\RefundRequest;
use PayPal\Api\Sale;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PaymentBundle\Interfaces\Payment as PaymentInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaypalService implements PaymentInterface
{
    private $distributorId = 0; // 店铺ID
    private $getDefault = true; // 是否取平台默认配置
    private $apiContext = null;

    public function __construct($distributorId = 0, $getDefault = true)
    {
        $this->distributorId = $distributorId;
        $this->getDefault = $getDefault;
    }

    // 接口方法实现...
}
```

### 配置管理方法

```php
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
public function getPaymentSetting($companyId)
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
 * 获取 PayPal API 上下文
 */
private function getApiContext($companyId)
{
    if ($this->apiContext !== null) {
        return $this->apiContext;
    }

    $paymentSetting = $this->getPaymentSetting($companyId);
    
    if (empty($paymentSetting) || empty($paymentSetting['client_id']) || empty($paymentSetting['client_secret'])) {
        throw new BadRequestHttpException('PayPal 配置信息不完整，请联系商家');
    }
    
    $this->apiContext = new ApiContext(
        new OAuthTokenCredential(
            $paymentSetting['client_id'],
            $paymentSetting['client_secret']
        )
    );
    
    // 设置配置参数
    $this->apiContext->setConfig([
        'mode' => isset($paymentSetting['sandbox']) && $paymentSetting['sandbox'] ? 'sandbox' : 'live',
        'log.LogEnabled' => true,
        'log.FileName' => storage_path('logs/paypal.log'),
        'log.LogLevel' => 'INFO'
    ]);
    
    return $this->apiContext;
}
```

### 支付相关方法

```php
/**
 * 预存款充值
 */
public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
{
    $passbackParams = [
        'company_id' => $data['company_id'],
        'pay_type' => 'paypal',
        'attach' => 'depositRecharge',
    ];
    
    $payer = new Payer();
    $payer->setPaymentMethod('paypal');
    
    $amount = new Amount();
    $amount->setCurrency('USD')
        ->setTotal(bcdiv($data['money'], 100, 2));
    
    $transaction = new Transaction();
    $transaction->setAmount($amount)
        ->setDescription($data['shop_name'] . '充值')
        ->setInvoiceNumber($data['deposit_trade_id'])
        ->setCustom(http_build_query($passbackParams));
    
    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl(url('/payment/paypal/success'))
        ->setCancelUrl(url('/payment/paypal/cancel'));
    
    $payment = new Payment();
    $payment->setIntent('sale')
        ->setPayer($payer)
        ->setTransactions([$transaction])
        ->setRedirectUrls($redirectUrls);
    
    try {
        $payment->create($this->getApiContext($data['company_id']));
        
        return [
            'pay_type' => 'paypal',
            'pay_info' => $payment->toArray(),
            'pay_url' => $payment->getApprovalLink(),
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
    $passbackParams = [
        'company_id' => $data['company_id'],
        'pay_type' => 'paypal',
        'attach' => 'orderPay',
    ];
    
    $payer = new Payer();
    $payer->setPaymentMethod('paypal');
    
    // 创建商品列表
    $itemList = new ItemList();
    $items = [];
    
    // 如果有详细商品信息，可以添加到 items 中
    $item = new Item();
    $item->setName($data['body'])
        ->setCurrency('USD')
        ->setQuantity(1)
        ->setPrice(bcdiv($data['total_fee'], 100, 2));
    $items[] = $item;
    
    $itemList->setItems($items);
    
    $amount = new Amount();
    $amount->setCurrency('USD')
        ->setTotal(bcdiv($data['total_fee'], 100, 2));
    
    $transaction = new Transaction();
    $transaction->setAmount($amount)
        ->setItemList($itemList)
        ->setDescription($data['detail'])
        ->setInvoiceNumber($data['order_id'])
        ->setCustom(http_build_query($passbackParams));
    
    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl(url('/payment/paypal/success'))
        ->setCancelUrl(url('/payment/paypal/cancel'));
    
    $payment = new Payment();
    $payment->setIntent('sale')
        ->setPayer($payer)
        ->setTransactions([$transaction])
        ->setRedirectUrls($redirectUrls);
    
    try {
        $payment->create($this->getApiContext($data['company_id']));
        
        return [
            'pay_type' => 'paypal',
            'pay_info' => $payment->toArray(),
            'pay_url' => $payment->getApprovalLink(),
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
    $apiContext = $this->getApiContext($companyId);
    
    try {
        $sale = new Sale();
        $sale->setId($data['transaction_id']);
        
        $refundRequest = new RefundRequest();
        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal(bcdiv($data['refund_fee'], 100, 2));
        
        $refundRequest->setAmount($amount);
        
        $refund = $sale->refund($refundRequest, $apiContext);
        
        return [
            'refund_id' => $refund->getId(),
            'refund_status' => $refund->getState(),
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
        $payment = Payment::get($data['transaction_id'], $this->getApiContext($data['company_id']));
        
        $status = 'FAIL';
        if ($payment->getState() === 'approved') {
            $status = 'SUCCESS';
        }
        
        return [
            'status' => $status,
            'transaction_id' => $payment->getId(),
            'pay_type' => 'paypal',
        ];
    } catch (\Exception $e) {
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
        $payment = Payment::get($trade_id, $this->getApiContext($companyId));
        return $payment->toArray();
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * 获取退款订单状态信息
 */
public function getRefundOrderInfo($companyId, $data)
{
    try {
        $refund = Refund::get($data['refund_id'], $this->getApiContext($companyId));
        return $refund->toArray();
    } catch (\Exception $e) {
        return [];
    }
}
```

### 支付执行方法

```php
/**
 * 执行支付（支付成功回调时调用）
 */
public function executePayment($companyId, $paymentId, $payerId)
{
    $apiContext = $this->getApiContext($companyId);
    
    $payment = Payment::get($paymentId, $apiContext);
    
    $execution = new PaymentExecution();
    $execution->setPayerId($payerId);
    
    try {
        $result = $payment->execute($execution, $apiContext);
        
        // 获取交易信息
        $transactions = $result->getTransactions();
        $transaction = $transactions[0];
        $invoiceNumber = $transaction->getInvoiceNumber();
        
        // 解析自定义参数
        $custom = [];
        parse_str($transaction->getCustom(), $custom);
        
        return [
            'status' => 'SUCCESS',
            'transaction_id' => $result->getId(),
            'invoice_number' => $invoiceNumber,
            'custom' => $custom,
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'FAIL',
            'error_msg' => $e->getMessage(),
        ];
    }
}
```

## PaypalManager 类设计

`PaypalManager` 类将封装 PayPal API 的调用，提供更高级别的抽象。

### 类结构

```php
<?php

namespace PaymentBundle\Manager;

use PayPal\Api\WebhookEvent;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\VerifyWebhookSignature;
use PayPal\Api\WebhookEventType;

class PaypalManager
{
    private $apiContext = null;
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
     * 获取 API 上下文
     */
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

    /**
     * 验证 Webhook 签名
     */
    public function verifyWebhook($headers, $body)
    {
        $signatureVerification = new VerifyWebhookSignature();
        $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
        $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
        $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
        $signatureVerification->setWebhookId(config('paypal.webhook_id'));
        $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
        $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);

        $webhookEvent = new WebhookEvent();
        $webhookEvent->fromJson($body);
        $signatureVerification->setWebhookEvent($webhookEvent);

        try {
            $output = $signatureVerification->post($this->getApiContext());
            return $output->getVerificationStatus() === 'SUCCESS';
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

## PaypalNotify 控制器设计

`PaypalNotify` 控制器将处理 PayPal 的回调和 Webhook 请求。

### 类结构

```php
<?php

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
        $paymentId = $request->input('paymentId');
        $payerId = $request->input('PayerID');
        
        if (empty($paymentId) || empty($payerId)) {
            return redirect('/payment/failed');
        }
        
        // 根据 paymentId 查询交易信息
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['transaction_id' => $paymentId]);
        
        if (!$tradeInfo) {
            return redirect('/payment/failed');
        }
        
        $companyId = $tradeInfo['company_id'];
        $distributorId = $tradeInfo['distributor_id'] ?? 0;
        
        $paypalService = new PaypalService($distributorId);
        $result = $paypalService->executePayment($companyId, $paymentId, $payerId);
        
        if ($result['status'] === 'SUCCESS') {
            $custom = $result['custom'] ?? [];
            
            if (isset($custom['attach']) && $custom['attach'] === 'depositRecharge') {
                $depositTrade = new DepositTrade();
                $options['pay_type'] = 'paypal';
                $options['transaction_id'] = $paymentId;
                $depositTrade->rechargeCallback($result['invoice_number'], 'SUCCESS', $options);
            } else {
                $options['pay_type'] = 'paypal';
                $options['transaction_id'] = $paymentId;
                $tradeService->updateStatus($result['invoice_number'], 'SUCCESS', $options);
            }
            
            return redirect('/payment/success');
        } else {
            return redirect('/payment/failed');
        }
    }
    
    /**
     * 处理 PayPal Webhook 事件
     */
    public function webhook(Request $request)
    {
        $headers = getallheaders();
        $body = $request->getContent();
        
        // 验证 Webhook 签名
        $paypalManager = new PaypalManager(
            config('paypal.client_id'),
            config('paypal.client_secret'),
            config('paypal.sandbox')
        );
        
        if (!$paypalManager->verifyWebhook($headers, $body)) {
            return response()->json(['status' => 'VERIFICATION_FAILED'], 400);
        }
        
        $event = json_decode($body, true);
        $eventType = $event['event_type'] ?? '';
        
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
                
            // 其他事件类型...
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
        // 处理退款逻辑
    }
}
```

## 路由配置

在 `routes/web.php` 中添加以下路由：

```php
// PayPal 支付回调
$router->get('/payment/paypal/success', 'PaymentBundle\Http\Controllers\PaypalNotify@handle');
$router->get('/payment/paypal/cancel', function () {
    return redirect('/payment/cancelled');
});
$router->post('/payment/paypal/webhook', 'PaymentBundle\Http\Controllers\PaypalNotify@webhook');
```

## 更新 PaymentsService 类

在 `src/PaymentBundle/Services/PaymentsService.php` 中添加 PayPal 支付服务的实例化代码：

```php
// 在 doPayment 方法中添加
if ($data['pay_type'] == 'paypal') {
    $this->paymentService = new PaypalService($data['distributor_id'] ?? 0);
}
```

## 更新 Payment API 控制器

在 `src/PaymentBundle/Http/Api/V1/Action/Payment.php` 中添加 PayPal 配置接口：

```php
// 在 setPaymentSetting 方法中添加
elseif ($request->input('pay_type') == 'paypal') {
    $paymentsService = new PaypalService($distributorId);
    $config['client_id'] = $request->input('client_id');
    $config['client_secret'] = $request->input('client_secret');
    $config['sandbox'] = $request->input('sandbox') === 'true';
    $config['is_open'] = $request->input('is_open') === 'true';
}
```

## 前端集成示例

PayPal 前端集成代码示例：

```javascript
// 添加 PayPal SDK
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD"></script>

// 创建 PayPal 按钮
paypal.Buttons({
    createOrder: function(data, actions) {
        // 调用后端创建订单接口
        return fetch('/api/v1/payment/create', {
            method: 'post',
            headers: {
                'content-type': 'application/json'
            },
            body: JSON.stringify({
                order_id: 'ORDER_ID',
                pay_type: 'paypal'
            })
        }).then(function(res) {
            return res.json();
        }).then(function(data) {
            // 从响应中提取 PayPal 订单 ID
            return data.pay_info.id;
        });
    },
    onApprove: function(data, actions) {
        // 支付成功后的处理
        window.location.href = '/payment/success?paymentId=' + data.paymentID + '&PayerID=' + data.payerID;
    }
}).render('#paypal-button-container');
``` 