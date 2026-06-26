<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Console;

use Illuminate\Console\Command;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncFailureRecorder;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncRunOptions;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncRunner;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncSteps;

final class ShuyunOpenPlatformRunHistoricalSyncCommand extends Command
{
    protected $signature = 'shuyun:open-platform:run-historical-sync
                            {company_id : 商户 company_id}
                            {--step=shops : 步骤 shops|categories|products|members|orders|refunds|points|all；逗号分隔多步}
                            {--limit=0 : 每步最多处理条数，0 不限制}
                            {--offset=0 : 跳过前 N 条}
                            {--resume : 从 checkpoint 继续}
                            {--rate=0 : QPS 限速，0 不限}
                            {--force : 会员步强制重跑（忽略 shuyun_open_online_wxapp_sync_at）}
                            {--dry-run : 只遍历计数/写 checkpoint，不调网关}
                            {--assume-card-bound : 跳过「店铺同步后须在数云绑会员卡」提示}
                            {--default-item-id=0 : 商品步仅同步指定 SPU（items.default_item_id；数云 product_id 取 items.goods_id）}
                            {--distributor-id=0 : 商品步仅同步指定店铺 distributor_id}
                            {--failures= : 失败 CSV 路径，默认 storage/shuyun_historical_sync/{company_id}/failures.csv}';

    protected $description = '数云开放：存量数据分步同步（店铺→类目→商品→会员→订单→退款→积分）';

    public function handle(HistoricalSyncRunner $runner): int
    {
        $companyId = (int) $this->argument('company_id');
        if ($companyId < 1) {
            $this->error('company_id 须为正整数。');

            return self::FAILURE;
        }

        $steps = HistoricalSyncSteps::parseStepOption((string) $this->option('step'));
        if ($steps === []) {
            $this->error('无效的 --step，可选: '.implode(', ', HistoricalSyncSteps::orderedSteps()).', all');

            return self::FAILURE;
        }

        if (in_array(HistoricalSyncSteps::SHOPS, $steps, true)
            && count($steps) > 1
            && ! $this->option('assume-card-bound')
            && ! $this->option('dry-run')
        ) {
            $this->warn('店铺同步完成后，请在数云后台完成「店铺绑定会员卡」，再继续后续步骤。');
            $this->warn('若已完成，请加 --assume-card-bound 跳过本提示。');
        }

        if (in_array(HistoricalSyncSteps::POINTS, $steps, true)
            && in_array(HistoricalSyncSteps::MEMBERS, $steps, true)
            && ! $this->option('dry-run')
        ) {
            $this->line('提示：同一命令含 members 与 points 时，数云建议 register 后等待 2–3 分钟再对齐积分。');
        }

        $failuresPath = trim((string) $this->option('failures'));
        if ($failuresPath === '') {
            $failuresPath = storage_path('shuyun_historical_sync/'.$companyId.'/failures.csv');
        }

        $options = new HistoricalSyncRunOptions(
            companyId: $companyId,
            steps: $steps,
            limit: max(0, (int) $this->option('limit')),
            offset: max(0, (int) $this->option('offset')),
            resume: (bool) $this->option('resume'),
            rate: max(0.0, (float) $this->option('rate')),
            force: (bool) $this->option('force'),
            dryRun: (bool) $this->option('dry-run'),
            assumeCardBound: (bool) $this->option('assume-card-bound'),
            defaultItemId: max(0, (int) $this->option('default-item-id')),
            distributorId: max(0, (int) $this->option('distributor-id')),
        );

        $recorder = new HistoricalSyncFailureRecorder($failuresPath);
        try {
            $results = $runner->run($options, $recorder);
        } catch (\Throwable $e) {
            $recorder->close();
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $recorder->close();

        $tableRows = [];
        foreach ($results as $r) {
            $tableRows[] = [
                $r->step,
                $r->processed,
                $r->succeeded,
                $r->skipped,
                $r->failed,
            ];
        }
        $this->table(['step', 'processed', 'succeeded', 'skipped', 'failed'], $tableRows);
        if (is_file($failuresPath)) {
            $this->line('失败明细: '.$failuresPath);
        }

        $failedTotal = array_sum(array_map(static fn ($r) => $r->failed, $results));

        return $failedTotal > 0 ? self::FAILURE : self::SUCCESS;
    }
}
