<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EspierBundle\Services\Export;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use EmployeePurchaseBundle\Services\ActivitiesService;

class EmployeePurchaseActivityQrcodeExportService implements ExportFileInterface
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
        $sourceRows = $activitiesService->buildActivityEnterpriseQrcodeExportRows(
            $companyId,
            $activityId,
            $distributorScopeId
        );
        if (empty($sourceRows)) {
            throw new ResourceException('导出有误,暂无数据导出');
        }

        $title = [
            'enterprise_name' => '企业名称',
            'enterprise_sn' => '企业编码',
            'passphrase_code' => '企业口令码',
            'participate_quota' => '可参与名额',
            'passphrase_limitfee' => '口令码额度(元)',
            'qrcode_url' => '企业小程序码下载地址',
        ];
        $rows = [];
        foreach ($sourceRows as $r) {
            $rows[] = [
                'enterprise_name' => (string) ($r[0] ?? ''),
                'enterprise_sn' => (string) ($r[1] ?? ''),
                'passphrase_code' => (string) ($r[2] ?? ''),
                'participate_quota' => (string) ($r[3] ?? ''),
                'passphrase_limitfee' => (string) ($r[4] ?? ''),
                'qrcode_url' => (string) ($r[5] ?? ''),
            ];
        }

        $dataGenerator = (function () use ($rows) {
            yield $rows;
        })();
        $fileName = date('YmdHis').'_activity_'.$activityId.'_qrcode';
        $exportService = new ExportFileService();

        return $exportService->exportCsv($fileName, $title, $dataGenerator, ['enterprise_sn']);
    }
}
