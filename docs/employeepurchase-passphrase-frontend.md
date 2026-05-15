# 内购活动 · 口令通道 · 前端对接说明

本文说明：**批量生成口令码**、**创建/更新活动时提交口令企业配置** 的请求方式与示例，以及 **C 端（小程序/H5）行为流水上报**：**扫码/进入**、**口令校验**。

> **鉴权**：与其它管理端内购接口一致，请求头需带 JWT，例如：  
> `Authorization: Bearer <token>`  
> **基础路径**：以实际部署为准。常见为 `{站点}/api`，下文记为 `{API_BASE}`。  
> 若网关还有版本段（如 `/api/v1`），请将下列路径接在版本根路径之后。

> **C 端（frontapi）**：内购流水为 **单一接口** `.../activity/behavior-report`，挂在 Dingo `v1` + 前缀 **`h5app`** 下，例如：  
> `{FRONTAPI_BASE}/v1/h5app/wxapp/employeepurchase/activity/behavior-report`  
> `{FRONTAPI_BASE}` 与小程序/H5 现有域名一致（常见同 `{API_BASE}`）。若网关省略 `v1` 或合并前缀，以实际为准。

---

## 零、体系总览（完整说明）

本节从**产品/数据/接口/代码/部署**串起全文，便于评审与交接；细节仍分章展开。

### 0.1 能力边界（本文档覆盖什么）

| 能力 | 说明 |
|------|------|
| **管理端 · 口令配置** | 批量生成口令码（不落库）、创建/更新活动时提交 `passphrase_enterprises`、读活动详情中带出口令企业行 |
| **管理端 · 行为统计** | 按活动聚合各参与企业的扫码 PV/UV、口令验证成功人数(UV)、绑定/下单 UV 等 |
| **C 端 · 行为流水** | **一个 URL**：上报 **扫码/进入**、**口令校验**；口令每次尝试（成败）均落库并带 `result_status` |
| **C 端 · 员工绑定流水** | **`POST .../wxapp/employee/auth`**（需 JWT）：活动内员工身份绑定**成功**后，若请求携带 **`activity_id`** 且企业参与该活动，服务端写入 **`bind`** 流水（供 `bind_user_count`） |
| **服务端 · 下单流水** | 内购单在 **`NormalOrderPaySuccessEvent`**（支付成功）时，若该用户在该活动+企业下存在 **扫码绑定** 的 **`bind`** 流水（`extra.bind_channel=qr_code`），才自动写 **`order`** 流水（`ref_id`=订单号）；**不处理取消/退款**（不删、不冲正） |
| **未覆盖** | **亲友邀请绑定**（`bindRelative`）当前不写该流水表；非内购订单不写本表 |

### 0.2 数据表

| 表名 | 迁移 | 作用 |
|------|------|------|
| `employee_purchase_activity_passphrase_enterprises` | 随活动/口令功能演进 | 活动维度下「企业 ↔ 口令码、名额」绑定；C 端口令比对读此表 |
| `employee_purchase_activity_enterprise_behavior_log` | **`Version20260409160000`** 建表（含 **`result_status`** 列） | 一条记录 = 一次行为；`behavior_type` 区分扫码/口令/绑定/下单等 |

**流水表主要字段（概念）**：`company_id`、`activity_id`、`enterprise_id`、`user_id`（可空）、`behavior_type`、`visitor_key`（可空，未登录 UV）、`ref_id`（可空；**`order` 行为存订单号**）、`extra`（可空）、`result_status`（**仅 `passphrase_verify` 使用**：`success` / `fail`）、`created`。

### 0.3 接口总览

**管理端**（均需 JWT，基路径 `{API_BASE}`，常含版本前缀，以环境为准）：

