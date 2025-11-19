# ShopexAI Bundle

本Bundle为shopex提供AI文章生成和图片生成功能，适用于PHP 7.4及以上版本。

## PHP 7.4 适配说明


### 使用注意事项

1. PHP 7.4 不支持联合类型声明（Union Types），如果后续开发中需要使用，请改用文档注释（PHPDoc）的方式
2. PHP 7.4 不支持构造函数属性提升（Constructor Property Promotion），需要分开声明属性和构造函数参数
3. PHP 7.4 不支持命名参数（Named Arguments），调用函数时请使用位置参数
4. PHP 7.4 不支持match表达式，请使用switch-case语句
5. PHP 7.4 支持空值合并赋值运算符（??=）、箭头函数，可以继续使用

## 功能介绍

ShopexAI Bundle 提供以下核心功能：

1. 通过DeepseekAPI自动生成商品软文内容
2. 通过阿里云通义万相API自动生成商品相关图片
3. 支持多商品批量生成内容和图片
4. 支持自定义提示词和参数配置
5. 支持流式响应和异步队列处理

## 主要组件

- ArticleService: 提供文章生成和保存功能
- AliyunImageService: 提供图片生成功能
- DeepseekService: 提供AI文本生成功能
- PromptService: 提供提示词构建功能

## 接口说明

主要API接口位于 Http/Api/V1/Action/ArticleController.php 中，提供了多种文章生成方式：

1. 同步生成接口
2. 流式响应接口
3. 异步生成接口（队列）

详细接口说明请参考Swagger文档。

## 功能特性

- 使用Deepseek API生成软文内容
- 使用阿里云通义万相-文生图V2生成配图
- 支持流式响应和非流式生成
- 支持缓存和队列异步处理
- 图片生成失败时使用默认图片
- 支持选择性生成文章和图片
- 支持将生成内容自动转换为文章管理系统格式

## 安装

1. 在`composer.json`中添加依赖：
```json
{
    "require": {
        "shopex/ai-bundle": "dev-master"
    }
}
```

2. 注册服务提供者，在`bootstrap/app.php`中添加：
```php
$app->register(ShopexAIBundle\Providers\ShopexAIServiceProvider::class);
```

3. 添加配置，在`.env`中添加：
```env
# Deepseek API配置（文本生成）
DEEPSEEK_API_KEY=your_api_key
DEEPSEEK_API_ENDPOINT=https://api.deepseek.com/v1/chat/completions


# 阿里云百炼API配置（图片生成）
ALIYUN_BAILIAN_API_KEY=
ALIYUN_BAILIAN_ENDPOINT=https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis

# 可选配置
SHOPEX_AI_CACHE_TTL=60
SHOPEX_AI_USE_QUEUE=true
SHOPEX_AI_QUEUE_NAME=slow
```

## 图片生成

本Bundle使用阿里云通义万相-文生图V2版API生成图片，支持以下特点：

- 高质量图片生成，使用最新的wanx2.1-t2i-turbo模型
- 支持多种风格设置
- 支持1024*1024图片尺寸
- 更低的生成延迟
- 强健的错误处理机制，当API调用失败时提供默认图片
- 高效的异步任务处理，避免请求超时

图片生成采用阿里云官方推荐的异步处理流程：
1. 首先创建图片生成任务，获取任务ID
2. 采用轮询方式查询任务状态，直到任务完成
3. 获取最终生成的图片URL

您可以通过修改提示词（prompt）来控制生成图片的内容和风格。系统使用prompt_extend=true自动优化您的提示词，获得更好的图片效果。

### 错误处理

图片生成服务具有强健的错误处理机制：
- 创建任务失败时自动使用配置的默认图片
- 任务查询超时或失败时使用默认图片
- 通过`is_default_image`字段标识是否使用了默认图片
- 错误信息会记录到日志并在响应中返回
- 可通过环境变量自定义默认图片URL：`ALIYUN_DEFAULT_IMAGE_URL`
- 支持查看原始和优化后的提示词，帮助诊断生成问题

## 使用方法

### API接口

