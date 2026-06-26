<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Console;

use Illuminate\Console\Command;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncAssessor;

final class ShuyunOpenPlatformAssessHistoricalSyncCommand extends Command
{
    protected $signature = 'shuyun:open-platform:assess-historical-sync
                            {company_id : 商户 company_id}
                            {--sample=0 : 预留：抽样测 RT（当前使用 --seconds-per-request 假设值）}
                            {--seconds-per-request=0.4 : 单次请求耗时假设（秒）}
                            {--report= : 将 JSON 报告写入文件路径}
                            {--dry-run : 与 assess 等价，仅统计}';

    protected $description = '数云开放：存量同步评估（统计各域 eligible 数量与耗时粗算，不写数云）';

    public function handle(HistoricalSyncAssessor $assessor): int
    {
        $companyId = (int) $this->argument('company_id');
        if ($companyId < 1) {
            $this->error('company_id 须为正整数。');

            return self::FAILURE;
        }

        $secondsPerRequest = (float) $this->option('seconds-per-request');
        if ($secondsPerRequest <= 0) {
            $secondsPerRequest = 0.4;
        }

        $report = $assessor->assess($companyId, $secondsPerRequest);
        $stats = $report['statistics'] ?? [];

        $this->line('数云开放网关 eligible: '.($report['gateway_eligible'] ? 'yes' : 'no'));
        $this->table(
            ['域', 'total', 'eligible', 'invalid/skipped'],
            [
                ['shops', $stats['shops']['total'] ?? '-', $stats['shops']['eligible'] ?? '-', '-'],
                ['categories', $stats['categories']['total'] ?? '-', $stats['categories']['eligible'] ?? '-', '-'],
                ['product_units', $stats['products']['product_units'] ?? '-', $stats['products']['eligible'] ?? '-', '-'],
                [
                    'members',
                    $stats['members']['total'] ?? '-',
                    $stats['members']['eligible'] ?? '-',
                    $stats['members']['invalid'] ?? '-',
                ],
                [
                    'orders',
                    $stats['orders']['total'] ?? '-',
                    $stats['orders']['eligible'] ?? '-',
                    $stats['orders']['skipped'] ?? '-',
                ],
                ['refunds', $stats['refunds']['total'] ?? '-', $stats['refunds']['eligible'] ?? '-', '-'],
                ['points', $stats['points']['total'] ?? '-', $stats['points']['eligible'] ?? '-', '-'],
            ]
        );

        $est = $report['estimate_seconds'] ?? ['min' => 0, 'max' => 0];
        $this->line(sprintf(
            '预估耗时（串行，RT≈%.2fs）：%d–%d 秒（约 %.1f–%.1f 分钟）',
            $secondsPerRequest,
            $est['min'],
            $est['max'],
            $est['min'] / 60,
            $est['max'] / 60
        ));

        $reportPath = trim((string) $this->option('report'));
        if ($reportPath !== '') {
            $json = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if ($json === false || file_put_contents($reportPath, $json) === false) {
                $this->error('无法写入报告: '.$reportPath);

                return self::FAILURE;
            }
            $this->line('报告已写入: '.$reportPath);
        }

        return self::SUCCESS;
    }
}