| 用途 | 方法 | 路径（模式） |
|------|------|----------------|
| 批量生成口令码 | POST | `{API_BASE}/employeepurchase/passphrase-codes/generate` |
| 按活动生成口令码 | POST | `{API_BASE}/employeepurchase/activity/{activityId}/passphrase-codes/generate` |
| 创建活动（含口令配置） | POST | `{API_BASE}/employeepurchase/activity` |
| 更新活动（含口令替换/关闭清空） | PUT | `{API_BASE}/employeepurchase/activity/{activityId}` |
| 活动详情（含 `passphrase_enterprises`） | GET | `{API_BASE}/employeepurchase/activity/{activityId}` |
| 各企业行为聚合统计 | GET | `{API_BASE}/employeepurchase/activity/{activityId}/enterprise-behavior-stats` |

**C 端**（frontapi，`{FRONTAPI_BASE}/v1/h5app/...`）：

| 用途 | 方法 | 路径 | 鉴权 |
|------|------|------|------|
| **扫码 + 口令校验（统一入口）** | POST | `/wxapp/employeepurchase/activity/behavior-report` | `frontnoauth:h5app`；可选 JWT |
| **员工身份绑定（可触发 bind 流水）** | POST | `/wxapp/employee/auth` | `dingoguard:h5app` + JWT |

`behavior-report`：Body 用 **`behavior_type`**：`scan` 或 `passphrase_verify`。可选 **`Authorization: Bearer`**：有效 JWT 时 **`company_id`、`user_id` 以 token 为准**，body 中的 `company_id` 会被忽略。

`employee/auth`：表单/Body 除原有字段外，建议传 **`activity_id`**（当前内购活动），以便绑定成功后写入 **`bind`** 流水；不传则**不写**绑定流水（不影响绑定成功）。

**路由名（Laravel/Dingo）**：`front.wxapp.employeepurchase.activity.behavior_report`；控制器：`EmployeePurchaseBundle\Http\FrontApi\V1\Action\Activity@reportActivityBehavior`。

### 0.4 行为类型与统计口径

| `behavior_type` | 含义 | `result_status` | 管理端 `enterprise-behavior-stats` 相关字段 |
|-----------------|------|-----------------|---------------------------------------------|
| `scan` | 扫码/进入 | 始终 `NULL` | `scan_count`（PV）、`scan_user_count`（UV，按 `user_id` 或 `visitor_key`） |
| `passphrase_verify` | 口令尝试 | **`success` / `fail`** | `passphrase_verify_user_count` = **验证成功 UV**（`fail` 不计入；历史 `NULL` 仍按成功口径兼容） |
| `bind` | **活动员工账号绑定**（`employee/auth` 成功且传有效 `activity_id`） | `NULL` | `bind_user_count`（UV，按 `user_id`）；**`extra` 含 `bind_channel`**（与请求 `auth_type` 一致，如 `qr_code`） |
| `order` | 内购订单**支付成功**且存在 **扫码绑定** 流水（同上 `bind` + `bind_channel=qr_code`） | `NULL` | `order_user_count`（UV，按 `user_id`；**同订单幂等只记一条**） |

**支付成功时，怎么知道是哪个活动？**  
支付网关回调里通常**只有订单号**，不会直接带 `activity_id`。本项目的约定是：

1. **订单类型**：`orders_associations`（`OrderAssociationService::getOrder`）里 **`order_type === 'normal'` 且 `order_class === 'employee_purchase'`** 才是内购实体单。字符串 **`normal_employee_purchase`** 是订单模块里 **`GetOrderServiceTrait`** 把二者拼起来选 `EmployeePurchaseBundle` 的 `NormalOrderService` 用的**路由键**，**不是**关联表里 `order_type` 列的原值。  
2. **活动维度**：内购订单**创建**时会在 **`employee_purchase_orders_rel_activity`** 落一行（`NormalOrderService::createExtend` → `OrdersRelActivityService::create`），字段含 **`order_id`、`activity_id`、`enterprise_id`、`user_id`**。  
3. **记流水**：支付成功监听里用 `order_id` 查这张表，读出 `activity_id` / `enterprise_id` / `user_id`，再查行为表是否存在 **`behavior_type=bind` 且 `extra.bind_channel=qr_code`** 的同维度流水；满足才写 `order`。若查不到关联行，说明不是按内购链路建的单，**不写**行为流水。

