# PayPal 支付集成实施计划

## 项目概述

为 ecshopx-api 项目集成 PayPal 支付功能，使系统能够支持国际支付。基于现有的支付架构，我们需要开发 PayPal 支付服务，实现支付、退款、查询等基本功能。

## 代码结构分析

通过分析现有的 PaymentBundle 代码结构，我们可以看到系统采用了以下架构：

1. **接口定义**：`PaymentBundle/Interfaces/Payment.php` 定义了支付服务必须实现的方法
2. **服务实现**：`PaymentBundle/Services/Payments/` 目录下包含各种支付方式的具体实现
3. **支付服务**：`PaymentBundle/Services/PaymentService.php` 和 `PaymentBundle/Services/PaymentsService.php` 处理支付流程
4. **控制器**：`PaymentBundle/Http/Controllers/PaymentNotify.php` 处理支付回调
5. **API接口**：`PaymentBundle/Http/Api/V1/Action/Payment.php` 和 `PaymentBundle/Http/FrontApi/V1/Action/Payment.php` 提供API接口

## 实施任务清单

### 1. 环境准备

- [ ] 注册 PayPal 开发者账号
- [ ] 创建 PayPal 应用并获取 API 凭证（Client ID 和 Secret）
- [ ] 安装 PayPal PHP SDK
  ```bash
  composer require paypal/rest-api-sdk-php
  ```

### 2. 实现 PayPal 支付服务类

- [ ] 创建 `src/PaymentBundle/Services/Payments/PaypalService.php` 实现 Payment 接口
  - [ ] 实现 `setPaymentSetting` 方法，用于保存 PayPal 配置
  - [ ] 实现 `getPaymentSetting` 方法，用于获取 PayPal 配置
  - [ ] 实现 `depositRecharge` 方法，用于储值卡充值
  - [ ] 实现 `doPay` 方法，用于创建支付
  - [ ] 实现 `doRefund` 方法，用于退款
  - [ ] 实现 `query` 方法，用于查询支付状态
  - [ ] 实现 `getPayOrderInfo` 和 `getRefundOrderInfo` 方法，用于获取订单信息

### 3. 创建 PayPal 支付管理类

- [ ] 创建 `src/PaymentBundle/Manager/PaypalManager.php` 用于封装 PayPal API 调用
  - [ ] 实现 `createPayment` 方法，创建 PayPal 支付
  - [ ] 实现 `executePayment` 方法，执行 PayPal 支付
  - [ ] 实现 `refundPayment` 方法，处理退款
  - [ ] 实现 `getPaymentDetails` 方法，获取支付详情
  - [ ] 实现 `verifyWebhook` 方法，验证 PayPal Webhook 请求

### 4. 添加 PayPal 支付回调控制器

- [ ] 创建 `src/PaymentBundle/Http/Controllers/PaypalNotify.php` 处理 PayPal 回调
  - [ ] 实现 `handle` 方法，处理 PayPal 支付成功回调
  - [ ] 实现 `webhook` 方法，处理 PayPal Webhook 事件

### 5. 修改现有代码以支持 PayPal

- [ ] 更新 `src/PaymentBundle/Services/PaymentService.php`，添加 PayPal 支付方式
- [ ] 更新 `src/PaymentBundle/Services/PaymentsService.php`，支持 PayPal 服务实例化
- [ ] 更新 `src/PaymentBundle/Http/Api/V1/Action/Payment.php`，添加 PayPal 配置接口
- [ ] 更新 `src/PaymentBundle/Http/FrontApi/V1/Action/Payment.php`，添加前端 PayPal 支付接口

### 6. 配置路由

- [ ] 添加 PayPal 回调路由到 `routes/web.php`
  ```php
  $router->post('/payment/paypal/notify', 'PaymentBundle\Http\Controllers\PaypalNotify@handle');
  $router->post('/payment/paypal/webhook', 'PaymentBundle\Http\Controllers\PaypalNotify@webhook');
  ```

### 7. 实现前端集成

- [ ] 创建 PayPal 支付按钮组件
- [ ] 实现 PayPal 支付流程前端交互

## 技术实现细节

### PayPal REST API 集成

PayPal 提供了 REST API，我们将使用官方 SDK 进行集成。主要流程如下：

1. **创建支付**：
   - 构建支付对象，包括金额、货币、描述等信息
   - 设置成功和取消回调 URL
   - 调用 PayPal API 创建支付，获取支付 URL
   - 重定向用户到 PayPal 支付页面

2. **执行支付**：
   - 用户在 PayPal 完成支付后，PayPal 重定向回我们的成功 URL
   - 获取 PaymentID 和 PayerID 参数
   - 调用 PayPal API 执行支付
   - 更新订单状态

3. **处理 Webhook**：
   - 配置 PayPal Webhook 以接收支付事件通知
   - 验证 Webhook 请求的真实性
   - 根据事件类型处理相应的业务逻辑

### 数据库设计

使用现有的交易表结构，确保能够存储 PayPal 特有的字段：

- `transaction_id`：PayPal 交易 ID
- `payer_id`：PayPal 付款人 ID
- `payment_method`：支付方式，值为 "paypal"

## 测试计划

1. **沙箱测试**：
   - 使用 PayPal 沙箱环境进行测试
   - 测试正常支付流程
   - 测试取消支付流程
   - 测试退款流程

2. **单元测试**：
   - 为 PaypalService 编写单元测试
   - 为 PaypalManager 编写单元测试

3. **集成测试**：
   - 测试整个支付流程
   - 测试 Webhook 处理

## 上线计划

1. **准备阶段**：
   - 完成所有代码开发和测试
   - 准备生产环境 PayPal 应用和凭证

2. **部署阶段**：
   - 部署代码到生产环境
   - 配置生产环境 PayPal Webhook

3. **监控阶段**：
   - 监控支付流程
   - 处理可能出现的问题

## 风险评估

1. **API 变更风险**：PayPal API 可能会有变更，需要及时关注官方文档更新
2. **安全风险**：支付相关功能需要特别注意安全性，确保数据加密和验证
3. **跨境支付风险**：涉及跨境支付，需要考虑汇率、税费等因素

## 参考资料

1. [PayPal REST API 文档](https://developer.paypal.com/docs/api/overview/)
2. [PayPal PHP SDK GitHub](https://github.com/paypal/PayPal-PHP-SDK)
3. [PayPal 开发者中心](https://developer.paypal.com/) 