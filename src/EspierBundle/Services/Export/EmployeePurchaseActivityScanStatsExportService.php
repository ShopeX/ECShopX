<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EspierBundle\Services\Export;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use EmployeePurchaseBundle\Services\ActivitiesService;
use EmployeePurchaseBundle\Services\ActivityEnterpriseBehaviorLogService;

class EmployeePurchaseActivityScanStatsExportService implements ExportFileInterface
{
    /**
     * @param array<string,mixed> $filter
     */
    public function exportData($filter)
    {
        $companyId = (int) ($filter['company_id'] ?? 0);
        $activityId = (int) ($filter['activity_id'] ?? 0);
        $distributorScopeId = isset($filter['distributor_id']) ? (int) $filter['distributor_id'] : null;
        if ($companyId <= 0 || $activityId <= 0) {
            throw new ResourceException('导出参数错误');
        }

        $activitiesService = new ActivitiesService();
        $activityFilter = [
            'company_id' => $companyId,
            'id' => $activityId,
        ];
        if ($distributorScopeId !== null) {
            $activityFilter['distributor_id'] = $distributorScopeId;
        }
        $activity = $activitiesService->getInfo($activityFilter);
        $passphraseEnabled = !empty($activity['is_passphrase_enabled']);

        $service = new ActivityEnterpriseBehaviorLogService();
        $result = $service->getAggregatedStatsForAdmin($companyId, $activityId, $distributorScopeId);
        $list = $result['list'] ?? [];
        if (empty($list)) {
            throw new ResourceException('导出有误,暂无数据导出');
        }

        $title = [
            'enterprise_name' => '企业名称',
            'enterprise_sn' => '企业编码',
            'scan_count' => '扫码次数',
            'scan_user_count' => '扫码人数',
        ];
        if ($passphraseEnabled) {
            $title['passphrase_verify_user_count'] = '验证口令人数';
        }
        $title['bind_user_count'] = '绑定人数';
        $title['order_user_count'] = '下单人数';

        $rows = [];
        $totals = [
            'scan_count' => 0,
            'scan_user_count' => 0,
            'passphrase_verify_user_count' => 0,
            'bind_user_count' => 0,
            'order_user_count' => 0,
        ];
        foreach ($list as $row) {
            $totals['scan_count'] += (int) ($row['scan_count'] ?? 0);
            $totals['scan_user_count'] += (int) ($row['scan_user_count'] ?? 0);
            $totals['passphrase_verify_user_count'] += (int) ($row['passphrase_verify_user_count'] ?? 0);
            $totals['bind_user_count'] += (int) ($row['bind_user_count'] ?? 0);
            $totals['order_user_count'] += (int) ($row['order_user_count'] ?? 0);

            $line = [
                'enterprise_name' => (string) ($row['enterprise_name'] ?? ''),
                'enterprise_sn' => (string) ($row['enterprise_sn'] ?? ''),
                'scan_count' => (int) ($row['scan_count'] ?? 0),
                'scan_user_count' => (int) ($row['scan_user_count'] ?? 0),
            ];
            if ($passphraseEnabled) {
                $line['passphrase_verify_user_count'] = (int) ($row['passphrase_verify_user_count'] ?? 0);
            }
            $line['bind_user_count'] = (int) ($row['bind_user_count'] ?? 0);
            $line['order_user_count'] = (int) ($row['order_user_count'] ?? 0);
            $rows[] = $line;
        }

        $totalLine = [
            'enterprise_name' => '合计',
            'enterprise_sn' => '',
            'scan_count' => $totals['scan_count'],
            'scan_user_count' => $totals['scan_user_count'],
        ];
        if ($passphraseEnabled) {
            $totalLine['passphrase_verify_user_count'] = $totals['passphrase_verify_user_count'];
        }
        $totalLine['bind_user_count'] = $totals['bind_user_count'];
        $totalLine['order_user_count'] = $totals['order_user_count'];
        $rows[] = $totalLine;

        $dataGenerator = (function () use ($rows) {
            yield $rows;
        })();
        $fileName = date('YmdHis').'_activity_'.$activityId.'_scan_stats';
        $exportService = new ExportFileService();

        return $exportService->exportCsv($fileName, $title, $dataGenerator, ['enterprise_sn']);
    }
}