因此：**「活动下支付」= 该订单在下单环节已绑定到具体活动**；支付阶段只是**回溯**这张关联表，而不是在支付时猜测活动。

**口令校验逻辑（摘要）**：活动须存在且 `enterprise_id` 为活动参与企业；须 **开启口令通道**；口令表须有对应行；用户输入与库中 `passphrase_code` 经规范化后 **`hash_equals`** 比对。失败场景仍 **HTTP 200**，`data.verified === false`，并写 **`fail`** 流水；**活动不存在**等业务错误抛错且 **不写口令流水**。

### 0.5 服务端代码入口（便于改查）

| 模块 | 路径 |
|------|------|
| C 端路由 | `routes/frontapi/employeepurchase.php` |
| C 端上报 | `EmployeePurchaseBundle\Http\FrontApi\V1\Action\Activity::reportActivityBehavior` 及私有方法 `writeActivityScanLog`、`verifyActivityPassphraseCore` |
| 口令比对 | `EmployeePurchaseBundle\Services\ActivitiesService`（`getPassphraseCodeForActivityEnterprise`、`isActivityEnterprisePassphraseMatch`） |
| 流水写入与聚合 | `EmployeePurchaseBundle\Services\ActivityEnterpriseBehaviorLogService`（`writeBehaviorLog`、`recordEmployeePurchaseOrderPaid`、`getAggregatedStatsForAdmin`） |
| 员工绑定与 bind 流水 | `EmployeePurchaseBundle\Services\EmployeesService::authentication`（成功后 `tryWriteEmployeeBindBehaviorLog`） |
| 支付成功与 order 流水 | `OrdersBundle\Events\NormalOrderPaySuccessEvent` → `EmployeePurchaseBundle\Listeners\EmployeePurchaseOrderPaySuccessListener` |
| 管理端统计接口 | `EmployeePurchaseBundle\Http\Api\V1\Action\Activity::getActivityEnterpriseBehaviorStats` |
| 流水仓储 | `EmployeePurchaseBundle\Repositories\ActivityEnterpriseBehaviorLogRepository` |
| 流水实体 | `EmployeePurchaseBundle\Entities\ActivityEnterpriseBehaviorLog` |

**Service 约定**：`writeBehaviorLog` / `record` 第 9 参数为 `result_status`；**仅** `behavior_type === passphrase_verify` 时允许且必填 `success`/`fail`，其它类型传状态会报错。

### 0.6 部署与迁移

1. 执行 Doctrine 迁移：**`Version20260409160000`**（若尚未执行）创建流水表（**已包含 `result_status` 列**）。  
2. 发布包含上述路由与控制器的代码版本。  
3. **前端**：仅对接 **`behavior-report`**；历史上若曾使用 `scan-report`、`passphrase-verify` 等旧路径，需全部切换为 **`behavior_type` 分派**。  
4. **OpenAPI/Swagger**：C 端注解在 `FrontApi\V1\Action\Activity` 的 `reportActivityBehavior` 上。  
5. **订单流水**：监听器在 `OrdersBundle\Providers\EventServiceProvider` 中注册 `NormalOrderPaySuccessEvent`。

### 0.7 下文章节索引

| 章节 | 内容 |
|------|------|
| **一～二** | 管理端：生成口令码、创建/更新活动保存口令企业 |
| **三～五** | 推荐流程、读活动详情、常见错误 |
| **六** | 流水表语义、`result_status`、管理端聚合接口、Service 写入说明 |
| **七** | C 端 **`behavior-report`** 请求/响应字段与示例 |

---

**活动详情（含口令企业 + 完整 `enterprise`）** 即管理端：

`GET https://demo-ecshopx.ishopex.cn/api/employeepurchase/activity/{activityId}`

示例：`GET https://demo-ecshopx.ishopex.cn/api/employeepurchase/activity/150`（`150` 为活动 ID）。

---

## 一、批量生成口令码

