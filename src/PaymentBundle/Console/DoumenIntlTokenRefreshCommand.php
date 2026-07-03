<?php

declare(strict_types=1);

namespace PaymentBundle\Console;

use Illuminate\Console\Command;
use PaymentBundle\Services\DoumenIntlScheduledTokenRefreshRunner;

/**
 * 斗门国际：按调度/手工触发 GET /authorize 刷新 token 并写入 Redis 缓存。
 *
 * 全量（无参数）仅处理「已启用 + 配置完整」的平台配置。
 * 指定 company_id 时仅刷新该公司配置。
 */
class DoumenIntlTokenRefreshCommand extends Command
{
    protected $signature = 'doumen:intl:refresh-tokens {company_id? : 仅刷新该公司；不传则扫描全部 eligible 配置}';

    protected $description = '刷新斗门国际支付网关 token（GET /authorize → Redis 缓存）';

    public function handle(): int
    {
        $runner = app(DoumenIntlScheduledTokenRefreshRunner::class);

        $arg = $this->argument('company_id');
        $companyId = $arg !== null && $arg !== '' ? (int) $arg : null;

        $stats = $runner->run($companyId);
        if ($companyId === null) {
            app('redis')->set('doumen_intl:token_refresh:last_scheduled_run', (string) time());
        }
        $this->line(json_encode($stats, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