#### 1. 直接生成（非流式）

```http
POST /api/article/generate-direct
Content-Type: application/json

{
    "prompt": "请生成一篇关于人工智能的软文",
    "image_prompt": "AI technology, futuristic, blue light",
    "is_article": true,
    "is_image": true
}
```

也可以使用结构化参数：

```http
POST /api/article/generate-direct
Content-Type: application/json

{
    "product": {
        "category": "运动鞋",
        "name": "Nike Air Max 270",
        "price": "¥1299",
        "params": "鞋底高度3cm，网面材质，橡胶大底"
    },
    "author_persona": "运动达人",
    "industry_presets": {
        "fabric": "网面材质",
        "breathability": "优良",
        "style": "运动休闲"
    },
    "subject_desc": "产品测评",
    "is_article": true,
    "is_image": true
}
```

#### 参数说明

- `is_article`: 是否生成文章内容，布尔值，默认为`true`
- `is_image`: 是否生成配图，布尔值，默认为`false`

这两个参数可以组合使用，例如：
- 同时生成文章和图片：`is_article=true, is_image=true`
- 只生成文章不生成图片：`is_article=true, is_image=false`
- 只生成图片不生成文章：`is_article=false, is_image=true`

当启用队列时，响应示例：
```json
{
    "message": "内容生成任务已加入队列，请稍后查询结果",
    "processing": true,
    "cache_key": "article_gen:a1b2c3d4e5f6",
    "async": true
}
```

#### 2. 查询生成状态（仅适用于队列处理模式）

```http
GET /api/article/check-status?cache_key=article_gen:a1b2c3d4e5f6
```

响应示例（处理中）：
```json
{
    "message": "内容正在生成中，请稍等片刻",
    "processing": true,
    "cache_key": "article_gen:a1b2c3d4e5f6"
}
```

响应示例（已完成）：
```json
{
    "article": "生成的文章内容...",
    "image": "https://image-url.com/xxx.jpg",
    "token_count": 1250,
    "job_completed": true,
    "completed_at": "2023-08-01 12:34:56",
    "processing": false
}
```

#### 3. 直接生成（流式）

```http
POST /api/article/generate-direct-stream
Content-Type: application/json

{
    "prompt": "请生成一篇关于人工智能的软文",
    "image_prompt": "AI technology, futuristic, blue light"
}
```

响应为Server-Sent Events (SSE)流，前端可以实时接收生成内容。

#### 4. 保存内容到文章管理系统

生成的内容可以通过系统自带的文章管理API保存到数据库：

```http
POST /api/article/management
Content-Type: application/json
Authorization: Bearer your_jwt_token

{
    "title": "Nike Air Max 270测评：透气如风，踩云而行的运动休闲利器",
    "content": [
        {
            "name": "writing",
            "base": {
                "title": "Nike Air Max 270测评：透气如风，踩云而行的运动休闲利器",
                "subtitle": "",
                "padded": true
            },
            "config": {
                "align": "left"
            },
            "data": [
                {
                    "content": "文章内容..."
                }
            ]
        },
        {
            "name": "slider",
            "base": {
                "title": "",
                "subtitle": "",
                "padded": true
            },
            "config": {
                "current": 0,
                "interval": 3000,
                "spacing": 0,
                "height": 140,
                "dot": false,
                "dotLocation": "right",
                "dotColor": "dark",
                "shape": "circle",
                "numNavShape": "rect",
                "dotCover": true,
                "rounded": false,
                "padded": false,
                "content": false
            },
            "data": [
                {
                    "imgUrl": "生成的图片URL",
                    "linkPage": "",
                    "content": "",
                    "title": "",
                    "id": ""
                }
            ]
        }
    ],
    "summary": "",
    "article_type": "bring",
    "category_id": 3
}
```

### 代码集成

您也可以在代码中直接调用现有的文章API：

```php
// 使用PHP Request直接调用现有API
$articlePostData = [
    'title' => '标题',
    'content' => [...], // 按上面的格式构建
    'article_type' => 'bring',
    'category_id' => 3
];

$request = new Request($articlePostData);
$articleController = app(\CompanysBundle\Http\Api\V1\Action\ArticleController::class);
$response = $articleController->createDataArticle($request);
$result = json_decode($response->getContent(), true);
```

