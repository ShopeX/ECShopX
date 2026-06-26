<?php

/**
 * 数云开放网关（open-api.shuyun.com + Gateway-*），与 common.shuyun_* / UAPI 隔离。
 *
 * **出站**：店铺/类目/商品/会员/订单等统一 `platCode=OFFLINE`、`platform: offline`；`shop_id` 为 `{distributor_id}{offline_plat_id_suffix}`（默认 `-off`，见 {@see offline_plat_id_suffix}）。
 * **入站**（等级、线下权益等）：验签用 {@see callback_identity_secret}（合作方「身份注册」密匙），与 DB `app_secret`（出站网关签名）分离。
 * 租户解析：优先 query/body `appId`；否则 body `platCode` 对应 DB `company_shuyun_open_platform_config.plat_code`（开启同步时应为 `OFFLINE`）。
 * Token 回调不验签；{@see callback_identity_secret} 不用于 token 路径。
 * 若库中 access_token 为空，出站可回退 {@see fallback_gateway_access_token}（联调/单租户；勿提交真实 token）。
 *
 * @see .tasks/plans/shuyun-open-platform-core.md
 * @see .tasks/plans/shuyun-offline-only.md
 * @see .tasks/plans/shuyun-shop-id-platform-migration.md
 */
return [
    /**
     * OFFLINE 出站 shop_id 后缀（拼在 distributor_id 后）。方案 C 默认 `-off`；设空字符串则裸 ID（仅联调）。
     * 环境变量：SHUYUN_OPEN_PLATFORM_OFFLINE_PLAT_ID_SUFFIX
     */
    'offline_plat_id_suffix' => env('SHUYUN_OPEN_PLATFORM_OFFLINE_PLAT_ID_SUFFIX', '-off'),
    'base_uri' => env('SHUYUN_OPEN_PLATFORM_BASE_URI', 'http://open-api.shuyun.com'),
    'timeout' => (float) env('SHUYUN_OPEN_PLATFORM_TIMEOUT', 30),
    'token_refresh_base_uri' => env('SHUYUN_OPEN_PLATFORM_TOKEN_REFRESH_BASE_URI', 'http://open-client.shuyun.com'),
    /**
     * DB 无 access_token 时，仍写入请求头 Gateway-Access-Token（与 Gateway-Authid 对应应用须匹配数云侧规则）。
     * 环境变量：SHUYUN_OPEN_PLATFORM_FALLBACK_GATEWAY_ACCESS_TOKEN
     */
    'fallback_gateway_access_token' => env('SHUYUN_OPEN_PLATFORM_FALLBACK_GATEWAY_ACCESS_TOKEN', ''),
    /**
     * 数云 **入站** HTTP 回调验签用：合作方「身份注册」密匙（MD5 规则见 {@see \ShuyunOpenPlatformBundle\Auth\ShuyunCallbackSignatureVerifier}）。
     * **不是** DB 中用于出站网关的 app_secret。环境变量：SHUYUN_OPEN_PLATFORM_CALLBACK_IDENTITY_SECRET
     */
    'callback_identity_secret' => env('SHUYUN_OPEN_PLATFORM_CALLBACK_IDENTITY_SECRET', ''),
    /**
     * token 回调联调：为 true 时打两条日志——请求快照（headers/query/body）与处理结果（code/msg）；**生产务必关闭**。
     * 环境变量：SHUYUN_OPEN_PLATFORM_CALLBACK_DEBUG_LOG
     */
    'callback_debug_log' => (bool) env('SHUYUN_OPEN_PLATFORM_CALLBACK_DEBUG_LOG', false),
    /**
     * Token 回调落库：遇 InnoDB 锁等待/死锁等可重试错误时的最大尝试次数（含首次）；≥1。
     * 环境变量：SHUYUN_OPEN_PLATFORM_TOKEN_CALLBACK_SAVE_MAX_ATTEMPTS
     */
    'token_callback_save_max_attempts' => max(1, (int) env('SHUYUN_OPEN_PLATFORM_TOKEN_CALLBACK_SAVE_MAX_ATTEMPTS', 6)),
    /**
     * Token 回调落库重试：首次重试前基础等待（微秒），与指数退避相乘；环境变量：SHUYUN_OPEN_PLATFORM_TOKEN_CALLBACK_SAVE_RETRY_BASE_USLEEP
     */
    'token_callback_save_retry_base_usleep' => max(5_000, (int) env('SHUYUN_OPEN_PLATFORM_TOKEN_CALLBACK_SAVE_RETRY_BASE_USLEEP', 50_000)),
    /**
     * Token 回调落库重试：单次退避上限（微秒）。环境变量：SHUYUN_OPEN_PLATFORM_TOKEN_CALLBACK_SAVE_RETRY_MAX_USLEEP
     */
    'token_callback_save_retry_max_usleep' => max(50_000, (int) env('SHUYUN_OPEN_PLATFORM_TOKEN_CALLBACK_SAVE_RETRY_MAX_USLEEP', 800_000)),
    /**
     * 联调日志中 body 最大字节，超出截断并追加标记；0 表示不截断。
     * 环境变量：SHUYUN_OPEN_PLATFORM_CALLBACK_DEBUG_LOG_BODY_MAX_BYTES
     */
    'callback_debug_log_body_max_bytes' => max(0, (int) env('SHUYUN_OPEN_PLATFORM_CALLBACK_DEBUG_LOG_BODY_MAX_BYTES', 65536)),
    /**
     * 非 token 类回调（如等级回调）联调：入口记录收到的 headers + body；**生产务必关闭**。
     * 环境变量：SHUYUN_OPEN_PLATFORM_CALLBACK_INBOUND_DEBUG_LOG
     */
    'callback_inbound_debug_log' => (bool) env('SHUYUN_OPEN_PLATFORM_CALLBACK_INBOUND_DEBUG_LOG', false),
    /**
     * 数云回调验签（{@see ShuyunCallbackSignatureVerifier}）过程调试：参与签名的参数键序、验签结果等；**生产务必关闭**。
     * 环境变量：SHUYUN_OPEN_PLATFORM_CALLBACK_SIGNATURE_DEBUG_LOG
     */
    'callback_signature_debug_log' => (bool) env('SHUYUN_OPEN_PLATFORM_CALLBACK_SIGNATURE_DEBUG_LOG', false),
    /** 请求体写入日志的最大字节（UTF-8 截断） */
    'gateway_request_log_body_max_bytes' => max(512, (int) env('SHUYUN_OPEN_PLATFORM_GATEWAY_REQUEST_LOG_BODY_MAX_BYTES', 12288)),
    /**
     * 响应体写入日志的最大字节；0 表示不截断（仍可能按 chunk 拆多条）。
     * 环境变量：SHUYUN_OPEN_PLATFORM_GATEWAY_RESPONSE_LOG_BODY_MAX_BYTES
     */
    'gateway_response_log_body_max_bytes' => max(0, (int) env('SHUYUN_OPEN_PLATFORM_GATEWAY_RESPONSE_LOG_BODY_MAX_BYTES', 0)),
    /**
     * 响应体超过该字节数时，主日志不写 response_body，改为额外多条「响应体分片」；与 gateway_call_id 对照。
     * 环境变量：SHUYUN_OPEN_PLATFORM_GATEWAY_RESPONSE_LOG_CHUNK_BYTES
     */
    'gateway_response_log_chunk_bytes' => max(1024, (int) env('SHUYUN_OPEN_PLATFORM_GATEWAY_RESPONSE_LOG_CHUNK_BYTES', 8192)),
    /**
     * 网关出站（如 bind.push）的 partner 固定标识，与数云约定；全项目数云开放平台相关请求统一使用此值。
     * 环境变量：SHUYUN_OPEN_PLATFORM_GATEWAY_PARTNER
     */
    'gateway_partner' => env('SHUYUN_OPEN_PLATFORM_GATEWAY_PARTNER', 'nnormal'),
    /**
     * 与数云 Token 回调 body 中 authValue 一致；由 Token 回调或后管配置保存时写入 `company_shuyun_open_platform_config.auth_value`（trim 后非空才写入，长度勿超表字段 128）。
     * 环境变量：SHUYUN_OPEN_PLATFORM_AUTH_VALUE
     */
    'auth_value' => env('SHUYUN_OPEN_PLATFORM_AUTH_VALUE', ''),
    /**
     * 同一 merge key 在 TTL 内重复触发只入队一次（店铺/后续类目商品共用 {@see ShuyunOpenPlatformMergedJobDispatchService}）。
     * 设为 0 则关闭合并（每次均入队，仅测试或排障）。
     */
    'merge_dispatch_ttl_seconds' => max(0, (int) env('SHUYUN_OPEN_PLATFORM_MERGE_DISPATCH_TTL', 3)),
    /**
     * 线下权益出站：{@see ShuyunOfflineBenefitReportService} 请求头 `platform`（与联调 apidoc 一致，常见小写 offline）。
     * 环境变量：SHUYUN_OPEN_PLATFORM_OFFLINE_BENEFIT_GATEWAY_PLATFORM
     */
    'offline_benefit_gateway_platform' => env('SHUYUN_OPEN_PLATFORM_OFFLINE_BENEFIT_GATEWAY_PLATFORM', 'offline'),
    /**
     * 线下权益发送报告（汇总 V2 + 明细 V2）网关调用失败时的最大轮询次数（每轮对仍未成功的接口重试一次；≥1）。
     * 环境变量：SHUYUN_OPEN_PLATFORM_OFFLINE_BENEFIT_REPORT_MAX_CYCLES
     */
    'offline_benefit_report_push_max_cycles' => max(1, (int) env('SHUYUN_OPEN_PLATFORM_OFFLINE_BENEFIT_REPORT_MAX_CYCLES', 3)),

    /**
     * 线下权益发券 Issuer：`stub`（联调占位码）或 `kaquan`（{@see ShuyunOfflineBenefitKaquanIssuer}）。
     * 环境变量：SHUYUN_OPEN_PLATFORM_OFFLINE_BENEFIT_ISSUER
     */
    'offline_benefit_issuer' => env('SHUYUN_OPEN_PLATFORM_OFFLINE_BENEFIT_ISSUER', 'kaquan'),

    /**
     * 数云 customerId → 本地 user_id：`numeric_user_id` 表示 customerId 为纯数字时直接作为 user_id（仅适用于两 ID 一致的场景）。
     * 若不一致，须实现并绑定自定义 {@see ShuyunOfflineBenefitIssuingMemberResolverInterface}。
     * 环境变量：SHUYUN_OPEN_PLATFORM_OFFLINE_BENEFIT_MEMBER_RESOLVE_MODE
     */
    'offline_benefit_member_resolve_mode' => env('SHUYUN_OPEN_PLATFORM_OFFLINE_BENEFIT_MEMBER_RESOLVE_MODE', 'numeric_user_id'),

    /**
     * 订单 {@see shuyun.base.trade.sync} 之 trade_source：键为 order_class（小写），值为数云侧编码字符串。
     * 默认值仅为联调占位；上线前须与数云运营对齐，可通过 env JSON 覆盖：SHUYUN_OPEN_PLATFORM_ORDER_CLASS_TRADE_SOURCE_MAP
     *
     * @var array<string, string>
     */
    'order_class_trade_source_map' => array_replace(
        [
            'normal' => '11',
            'pointsmall' => '12',
            'shopadmin' => '13',
            'shopguide' => '14',
            'employee_purchase' => '15',
            'groups' => '16',
            'seckill' => '17',
            'community' => '18',
            'bargain' => '19',
            'excard' => '20',
            'drug' => '21',
        ],
        json_decode((string) env('SHUYUN_OPEN_PLATFORM_ORDER_CLASS_TRADE_SOURCE_MAP', '{}'), true) ?: []
    ),
];
