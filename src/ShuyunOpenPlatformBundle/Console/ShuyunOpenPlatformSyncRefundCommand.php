<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use ShuyunOpenPlatformBundle\Jobs\SyncNormalOrderRefundToShuyunOpenPlatformJob;

/**
 * 与 {@see DispatchNormalOrderRefundSyncToShuyunOpenPlatformListener} 使用同一 Job。
 */
class ShuyunOpenPlatformSyncRefundCommand extends Command
{
    protected $signature = 'shuyun:open-platform:sync-refund
                            {company_id : 商户 company_id}
                            {refund_bn : 退款单号 refund_bn}
                            {--queue : 入队到 slow 由队列 worker 异步执行}';

    protected $description = '数云开放：手动推送退款同步（需退款状态 SUCCESS；ShuyunOpenPlatform::SyncNormalOrderRefundToShuyunOpenPlatformJob）';

    public function handle(Dispatcher $dispatcher): int
    {
        $companyId = (int) $this->argument('company_id');
        $refundBn = trim((string) $this->argument('refund_bn'));
        if ($companyId < 1 || $refundBn === '') {
            $this->error('company_id 与 refund_bn 均须有效。');

            return self::FAILURE;
        }

        $job = new SyncNormalOrderRefundToShuyunOpenPlatformJob($companyId, $refundBn);
        if ($this->option('queue')) {
            $dispatcher->dispatch($job->onQueue('slow'));
            $this->line('已入队 slow：'.SyncNormalOrderRefundToShuyunOpenPlatformJob::class);

            return self::SUCCESS;
        }

        $dispatcher->dispatchNow($job);
        $this->line('已在当前进程同步执行（dispatchNow）。请查看 shuyun_open_platform 通道日志。');

        return self::SUCCESS;
    }
}
