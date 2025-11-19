# PayPal 开发者账号与沙盒环境设置指南

本文档提供了详细的 PayPal 开发者账号注册和沙盒环境设置步骤，帮助开发人员快速搭建 PayPal 支付测试环境。

## 一、注册 PayPal 开发者账号

### 1. 创建 PayPal 账号

如果您还没有 PayPal 账号，需要先创建一个：

1. 访问 [PayPal 官网](https://www.paypal.com/)
2. 点击右上角的"注册"按钮
3. 选择"个人账户"或"商家账户"（建议选择"商家账户"，因为开发者通常需要商家账户的功能）
4. 填写电子邮件地址、创建密码，然后点击"继续"
5. 填写您的个人信息（姓名、地址、电话号码等）
6. 验证您的电子邮件地址和手机号码
7. 添加您的银行卡或银行账户信息（可选，但建议添加以便完整体验 PayPal 功能）

### 2. 访问 PayPal 开发者平台

1. 使用您的 PayPal 账号登录 [PayPal 开发者平台](https://developer.paypal.com/)
2. 点击页面右上角的"Log in to Dashboard"按钮
3. 使用您的 PayPal 账号凭据登录
4. 首次登录时，系统会要求您接受开发者协议，请阅读并接受

### 3. 完善开发者资料

1. 登录成功后，您会进入开发者控制台
2. 点击右上角的个人资料图标，选择"Account Settings"
3. 完善您的开发者资料信息（可选）
4. 确认您的联系电子邮件是否正确，因为重要的开发者通知将发送到此邮箱

## 二、创建沙盒测试账号

PayPal 沙盒环境允许您测试支付流程，而无需使用真实资金。您需要创建两种类型的沙盒账号：商家账号（接收付款）和个人账号（付款）。

### 1. 创建沙盒商家账号

1. 在开发者控制台中，点击左侧导航栏中的"Sandbox" > "Accounts"
2. 点击"Create account"按钮
3. 在弹出的表单中填写以下信息：
   - Account Type: 选择 "Business"
   - Country: 选择您的业务所在国家/地区
   - Email Address: 系统会自动生成一个测试邮箱，您也可以自定义
   - Password: 设置一个容易记住的密码
   - Paypal Balance: 设置初始余额（建议设置足够大的金额，如 10000）
   - 其他选项可保持默认
4. 点击"Create Account"按钮完成创建

### 2. 创建沙盒个人账号

1. 在同一页面，再次点击"Create account"按钮
2. 这次选择 Account Type 为 "Personal"
3. 填写其他信息（与创建商家账号类似）
4. 点击"Create Account"按钮完成创建

### 3. 管理沙盒账号

创建完成后，您可以在账号列表中看到您创建的沙盒账号：

1. 记录下每个账号的电子邮件地址和密码，这些将用于测试支付流程
2. 您可以点击账号旁边的"View/edit account"按钮查看或编辑账号详情
3. 如需查看账号的信用卡信息，点击"View/edit account"，然后在"Funding"选项卡中查看

## 三、创建 PayPal REST API 应用

要使用 PayPal API 进行开发，您需要创建一个应用并获取 API 凭证。

### 1. 创建应用

1. 在开发者控制台中，点击左侧导航栏中的"My Apps & Credentials"
2. 在"REST API apps"部分，点击"Create App"按钮
3. 输入您的应用名称（例如"ECSHOPX PayPal Integration"）
4. 选择一个沙盒商家账号作为应用关联的账号
5. 点击"Create App"按钮

### 2. 获取 API 凭证

创建应用后，您将看到应用详情页面：

1. 记录下"Sandbox"部分中的"Client ID"和"Secret"，这些是 API 凭证
2. 这些凭证将用于您的代码中进行 API 认证

### 3. 配置应用设置

1. 在应用详情页面，您可以配置以下设置：
   - App settings: 设置应用名称和描述
   - Account settings: 管理与应用关联的 PayPal 账号
   - Sandbox webhooks: 配置测试环境的 webhook（事件通知）

## 四、配置 Webhook

Webhook 允许 PayPal 向您的服务器发送事件通知，例如当支付完成或退款处理时。

### 1. 添加 Webhook

1. 在应用详情页面，滚动到"Webhooks"部分
2. 点击"Add Webhook"按钮
3. 在"Webhook URL"字段中，输入您的服务器 URL（例如：`https://your-domain.com/payment/paypal/webhook`）
   - 注意：在开发阶段，您可能需要使用工具如 ngrok 创建一个公共 URL 指向您的本地服务器
4. 在"Event types"部分，选择您想要接收通知的事件类型，建议至少选择：
   - Payment sale completed
   - Payment sale refunded
   - Payment sale reversed

### 2. 验证 Webhook

1. 添加 Webhook 后，记录下生成的"Webhook ID"，这将用于验证接收到的 Webhook 请求
2. 您可以使用"Webhook simulator"测试 Webhook 配置是否正确：
   - 点击"Webhooks"部分中的"Simulate"按钮
   - 选择要模拟的事件类型
   - 输入必要的参数
   - 点击"Send test webhook"按钮
   - 检查您的服务器是否正确接收和处理了事件

## 五、测试沙盒环境

### 1. 登录沙盒账号

1. 访问 [PayPal 沙盒网站](https://www.sandbox.paypal.com/)
2. 使用您创建的沙盒个人账号登录
3. 熟悉沙盒环境的界面，它与实际的 PayPal 网站非常相似

### 2. 进行测试支付

1. 在您的应用中实现 PayPal 支付流程
2. 当重定向到 PayPal 支付页面时，使用沙盒个人账号登录
3. 确认支付
4. 验证您的应用是否正确处理了支付结果

### 3. 查看交易记录

1. 登录沙盒商家账号
2. 查看交易历史记录，确认测试支付是否正确记录
3. 尝试执行退款操作，测试退款流程

## 六、从沙盒过渡到生产环境

当您完成测试并准备在生产环境中使用 PayPal 时：

### 1. 创建生产应用

1. 在开发者控制台中，点击左侧导航栏中的"My Apps & Credentials"
2. 切换到"Live"标签
3. 点击"Create App"按钮
4. 输入应用名称并创建应用
5. 记录生产环境的"Client ID"和"Secret"

### 2. 配置生产 Webhook

1. 在生产应用详情页面，添加生产环境的 Webhook
2. 确保 URL 指向您的生产服务器
3. 选择相同的事件类型

### 3. 更新应用配置

1. 在您的应用中，将 API 凭证从沙盒环境更改为生产环境
2. 将 API 模式从"sandbox"更改为"live"
3. 更新 Webhook ID

## 七、常见问题与解决方案

### 1. 沙盒账号登录问题

**问题**：无法登录沙盒账号
**解决方案**：
- 确保您使用的是沙盒网站 (sandbox.paypal.com)
- 检查账号邮箱和密码是否正确
- 如果仍然无法登录，尝试重置密码或创建新的沙盒账号

### 2. API 认证失败

**问题**：API 请求返回认证错误
**解决方案**：
- 检查 Client ID 和 Secret 是否正确
- 确保您使用的是正确环境的凭证（沙盒或生产）
- 验证 API 请求格式是否正确

### 3. Webhook 未触发

**问题**：没有收到 Webhook 通知
**解决方案**：
- 确保 Webhook URL 可以从公网访问
- 检查服务器防火墙设置
- 使用 Webhook 模拟器测试配置
- 检查服务器日志，查看是否有接收到请求但处理失败

### 4. 沙盒支付失败

**问题**：沙盒环境中支付失败
**解决方案**：
- 确保沙盒个人账号有足够的余额
- 检查支付请求参数是否正确
- 查看 PayPal 开发者控制台中的错误日志

## 八、有用的资源

1. [PayPal 开发者文档](https://developer.paypal.com/docs/)
2. [PayPal REST API 参考](https://developer.paypal.com/api/rest/)
3. [PayPal PHP SDK 文档](https://github.com/paypal/PayPal-PHP-SDK/wiki)
4. [PayPal 开发者论坛](https://www.paypal-community.com/t5/PayPal-Developers/bd-p/DeveloperCommunity)
5. [PayPal 开发者技术支持](https://developer.paypal.com/support/)

---

按照本指南完成设置后，您应该已经拥有了一个功能完整的 PayPal 开发环境，可以开始集成和测试 PayPal 支付功能。如果遇到任何问题，请参考上述资源或联系 PayPal 开发者支持。 