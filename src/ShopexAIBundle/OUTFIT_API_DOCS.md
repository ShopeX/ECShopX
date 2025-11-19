# 虚拟试衣 API 文档

## 概述

虚拟试衣功能提供了AI驱动的服装试穿效果生成服务，支持同步和异步两种生成模式。

## API 接口

### 1. 生成虚拟试衣图片

**接口地址：** `POST /outfit/generate`

**功能描述：** 生成虚拟试衣图片，支持直接生成和异步生成两种模式

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| person_image_url | string | 是 | 人物图片URL |
| top_garment_url | string | 条件必填 | 上衣图片URL（与bottom_garment_url至少需要提供一个） |
| bottom_garment_url | string | 条件必填 | 下装图片URL（与top_garment_url至少需要提供一个） |

**参数验证规则：**
- `person_image_url`: 必填，必须是有效的URL格式
- `top_garment_url`: 与bottom_garment_url至少需要提供一个，必须是有效的URL格式  
- `bottom_garment_url`: 与top_garment_url至少需要提供一个，必须是有效的URL格式

**异步生成说明：**
是否使用异步生成由系统配置 `shopexai.outfit.use_queue` 控制，默认为 true。用户无需关心这个配置，系统会自动处理。

**响应示例：**

同步模式成功响应：
```json
{
    "code": 200,
    "message": "虚拟试衣生成成功",
    "data": {
        "url": "https://example.com/generated-image.jpg",
        "is_default": false,
        "model": "outfit-anyone-v1",
        "task_id": null
    }
}
```

异步模式响应：
```json
{
    "code": 200,
    "message": "虚拟试衣任务已创建，请使用任务ID查询结果",
    "data": {
        "task_id": "550e8400-e29b-41d4-a716-446655440000",
        "cache_key": "outfit_550e8400-e29b-41d4-a716-446655440000"
    }
}
```

### 2. 查询任务状态

**接口地址：** `GET /outfit/status/{task_id}`

**功能描述：** 查询异步生成的虚拟试衣任务状态和结果

**路径参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| task_id | string | 是 | 任务ID |

**响应示例：**

处理中：
```json
{
    "code": 200,
    "message": "任务正在处理中",
    "data": {
        "status": "processing",
        "created_at": "2024-01-01 12:00:00"
    }
}
```

已完成：
```json
{
    "code": 200,
    "message": "任务已完成",
    "data": {
        "status": "completed",
        "url": "https://example.com/generated-image.jpg",
        "is_default": false,
        "model": "outfit-anyone-v1",
        "created_at": "2024-01-01 12:00:00",
        "completed_at": "2024-01-01 12:01:30"
    }
}
```

失败：
```json
{
    "code": 200,
    "message": "任务执行失败",
    "data": {
        "status": "failed",
        "url": "https://example.com/default-image.jpg",
        "is_default": true,
        "created_at": "2024-01-01 12:00:00",
        "completed_at": "2024-01-01 12:01:30"
    }
}
```

## 错误响应

所有接口在出现错误时都会返回统一的错误格式：

```json
{
    "code": 400,
    "message": "错误信息描述"
}
```

常见错误码：
- `400`: 请求参数错误
- `404`: 任务不存在或已过期
- `500`: 服务器内部错误

## 参数验证功能

### validateParams 方法

新增的 `validateParams` 方法提供了统一的参数验证功能：

**功能特点：**
1. 使用Laravel的Validator进行参数验证
2. 支持自定义验证规则
3. 提供中文错误提示
4. 统一的错误处理和日志记录

**使用示例：**
```php
$validatedData = $this->validateParams($request, [
    'person_image_url' => 'required|url',
    'top_garment_url' => 'required|url',
    'bottom_garment_url' => 'nullable|url',
    'use_queue' => 'nullable|boolean'
]);

if (isset($validatedData['error'])) {
    return response()->json([
        'code' => 400,
        'message' => $validatedData['error']
    ], 400);
}
```

**支持的验证规则：**
- `required`: 必填字段
- `url`: URL格式验证
- `nullable`: 可为空
- `boolean`: 布尔值验证
- 其他Laravel支持的验证规则

## Swagger 文档

API接口已添加完整的Swagger文档注释，包括：
- 接口描述和标签
- 请求参数定义
- 响应格式说明
- 错误响应定义

可通过Swagger UI查看完整的API文档。 