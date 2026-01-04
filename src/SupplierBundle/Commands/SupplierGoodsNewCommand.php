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

namespace SupplierBundle\Commands;

use GoodsBundle\Services\ItemRelAttributesService;
use GoodsBundle\Services\ItemsRelCatsService;
use GoodsBundle\Services\ItemsService;
use Illuminate\Console\Command;
use SupplierBundle\Services\SupplierItemsAttrService;
use SupplierBundle\Services\SupplierItemsService;

class SupplierGoodsNewCommand extends Command
{
    /**
     * 命令行执行命令
     * php artisan tools:supplier_goods_new
     * @var string
     */
    protected $signature = 'tools:supplier_goods_new';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '供应商商品数据初始化';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('begin => ' . date('Y-m-d H:i:s'));

        $orderBy = ['item_id' => 'DESC'];
        $filter = ['supplier_id|gte' => 1];
        $itemsService = new ItemsService();
        $supplierItemsService = new SupplierItemsService();
        $pageSize = 100;
        // $data_cols = $supplierItemsService->repository->cols;
        // $conn = app("registry")->getConnection("default");
        for ($page = 1; $page <= 500; $page++) {
            $rs = $supplierItemsService->repository->getLists($filter, '*', $page, $pageSize, $orderBy);
            if (!$rs) {
                break;
            }
            foreach ($rs as $v) {
                if ($itemsService->itemsRepository->getInfo(['supplier_item_id' => $v['item_id']])) {
                    echo($v['item_id'] . ' => skip;');
                    continue;
                }
                $this->line($v['item_id'] . ' => create');
                // $insert_col = [];
                // $insert_val = [];
                // foreach ($data_cols as $col_name) {
                //     if ($col_name == 'sales' or $col_name == 'volume' or $col_name == 'audit_date') {
                //         $v[$col_name] = intval($v[$col_name]);
                //     }
                //     $insert_col[] = $col_name;
                //     $insert_val[] = $v[$col_name] ?? '';
                // }
                // //写入新的供应商商品表
                // $sql = "insert into supplier_goods (" . implode(',', $insert_col) . ") values ('" . implode("','", $insert_val) . "')";
                // $conn->executeUpdate($sql);

                //写入商品规格表
                $this->createItemSpec($v['item_id']);

                //写入新的供应商商品属性表
                $defaultItemId = $v['default_item_id'];
                $this->itemsRelCats($defaultItemId);// 默认商品关联分类                
                $this->itemsRelBrand($defaultItemId);// 关联品牌               
                $this->itemsRelParams($defaultItemId); // 关联参数

                $itemsService->simpleUpdateBy(['item_id' => $v['item_id']], ['supplier_item_id' => $v['item_id']]);
            }
        }
        $this->line('finish => ' . date('Y-m-d H:i:s'));
        return true;
    }

    private function createItemSpec($item_id)
    {
        $itemRelAttributesService = new ItemRelAttributesService();
        $rs = $itemRelAttributesService->ItemRelAttributes->getList(['item_id' => $item_id, 'attribute_type' => 'item_spec']);
        if ($rs) {
            $SupplierItemsAttrService = new SupplierItemsAttrService();
            foreach ($rs as $v) {
                $paramsData = [
                    'company_id' => $v['company_id'],
                    'item_id' => $v['item_id'],
                    'attribute_id' => $v['attribute_id'],
                    'attribute_type' => 'item_spec',
                ];
                $attrData = [
                    'item_spec' => [
                        'attribute_sort' => $v['attribute_sort'],
                        'image_url' => $v['image_url'],
                        'attribute_value_id' => $v['attribute_value_id'],
                        'custom_attribute_value' => $v['custom_attribute_value'],
                    ]
                ];
                $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
            }
        }
    }

    private function itemsRelCats($defaultItemId)
    {
        $itemsRelCatsService = new ItemsRelCatsService();
        $rs = $itemsRelCatsService->entityRepository->getList(['item_id' => $defaultItemId]);
        if ($rs) {
            $paramsData = [
                'company_id' => $rs[0]['company_id'],
                'item_id' => $defaultItemId,
                'attribute_id' => 0,
                'attribute_type' => 'category',
            ];
            $attrData = [
                'category' => array_column($rs, 'category_id')
            ];
            $SupplierItemsAttrService = new SupplierItemsAttrService();
            $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
        }
    }

    /**
     * 商品关联品牌 如果为单规格关联当前商品ID，多规格关联默认商品ID
     */
    private function itemsRelBrand($defaultItemId)
    {
        $itemRelAttributesService = new ItemRelAttributesService();
        $rs = $itemRelAttributesService->ItemRelAttributes->getInfo(['item_id' => $defaultItemId, 'attribute_type' => 'brand']);
        // 保存品牌
        if ($rs) {
            $paramsData = [
                'company_id' => $rs['company_id'],
                'item_id' => $defaultItemId,
                'attribute_id' => trim($rs['attribute_id']),
                'attribute_type' => 'brand',
            ];
            $attrData = [
                'brand' => trim($rs['attribute_id']),
                'image_url' => '',
            ];
            $SupplierItemsAttrService = new SupplierItemsAttrService();
            $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
        }
    }

    /**
     * 商品关联参数 如果为单规格关联当前商品ID，多规格关联默认商品ID
     */
    private function itemsRelParams($defaultItemId)
    {
        $SupplierItemsAttrService = new SupplierItemsAttrService();
        $itemRelAttributesService = new ItemRelAttributesService();
        $rs = $itemRelAttributesService->ItemRelAttributes->getList(['item_id' => $defaultItemId, 'attribute_type' => 'item_params']);
        foreach ($rs as $v) {
            $paramsData = [
                'company_id' => $v['company_id'],
                'item_id' => $defaultItemId,
                'attribute_id' => $v['attribute_id'],
                'attribute_type' => 'item_params',
            ];
            $attrData = [
                'item_params' => [
                    'attribute_id' => $v['attribute_id'],
                    'attribute_value_id' => $v['attribute_value_id'] ?? null,
                    'custom_attribute_value' => $v['attribute_value_name'] ?? null,
                ]
            ];
            $SupplierItemsAttrService->saveAttrData($paramsData, $attrData);
        }
    }
}
