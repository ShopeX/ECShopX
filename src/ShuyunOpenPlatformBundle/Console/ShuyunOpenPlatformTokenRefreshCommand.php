<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Console;

use Illuminate\Console\Command;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformScheduledTokenRefreshRunner;

/**
 * 数云开放网关：按调度/手工触发 GET 刷新 Token 链接（新 Token 仍由回调 POST 落库）。
 *
 * 全量（无参数）仅处理「已启用 + 有 app_id/access_token + 非 isOverDue=1」行，避免无效请求。
 * 指定 company_id 时跳过 is_enabled 校验，便于冷启动刷新 token。
 */
class ShuyunOpenPlatformTokenRefreshCommand extends Command
{
    protected $signature = 'shuyun:open-platform:refresh-tokens {company_id? : 仅刷新该公司；不传则按调度规则扫全表}';

    protected $description = '触发数云 open-client Token 刷新 GET（company_shuyun_open_platform_config）';

    public function handle(): int
    {
        $runner = app(ShuyunOpenPlatformScheduledTokenRefreshRunner::class);

        $arg = $this->argument('company_id');
        $companyId = $arg !== null && $arg !== '' ? (int) $arg : null;

        $stats = $runner->run($companyId);
        $this->line(json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