## 配置选项

### 缓存设置
- `SHOPEX_AI_CACHE_TTL`: 缓存有效期（秒），默认为60秒

### 队列设置
- `SHOPEX_AI_USE_QUEUE`: 是否使用队列处理非流式生成，默认为true
- `SHOPEX_AI_QUEUE_NAME`: 队列名称，默认为article_generation

### 图片生成设置
- `ALIYUN_BAILIAN_API_KEY`: 阿里云通义万相API密钥
- `ALIYUN_BAILIAN_ENDPOINT`: 阿里云通义万相API端点URL
- `ALIYUN_DEFAULT_IMAGE_URL`: 当图片生成失败时使用的默认图片URL

启用队列时，需要确保队列工作进程正在运行：
```bash
php artisan queue:work --queue=article_generation
```

## 响应结构

图片生成部分的响应结构包含以下字段：

```json
{
  "url": "https://dashscope-result-xxx.oss-cn-xxx.aliyuncs.com/xxx.png",
  "is_default_image": false,
  "task_id": "d492bffd-10b5-4169-b639-xxxxxx",
  "actual_prompt": "优化后的提示词...",
  "model": "wanx2.1-t2i-turbo",
  "usage": {
    "image_count": 1
  }
}
```

- `url`: 生成图片的URL或默认图片URL
- `is_default_image`: 是否使用了默认图片
- `task_id`: 阿里云任务ID（仅成功生成时提供）
- `actual_prompt`: 实际使用的优化后提示词（当prompt_extend=true时）
- `model`: 使用的模型
- `usage`: 资源使用情况

## 依赖

- PHP >= 8.2
- guzzlehttp/guzzle >= 7.0 

## ShopexAI 模块

ShopexAI 模块用于生成AI内容，包括文章和图片。

### API 文档

#### 1. 直接生成文章内容

> POST /api/shopexai/article/direct

可用于一次性生成文章内容，支持可选生成配图。

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
| ------ | ---- | ---- | ---- |
| prompt | string | 否 | 用户提供的提示词，将与系统模板结合生成完整提示词 |
| industry | string | 否 | 行业类型，如"餐饮"、"美妆"等 |
| product | string | 否 | 产品名称 |
| keywords | array | 否 | 关键词列表 |
| style | string | 否 | 文章风格，如"专业"、"轻松" |
| is_article | boolean | 否 | 是否生成文章内容，默认为 true |
| is_image | boolean | 否 | 是否生成配图，默认为 false |
| auto_save | boolean | 否 | 是否自动保存到文章系统，默认为 false |
| category_id | integer | 否 | 文章分类ID，仅在auto_save为true时有效，默认为3 |
| author | string | 否 | 文章作者，仅在auto_save为true时有效 |
| head_portrait | string | 否 | 作者头像URL，仅在auto_save为true时有效 |
| use_queue | boolean | 否 | 是否使用队列异步生成(仅在系统配置允许的情况下生效) |

**返回参数：**

| 参数名 | 类型 | 说明 |
| ------ | ---- | ---- |
| article | string | 生成的文章内容 |
| image | string | 生成的图片URL(如果requested) |
| is_default_image | boolean | 是否使用了默认图片 |
| token_count | integer | 生成消耗的token数量 |
| actual_prompt | string | 实际用于图像生成的提示词 |
| processing | boolean | 是否正在处理中(异步模式) |
| cache_key | string | 缓存键(用于查询异步结果) |
| from_cache | boolean | 是否来自缓存 |
| auto_saved | boolean | 是否已自动保存到文章系统 |
| article_id | integer | 保存的文章ID(仅在auto_save为true时有效) |

#### 2. 流式生成文章内容

> POST /api/shopexai/article/direct-stream

使用流式响应生成文章内容，支持可选生成配图。

**请求参数：**

与直接生成文章内容相同

**返回格式：**

