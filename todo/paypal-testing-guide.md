# PayPal 支付集成测试指南

本文档提供了 PayPal 支付集成的测试指南，包括沙箱环境测试和生产环境配置。

## 沙箱环境测试

### 1. 创建 PayPal 开发者账号

1. 访问 [PayPal 开发者网站](https://developer.paypal.com/)
2. 点击右上角的 "Log in to Dashboard"，使用您的 PayPal 账号登录或注册新账号
3. 登录后，您将进入 PayPal 开发者控制台

### 2. 创建沙箱测试账号

1. 在开发者控制台中，点击左侧菜单的 "Sandbox" > "Accounts"
2. 点击 "Create account" 按钮
3. 创建两种类型的账号：
   - **Business 账号**：作为商家接收付款
   - **Personal 账号**：作为买家进行支付测试
4. 记录下这些测试账号的邮箱和密码，以便后续测试使用

### 3. 创建 PayPal 应用

1. 在开发者控制台中，点击左侧菜单的 "My Apps & Credentials"
2. 在 "REST API apps" 部分，点击 "Create App" 按钮
3. 输入应用名称（如 "ECSHOPX PayPal Integration"）
4. 选择刚才创建的商家账号作为 Sandbox Business Account
5. 点击 "Create App" 按钮完成创建
6. 创建完成后，您将获得 Client ID 和 Secret，记录这些信息用于配置

### 4. 配置 Webhook

1. 在应用详情页面，滚动到 "Webhooks" 部分
2. 点击 "Add Webhook" 按钮
3. 输入您的 Webhook URL（例如：`https://your-domain.com/payment/paypal/webhook`）
4. 选择需要接收的事件类型，建议至少选择以下事件：
   - PAYMENT.SALE.COMPLETED
   - PAYMENT.SALE.REFUNDED
   - PAYMENT.SALE.REVERSED
5. 点击 "Save" 按钮保存配置
6. 记录生成的 Webhook ID，这将用于验证 Webhook 请求

### 5. 配置项目

1. 在项目中安装 PayPal PHP SDK：
   ```bash
   composer require paypal/rest-api-sdk-php
   ```

2. 在项目中配置 PayPal 参数：
   - Client ID
   - Client Secret
   - Sandbox 模式（设置为 true）
   - Webhook ID

3. 确保 PayPal 回调 URL 已正确配置在路由中

### 6. 测试支付流程

1. **基本支付流程测试**：
   - 创建订单并选择 PayPal 支付方式
   - 点击支付按钮，应该会重定向到 PayPal 登录页面
   - 使用之前创建的沙箱个人账号登录
   - 确认支付
   - 验证是否成功重定向回商店并显示支付成功信息
   - 检查订单状态是否已更新为已支付

2. **取消支付测试**：
   - 在 PayPal 支付页面点击取消按钮
   - 验证是否成功重定向回商店并显示取消支付信息
   - 检查订单状态是否仍为未支付

3. **退款测试**：
   - 对已完成的订单发起退款
   - 验证退款是否成功处理
   - 检查退款状态是否正确更新

4. **Webhook 测试**：
   - 在 PayPal 开发者控制台中，找到您的应用
   - 点击 "Webhooks" 部分的 "Simulator"
   - 选择要模拟的事件类型（如 PAYMENT.SALE.COMPLETED）
   - 输入相关参数
   - 点击 "Send test webhook" 按钮
   - 验证您的系统是否正确处理了 Webhook 请求

### 7. 常见问题排查

1. **支付创建失败**：
   - 检查 PayPal 配置是否正确（Client ID 和 Secret）
   - 查看日志文件 `storage/logs/paypal.log` 获取详细错误信息

2. **回调处理失败**：
   - 确保回调 URL 可以从外网访问
   - 检查 PayerID 和 PaymentID 是否正确传递

3. **Webhook 验证失败**：
   - 确保 Webhook ID 配置正确
   - 检查 Webhook URL 是否可以从外网访问
   - 验证请求头中的签名信息是否完整

## 生产环境配置

### 1. 创建生产应用

1. 在 PayPal 开发者控制台中，点击左侧菜单的 "My Apps & Credentials"
2. 切换到 "Live" 标签
3. 点击 "Create App" 按钮
4. 输入应用名称（如 "ECSHOPX PayPal Integration"）
5. 点击 "Create App" 按钮完成创建
6. 记录生产环境的 Client ID 和 Secret

### 2. 配置生产 Webhook

1. 在生产应用详情页面，滚动到 "Webhooks" 部分
2. 点击 "Add Webhook" 按钮
3. 输入您的生产环境 Webhook URL
4. 选择需要接收的事件类型（与沙箱环境相同）
5. 点击 "Save" 按钮保存配置
6. 记录生成的 Webhook ID

### 3. 更新项目配置

1. 更新项目中的 PayPal 配置：
   - 替换为生产环境的 Client ID 和 Secret
   - 将 Sandbox 模式设置为 false
   - 更新为生产环境的 Webhook ID

2. 确保所有 URL 都使用 HTTPS 协议

3. 确保日志级别适合生产环境（建议设置为 INFO 或 ERROR）

### 4. 安全检查清单

在部署到生产环境前，请确保完成以下安全检查：

1. **SSL 证书**：确保网站使用有效的 SSL 证书，所有支付相关页面都通过 HTTPS 访问

2. **敏感信息保护**：
   - Client Secret 不应该出现在客户端代码中
   - 支付相关日志不应包含敏感信息
   - Redis 中存储的配置信息应该加密

3. **错误处理**：
   - 生产环境中不应该向用户显示详细的错误信息
   - 所有异常应该被记录到日志中，但向用户展示友好的错误消息

4. **防重复提交**：
   - 实现防重复提交机制，避免重复支付
   - 使用交易 ID 确保每笔交易只处理一次

5. **数据验证**：
   - 验证所有来自 PayPal 的回调数据
   - 验证金额是否与订单金额匹配
   - 验证货币类型是否正确

### 5. 上线前最终测试

1. 使用生产配置在测试环境进行最终测试
2. 执行小额真实支付测试，验证整个流程
3. 测试退款流程
4. 确认 Webhook 能够正常接收和处理

### 6. 监控和维护

1. 设置监控系统，监控支付流程的关键指标：
   - 支付成功率
   - 支付处理时间
   - 错误率

2. 定期检查 PayPal 开发者文档，了解 API 变更和安全更新

3. 定期检查 PayPal 账户状态和交易记录，确保与系统记录一致

## 常见错误代码和解决方案

| 错误代码 | 描述 | 解决方案 |
|---------|------|---------|
| VALIDATION_ERROR | 请求参数验证失败 | 检查请求参数是否符合 PayPal API 要求 |
| INVALID_REQUEST | 无效的请求 | 检查请求格式和内容是否正确 |
| AUTHENTICATION_FAILURE | 认证失败 | 检查 Client ID 和 Secret 是否正确 |
| AUTHORIZATION_ERROR | 授权失败 | 检查应用权限是否配置正确 |
| EXPIRED_CREDIT_CARD | 信用卡已过期 | 通知用户更新支付方式 |
| INSUFFICIENT_FUNDS | 资金不足 | 通知用户选择其他支付方式 |
| INTERNAL_SERVICE_ERROR | PayPal 内部服务错误 | 稍后重试或联系 PayPal 支持 |

## 参考资料

1. [PayPal 开发者文档](https://developer.paypal.com/docs/api/overview/)
2. [PayPal REST API 参考](https://developer.paypal.com/api/rest/)
3. [PayPal PHP SDK 文档](https://github.com/paypal/PayPal-PHP-SDK/wiki)
4. [PayPal Webhook 指南](https://developer.paypal.com/docs/api-basics/notifications/webhooks/) 