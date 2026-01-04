<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;

class NormalItemsTagExportService implements ExportFileInterface
{
    private $title = [
        'item_name' => '商品名称',
        'item_bn' => '商品货号',
        'tag_name' => '标签名称',
    ];

    public $operator_type='';

    public function exportData($filter)
    {
        // Powered by ShopEx EcShopX
        unset($filter['merchant_id']);
        // TODO: Implement exportData() method.
        if(isset($filter['operator_type'])){
            $this->operator_type = $filter['operator_type'];
            unset($filter['operator_type']);
        }
        $itemService = new ItemsService();
        // if($this->operator_type == 'supplier'){
        //     $itemService = new SupplierItemsService();
        // }else{
        //     unset($filter['supplier_id']);
        // }

        if (isset($filter['item_id'])) {
            $filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $filter['item_id']
            ];
        }
        if (isset($filter['item_id']) && $filter['item_id']) {
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }
        $count = $itemService->getSkuItemsList($filter, 1, 1)['total_count'];
        if ($count <= 0) {
            return [];
        }
        $fileName = date('YmdHis') . "normal_items_tag";
        $dataList = $this->getLists($filter, $count);
        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['item_bn'];
        $result = $exportService->exportCsv($fileName, $this->title, $dataList, $textFields);
        return $result;
    }

    private function getLists($filter, $count)
    {
        // NOTE: important business logic
        $title = $this->title;
        $limit = 500;
        $totalPage = ceil($count / $limit);
        $itemService = new ItemsService();
        // if($this->operator_type == 'supplier'){
        //     $itemService = new SupplierItemsService();
        // }
        $itemsTagsService = new ItemsTagsService();
        for ($i = 1; $i <= $totalPage; $i++) {
            $itemsTagData = [];
            if (isset($filter['item_id']) && $filter['item_id']) {
                $filter['default_item_id'] = $filter['item_id'];
                unset($filter['item_id']);
            }
            unset($filter['is_default']);
            $orderBy = ['default_item_id' => 'DESC'];
            $result = $itemService->getSkuItemsList($filter, $i, $limit, $orderBy);
            $default_item_ids = array_column($result['list'], 'default_item_id');
            // 查询商品标签
            $tag_filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $default_item_ids,
            ];
            $itemTagList = $itemsTagsService->getItemsRelTagList($tag_filter);
            foreach ($itemTagList as $tag) {
                $_itemTagList[$tag['item_id']][] = $tag['tag_name'];
            }
            foreach ($result['list'] as $key => $value) {
                foreach ($title as $k => $val) {
                    if ($k == 'tag_name') {
                        $tag_name = $_itemTagList[$value['default_item_id']] ?? [];
                        $itemsTagData[$key][$k] = implode(',', $tag_name);
                    } if ($k == 'item_bn') {
                        // 直接赋值，不再判断是否为数字，不再添加引号，由 ExportFileService 统一处理
                        $itemsTagData[$key][$k] = $value[$k] ?? '';
                    } elseif (isset($value[$k])) {
                        $itemsTagData[$key][$k] = $value[$k];
                    }
                }
            }
            yield $itemsTagData;
        }
    }
}