生成 **8 位**「数字 + 英文大小写」字符串；服务端会与库内已有口令去重后返回（**不落库**，仅给表单填值用）。

### 1.1 新建活动（尚无 `activity_id`）— 推荐

与本公司下、所有活动已占用的口令去重；企业须为当前公司下（店铺账号则为当前店铺可见）的内购企业。

**请求**

```http
POST {API_BASE}/employeepurchase/passphrase-codes/generate
Content-Type: application/json
Authorization: Bearer <token>
```

**Body（JSON）**

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `enterprise_ids` | `number[]` | 是 | 需要生成口令的企业 ID 列表 |
| `count` | `number` | 否 | 每个企业生成几条，默认 `1`，最大 `50` |
| `activity_id` | `number` | 否 | 新建场景**不传**或传 `0` |

**限制（服务端）**：最多 100 个企业；`enterprise_ids.length * count` 不超过 500。

**响应 `data` 示例**

```json
{
  "list": [
    { "enterprise_id": 101, "passphrase_codes": ["a3Bc9XyZ"] },
    { "enterprise_id": 102, "passphrase_codes": ["m7Np2QkL", "v4Rt8WsD"] }
  ]
}
```

**cURL 示例**

```bash
curl -sS -X POST "${API_BASE}/employeepurchase/passphrase-codes/generate" \
  -H "Authorization: Bearer YOUR_JWT" \
  -H "Content-Type: application/json" \
  -d '{
    "enterprise_ids": [101, 102],
    "count": 1
  }'
```

**前端 `fetch` 示例**

```javascript
const res = await fetch(`${API_BASE}/employeepurchase/passphrase-codes/generate`, {
  method: 'POST',
  headers: {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    enterprise_ids: [101, 102],
    count: 1,
  }),
});
const json = await res.json();
// 按项目实际解析 Dingo 包装，口令列表一般在 json.data.list
```

---

### 1.2 编辑已有活动（带 `activity_id`）

**方式 A：路径里带活动 ID（与旧版兼容）**

```http
POST {API_BASE}/employeepurchase/activity/{activityId}/passphrase-codes/generate
Content-Type: application/json
```

路径中的 `activityId` 优先；**忽略** body 里的 `activity_id`。  
去重范围：**该活动**下已保存的口令；企业须为该活动的**参与企业**。

**cURL 示例**

```bash
curl -sS -X POST "${API_BASE}/employeepurchase/activity/12345/passphrase-codes/generate" \
  -H "Authorization: Bearer YOUR_JWT" \
  -H "Content-Type: application/json" \
  -d '{"enterprise_ids":[101,102],"count":1}'
```

**方式 B：仍用 1.1 的 URL，body 里带 `activity_id`**

```json
{
  "activity_id": 12345,
  "enterprise_ids": [101, 102],
  "count": 1
}
```

---

## 二、保存口令企业配置（写入数据库）

口令企业与 **`employee_purchase_activity_passphrase_enterprises`** 的同步，通过 **创建活动** / **更新活动** 接口完成，**无单独「只保存口令」接口**。

### 2.1 `passphrase_enterprises` 每项结构

| 字段 | 类型 | 必填（开启口令时） | 说明 |
|------|------|-------------------|------|
| `enterprise_id` | number | 是 | 企业 ID，且必须出现在本次请求的**活动参与企业** `enterprise_id` 列表中 |
| `participate_quota` | number | 是 | 可参与名额，**必须 > 0**；该企业在本活动**已有**口令行时，**不得低于已保存值**（不可下调） |
| `passphrase_code` | string | 是 | 口令码，1～64 字符（推荐用生成接口的 8 位） |

**别名（后端已支持）**

- `participate_quota` 可写 `quota`
- `passphrase_code` 可写 `code`

**数组示例**

```json
[
  { "enterprise_id": 101, "participate_quota": 50, "passphrase_code": "a3Bc9XyZ" },
  { "enterprise_id": 102, "participate_quota": 30, "passphrase_code": "m7Np2QkL" }
]
```

