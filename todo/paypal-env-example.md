# PayPal 环境变量配置示例

将以下环境变量添加到您的 `.env` 文件中，并根据您的 PayPal 应用配置进行修改：

```
# PayPal 配置
PAYPAL_CLIENT_ID=your_client_id_here
PAYPAL_SECRET=your_client_secret_here
PAYPAL_SANDBOX=true
PAYPAL_WEBHOOK_ID=your_webhook_id_here
PAYPAL_CURRENCY=USD

# PayPal 回调 URL
PAYPAL_RETURN_URL=/payment/paypal/success
PAYPAL_CANCEL_URL=/payment/paypal/cancel
PAYPAL_WEBHOOK_URL=/payment/paypal/webhook

# PayPal 日志设置
PAYPAL_LOG_ENABLED=true
PAYPAL_LOG_LEVEL=INFO
```

## 配置说明

### API 凭证
- `PAYPAL_CLIENT_ID`: 您的 PayPal 应用的 Client ID
- `PAYPAL_SECRET`: 您的 PayPal 应用的 Secret
- `PAYPAL_SANDBOX`: 是否使用沙盒环境（生产环境设置为 false）
- `PAYPAL_WEBHOOK_ID`: 您的 PayPal Webhook ID
- `PAYPAL_CURRENCY`: 默认货币，通常为 USD（美元）

### 回调 URL
- `PAYPAL_RETURN_URL`: 支付成功后的回调 URL
- `PAYPAL_CANCEL_URL`: 支付取消后的回调 URL
- `PAYPAL_WEBHOOK_URL`: Webhook 接收 URL

### 日志设置
- `PAYPAL_LOG_ENABLED`: 是否启用日志
- `PAYPAL_LOG_LEVEL`: 日志级别（INFO、DEBUG、ERROR 等）

## 注意事项

1. 在开发和测试阶段，建议将 `PAYPAL_SANDBOX` 设置为 `true`，使用 PayPal 沙盒环境
2. 在生产环境中，将 `PAYPAL_SANDBOX` 设置为 `false`，并确保使用生产环境的 API 凭证
3. 确保回调 URL 可以从外网访问，特别是 Webhook URL
4. 生产环境中建议将 `PAYPAL_LOG_LEVEL` 设置为 `INFO` 或 `ERROR`，以减少日志量 