<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EspierBundle\Services\Export;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Services\ActivitiesService;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

class EmployeePurchaseActivityItemsExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        $companyId = (int) ($filter['company_id'] ?? 0);
        $activityId = (int) ($filter['activity_id'] ?? 0);
        if ($companyId <= 0 || $activityId <= 0) {
            throw new ResourceException('导出参数错误');
        }

        unset($filter['operator_id']);
        $title = [
            'item_name' => '商品标题',
            'goods_bn' => 'SPU编码',
            'item_bn' => 'SKU编码',
            'activity_price' => '活动价格',
            'activity_store' => '活动库存',
            'limit_num' => '限购数量',
            'limit_fee' => '限购金额',
            'shelf_status' => '状态',
            'sort' => '排序',
        ];
        $dataGenerator = $this->getLists($filter);
        $fileName = date('YmdHis').'_activity_'.$activityId.'_items';

        return (new ExportFileService())->exportCsv($fileName, $title, $dataGenerator, ['goods_bn', 'item_bn']);
    }

    private function getLists($filter)
    {
        $service = new ActivitiesService();
        $page = 1;
        $pageSize = 100;
        do {
            $result = $service->getActivityItemList($filter, $page, $pageSize, true, false);
            $rows = [];
            foreach ($result['list'] ?? [] as $goods) {
                $items = !empty($goods['spec_items']) ? $goods['spec_items'] : [$goods];
                foreach ($items as $item) {
                    $rows[] = [
                        'item_name' => (string) ($item['item_name'] ?? ''),
                        'goods_bn' => (string) ($item['goods_bn'] ?? ''),
                        'item_bn' => (string) ($item['item_bn'] ?? ''),
                        'activity_price' => bcdiv((string) ($item['activity_price'] ?? 0), '100', 2),
                        'activity_store' => (int) ($item['activity_store'] ?? 0),
                        'limit_num' => (int) ($item['limit_num'] ?? 0),
                        'limit_fee' => bcdiv((string) ($item['limit_fee'] ?? 0), '100', 2),
                        'shelf_status' => (int) ($item['shelf_status'] ?? 1) === 1 ? '上架' : '下架',
                        'sort' => (int) ($item['sort'] ?? 0),
                    ];
                }
            }
            if ($rows) {
                yield $rows;
            }
            $page++;
        } while (($page - 1) * $pageSize < (int) ($result['total_count'] ?? 0));
    }
}