同时还需传（与原有逻辑一致）：

- `is_passphrase_enabled`：开启口令通道时为 `true` / `1` / `"true"` 等（与现有活动布尔字段处理一致）
- `passphrase_limitfee`：口令通道额度，**分**，整数且 ≥ 0

**校验要点（后端）**

- 开启口令时：`passphrase_enterprises` 非空，且每行字段合法。
- 同一活动内：口令码不重复；每个 `enterprise_id` 只出现一行。
- 全公司范围内：口令码不能与**其它活动**已占用冲突（更新当前活动时会排除本活动旧数据）。
- 更新口令配置时：同一 `enterprise_id` 若已有存储行，则本次 `participate_quota` **不得小于**库中已保存值。

**管理端 UI（`purchase.vue`）**

- 活动详情为「进行中」（`status === ongoing`）且已开启口令时：界面仅允许编辑口令企业表中的 **可参与名额**；保存按钮在未开口令的进行中活动上禁用。强校验仍以接口为准。

---

### 2.2 创建活动 `POST {API_BASE}/employeepurchase/activity`

管理端创建活动多为 **multipart/form-data**（含图片等字段）。此时 `passphrase_enterprises` 常作为 **JSON 字符串** 放在一个表单项里。

**表单项示例（节选）**

| 字段 | 示例值 |
|------|--------|
| `is_passphrase_enabled` | `1` |
| `passphrase_limitfee` | `10000` |
| `passphrase_enterprises` | `[{"enterprise_id":101,"participate_quota":50,"passphrase_code":"a3Bc9XyZ"}]` |
| `enterprise_id[]` | `101`（多条重复 key，视你们表单封装而定） |

若整条请求用 **application/json**（且网关/后端允许），也可直接传数组类型字段（需与现有创建接口对 Content-Type 的约定一致）。

**逻辑顺序建议**

1. 用户选好参与企业 `enterprise_id`。
2. 调 **1.1 生成接口**，把返回的 `passphrase_codes` 填到各企业行。
3. 用户可改名额等，再与其它活动字段一并 **POST 创建**。

---

### 2.3 更新活动 `PUT {API_BASE}/employeepurchase/activity/{activityId}`

- 请求体字段与创建类似。
- **只要请求里带有键名 `passphrase_enterprises`**（即使值来自空数组），后端会按 **整表替换** 该活动的口令企业数据。
- 若本次将 `is_passphrase_enabled` 置为关闭，后端会**清空**口令企业表并处理额度，无需再传 `passphrase_enterprises`。

**JSON 示例（若接口支持 JSON 更新）**

```http
PUT {API_BASE}/employeepurchase/activity/12345
Content-Type: application/json
Authorization: Bearer <token>
```

```json
{
  "name": "活动名称",
  "title": "活动标题",
  "is_passphrase_enabled": true,
  "passphrase_limitfee": 10000,
  "passphrase_enterprises": [
    { "enterprise_id": 101, "participate_quota": 50, "passphrase_code": "a3Bc9XyZ" }
  ]
}
```

> 注意：你们环境若更新仍要求带齐 `pages_template_id`、`pic`、`share_pic` 等必填项，请按现有活动更新接口文档补全；上表仅强调口令相关字段。

### 2.4 管理端 ecshopx-admin（与本仓库字段对齐）

- **活动创建/编辑页**：`ecshopx-admin/src/view/marketing/employee/purchase.vue`  
  - 与 **§2.1** 一致提交：`is_passphrase_enabled`、`passphrase_limitfee`（**分**，整数 ≥ 0）、`passphrase_enterprises`（**JSON 字符串**；后端亦接受别名 `quota`、`code`）。  
  - **开启口令**时展示「口令码额度」（元，写入时换算为分）；**亲友**整块表单项（含标签）通过 `SpForm` 的 `isShow` 隐藏。  
  - 开启口令时仍填写 **员工购买时间**；**员工购买额度**在口令模式下不展示，提交以 `employee_limitfee` 为准。  
  - 创建/更新走 `POST /employeepurchase/activity`、`PUT /employeepurchase/activity/{activityId}`，由 `src/api/marketing.js` 发出。
