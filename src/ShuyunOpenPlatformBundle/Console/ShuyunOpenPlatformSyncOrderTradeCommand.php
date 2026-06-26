<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use ShuyunOpenPlatformBundle\Jobs\SyncNormalOrderTradeToShuyunOpenPlatformJob;

/**
 * 与 {@see DispatchNormalOrderTradeSyncToShuyunOpenPlatformListener} 使用同一 Job；
 * 默认在 artisan 进程内同步执行（方便排障、避免与监听器去重/队列配置纠缠）。
 */
class ShuyunOpenPlatformSyncOrderTradeCommand extends Command
{
    protected $signature = 'shuyun:open-platform:sync-order-trade
                            {company_id : 商户 company_id}
                            {order_id : 订单号 order_id}
                            {--queue : 入队到 slow 由队列 worker 异步执行（与线上支付后派发一致）}';

    protected $description = '数云开放：手动推送单笔实体订单正向 trade 同步（ShuyunOpenPlatform::SyncNormalOrderTradeToShuyunOpenPlatformJob）';

    public function handle(Dispatcher $dispatcher): int
    {
        $companyId = (int) $this->argument('company_id');
        $orderId = trim((string) $this->argument('order_id'));
        if ($companyId < 1 || $orderId === '') {
            $this->error('company_id 与 order_id 均须有效。');

            return self::FAILURE;
        }

        $job = new SyncNormalOrderTradeToShuyunOpenPlatformJob($companyId, $orderId);
        if ($this->option('queue')) {
            $dispatcher->dispatch($job->onQueue('slow'));
            $this->line('已入队 slow：'.SyncNormalOrderTradeToShuyunOpenPlatformJob::class);

            return self::SUCCESS;
        }

        $dispatcher->dispatchNow($job);
        $this->line('已在当前进程同步执行（dispatchNow）。请查看 shuyun_open_platform 通道日志。');

        return self::SUCCESS;
    }
}