服务器发送事件(SSE)格式，包含多个事件：

1. 状态更新事件：
```
data: {"type":"status","message":"状态信息"}
```

2. 内容块事件：
```
data: {"type":"chunk","chunk":"内容片段"}
```

3. 完整内容事件：
```
data: {"type":"content","content":"完整内容"}
```

4. 完成事件：
```
data: {"type":"complete","data":{"article":"文章内容","image":"图片URL",...}}
```

5. 错误事件：
```
data: {"type":"error","message":"错误信息"}
```

#### 3. 检查生成状态

> GET /api/shopexai/article/check

检查异步任务的生成状态。

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
| ------ | ---- | ---- | ---- |
| cache_key | string | 是 | 缓存键(异步生成返回的) |

**返回参数：**

| 参数名 | 类型 | 说明 |
| ------ | ---- | ---- |
| processing | boolean | 是否正在处理中 |
| found | boolean | 是否找到任务 |
| article | string | 生成的文章内容(如已完成) |
| image | string | 生成的图片URL(如已完成) |
| error | boolean | 是否发生错误 |
| message | string | 错误信息(如有) |
| token_count | integer | 生成消耗的token数量 |
| job_completed | boolean | 任务是否已完成 |
| auto_saved | boolean | 是否已自动保存到文章系统 |
| article_id | integer | 保存的文章ID(仅在auto_save为true时有效) |

### 使用示例

#### 同步生成文章和配图

```javascript
// 发起请求
const response = await fetch('/api/shopexai/article/direct', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product: "洁面乳",
    industry: "美妆",
    keywords: ["保湿", "补水", "温和"],
    style: "专业",
    is_image: true
  })
});

const result = await response.json();
console.log(result.article); // 文章内容
console.log(result.image);   // 图片URL
```

#### 异步生成文章和配图

```javascript
// 发起异步请求
const response = await fetch('/api/shopexai/article/direct', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product: "洁面乳",
    industry: "美妆",
    keywords: ["保湿", "补水", "温和"],
    style: "专业",
    is_image: true,
    use_queue: true
  })
});

const result = await response.json();
const cacheKey = result.cache_key;

// 定时查询结果
const checkResult = async () => {
  const checkResponse = await fetch(`/api/shopexai/article/check?cache_key=${cacheKey}`);
  const checkData = await checkResponse.json();
  
  if (!checkData.processing) {
    console.log(checkData.article); // 文章内容
    console.log(checkData.image);   // 图片URL
    clearInterval(intervalId);
  }
};

const intervalId = setInterval(checkResult, 2000);
```

#### 流式生成文章和配图

```javascript
// 创建 EventSource 连接
const eventSource = new EventSource('/api/shopexai/article/direct-stream', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product: "洁面乳",
    industry: "美妆",
    keywords: ["保湿", "补水", "温和"],
    style: "专业",
    is_image: true
  })
});

// 接收状态更新
eventSource.addEventListener('message', (event) => {
  const data = JSON.parse(event.data);
  
  switch (data.type) {
    case 'status':
      console.log('状态更新:', data.message);
      break;
    case 'chunk':
      console.log('收到内容片段:', data.chunk);
      break;
    case 'content':
      console.log('完整内容:', data.content);
      break;
    case 'complete':
      console.log('生成完成:', data.data);
      eventSource.close();
      break;
    case 'error':
      console.error('发生错误:', data.message);
      eventSource.close();
      break;
  }
});
```

#### 生成内容并自动保存到文章系统

```javascript
// 发起请求
const response = await fetch('/api/shopexai/article/direct', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product: "洁面乳",
    industry: "美妆",
    keywords: ["保湿", "补水", "温和"],
    style: "专业",
    is_image: true,
    auto_save: true,
    category_id: 3,
    author: "AI助手",
    head_portrait: "https://example.com/avatar.jpg"
  })
});

const result = await response.json();
console.log(result.article);    // 文章内容
console.log(result.image);      // 图片URL
console.log(result.auto_saved); // 是否已保存
console.log(result.article_id); // 保存的文章ID
``` 