- **批量生成口令**：`ecshopx-admin/src/api/marketing.js` 中的 `generatePassphraseCodes`、`generatePassphraseCodesByActivity`，请求体与 **§1.1 / §1.2** 一致：`enterprise_ids` 为 **数字数组**，可选 `count`。

---

## 三、推荐前端流程（简图）

1. 选参与企业 → `enterprise_ids`  
2. `POST .../passphrase-codes/generate` → 得到每企业 `passphrase_codes`  
3. 表格绑定：`enterprise_id`、`participate_quota`、选一条 `passphrase_code`  
4. 提交创建或更新：带上 `is_passphrase_enabled`、`passphrase_limitfee`、`passphrase_enterprises`  

---

## 四、活动详情中读取口令配置

与线上一致：`GET {API_BASE}/employeepurchase/activity/{activityId}`（例如 `GET .../api/employeepurchase/activity/150`）。返回里包含 `passphrase_enterprises`：每条为口令表一行（`id`、`enterprise_id`、`participate_quota`、`passphrase_code`、`created`、`updated` 等），`enterprise` 为 **与 `GET /enterprise/{id}`（getEnterpriseInfo）一致的企业详情**（含邮箱通道时的 `relay_host`、`smtp_port`、`email_user`、`email_password`、`email_suffix`，以及与企业列表一致的 **`distributor_name`**、`is_employee_check_enabled` 字符串等）。若企业已删或查不到，则 `enterprise` 为 `{ "id": <enterprise_id> }`。

---

## 五、常见错误提示（文案以实际返回为准）

| 场景 | 可能提示 |
|------|----------|
| 开启口令但未传企业口令列表 | 开启口令通道时请配置口令企业信息 |
| 企业不在活动参与列表中 | 口令企业须为活动参与企业 |
| 名额 ≤ 0 | 可参与名额须大于0 |
| 名额低于已保存值 | `{企业名称（企业 ID x）|企业 ID x}：本活动可参与名额不得低于已保存值（已保存：N，本次提交：M）` |
| 口令与其它活动冲突 | 口令编码已被其它活动占用：xxx |
| 生成接口企业非法 | 企业不存在或无权操作 |

---

## 六、活动企业行为流水与统计表（管理端）

数据表：`employee_purchase_activity_enterprise_behavior_log`（迁移 **`Version20260409160000`** 建表，已含 `result_status`）。**一行 = 一次行为**，通过 `behavior_type` 区分。

**行为类型常量**（写入时需与服务端一致）：

| 值 | 说明 |
|----|------|
| `scan` | 扫码 / 进入活动页（可重复，算 PV） |
| `passphrase_verify` | 口令验证（**每次尝试一条**，成功/失败由 `result_status` 区分） |
| `bind` | **活动员工身份绑定**成功（见 `POST .../employee/auth` + 可选 `activity_id`） |
| `order` | 内购订单支付成功且用户为 **扫码绑定**（存在 `bind` 且 `extra.bind_channel=qr_code`），`ref_id` 为订单号 |

**`result_status`（可选列，仅口令验证使用）**：`success` 表示校验与库中口令一致，`fail` 表示不一致或未开启口令等失败场景；其它 `behavior_type` 该列为 `NULL`。

**C 端 HTTP 接口（扫码、口令）**：见下文 **「七、C 端行为流水统一上报」**；**同一 URL**，`behavior_type` 区分 `scan` / `passphrase_verify`；带有效 JWT 时 `company_id`、`user_id` 来自登录态，**勿在 body 伪造会员身份**。

**管理端实时聚合接口**：

```http
GET {API_BASE}/employeepurchase/activity/{activityId}/enterprise-behavior-stats
Authorization: Bearer <token>
```

示例：`GET https://demo-ecshopx.ishopex.cn/api/employeepurchase/activity/150/enterprise-behavior-stats`

返回 `data.list`：每行对应一个**活动参与企业**，字段含 `enterprise_id`、`enterprise_name`、`enterprise_sn`、`logo`、`scan_count`、`scan_user_count`、`passphrase_verify_user_count`（**仅统计验证成功 UV**，`result_status=success`）、`bind_user_count`、`order_user_count`。

**写入流水**：**扫码、口令** 使用 **第七章** `behavior-report`；**员工绑定** 在 **`POST .../wxapp/employee/auth`** 成功时由服务端自动写入（须传 **`activity_id`**），并写入 **`extra.bind_channel`**。**内购订单支付成功** 由 **`NormalOrderPaySuccessEvent`** 监听器在满足 **扫码绑定流水** 条件时写 **`order`**（**取消/退款不删流水**）。其它场景若需扩展仍可调用 **`writeBehaviorLog(...)`** / **`record(...)`**。`passphrase_verify` 若直接调 Service，须传入第 9 参数 **`result_status`**（`success` / `fail`）。

---

## 七、C 端行为流水统一上报

**一个接口**完成：**扫码/进入** 与 **口令校验**。注册于 **`routes/frontapi/employeepurchase.php`**，`$api->version('v1', ...)`，URL 常含 **`/v1/`**，前缀 **`h5app`**。

**路径（接在 `{FRONTAPI_BASE}/v1` 之后）**

| 方法 | 路径 |
|------|------|
| POST | `/h5app/wxapp/employeepurchase/activity/behavior-report` |

**示例**

```http
POST https://{域名}/api/v1/h5app/wxapp/employeepurchase/activity/behavior-report
Content-Type: application/json
Authorization: Bearer <token>   # 可选；有效则按登录态解析 company_id、user_id
```

**鉴权与 `company_id`（`frontnoauth:h5app`）**

- 请求**带有效 JWT** 时：中间件会解析出会员身份，`company_id`、`user_id` **以 token 为准**（body 里的 `company_id` **会被忽略**，避免伪造）。
- **未登录**：须在 body 传 **`company_id`**（与活动所属公司一致）。
- **UV**：未登录强烈建议传 **`visitor_key`**（如 openid 摘要，≤64）；已登录以 **`user_id`** 去重。

---

### 7.1 公共 Body 字段

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `behavior_type` | string | 是 | `scan` 或 `passphrase_verify` |
| `company_id` | number | 未登录必填 | 已登录可省略（由 JWT 决定） |
| `activity_id` | number | 是 | 活动 ID |
| `enterprise_id` | number | 是 | 企业 ID，须为该活动参与企业（扫码、口令均校验） |
| `visitor_key` | string | 否 | 未登录建议传 |

---

### 7.2 `behavior_type = scan`（扫码 / 进入）

**响应 `data` 示例**

```json
{ "behavior_type": "scan", "status": true, "log_id": 123456 }
```

活动不存在、企业未参与该活动 → 错误响应（不写流水）。

---

### 7.3 `behavior_type = passphrase_verify`（口令校验）

与库表 **`employee_purchase_activity_passphrase_enterprises`** 中该活动-企业的 `passphrase_code` 比对；**每次请求无论成败都写一条流水**，`result_status` 为 `success` 或 `fail`。

**额外 Body 字段**

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `passphrase_code` | string | 与 `code` 二选一 | 用户输入口令 |
| `code` | string | 与上一项二选一 | 同义别名 |

- 活动不存在 → 错误，**不写流水**。
- 口令错误、未开启口令、无绑定行等 → **HTTP 200**，`verified: false`，并写 **fail** 流水。

**响应 `data` 示例**

```json
{ "behavior_type": "passphrase_verify", "verified": true, "log_id": 123457 }
```

```json
{ "behavior_type": "passphrase_verify", "verified": false, "log_id": 123458 }
```

---

文档版本：与仓库内 `EmployeePurchaseBundle` 口令与行为流水实现同步维护；**完整总览见上文「零、体系总览」**。
