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

namespace GoodsBundle\Console;

use Illuminate\Console\Command;
use GoodsBundle\Services\ItemsCategoryService;

class ImportItemsCategory extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'item:importItemsCategory {companyId} {isMain} {filePath}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '导入商品分类';

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
        $companyId = $this->argument('companyId') ?: 1;
        $isMain = $this->argument('isMain') ?: 0;
        $filePath = $this->argument('filePath');
        if (!$filePath) {
            $this->info('请输入文件路径');
            exit;
        }

        $results = app('excel')->toArray(new \stdClass(), $filePath);
        $results = $results[0]; //excel第一张sheet

        $headerData = array_filter($results[0]);
        array_walk($headerData, function (&$value) {
            $value = preg_replace("/\s|　/", "", $value);
        });
        $column = $this->headerHandle($headerData);
        unset($results[0]);

        foreach ($results as $key => $row) {
            if (!array_filter($row)) {
                continue;
            }
            $data = $this->preRowHandle($column, $row);
            $data['company_id'] = $companyId;
            $data['is_main_category'] = $isMain;
            $this->handleRow($data);
        }
    }

    private function handleRow($data)
    {
        $itemsCategoryService = new ItemsCategoryService();

        $params = [
            'company_id' => $data['company_id'],
            'is_main_category' => $data['is_main_category'],
            'category_name' => $data['lv1'],
            'category_level' => 1,
        ];
        $lv1 = $itemsCategoryService->getInfo($params);
        if (!$lv1) {
            $itemsCategoryService->createClassificationService($params, $data['company_id'], 0);
            $lv1 = $itemsCategoryService->getInfo($params);
        }

        $params = [
            'company_id' => $data['company_id'],
            'is_main_category' => $data['is_main_category'],
            'category_name' => $data['lv2'],
            'parent_id' => $lv1['category_id'],
            'category_level' => 2,
        ];
        $lv2 = $itemsCategoryService->getInfo($params);
        if (!$lv2) {
            $itemsCategoryService->createClassificationService($params, $data['company_id'], 0);
            $lv2 = $itemsCategoryService->getInfo($params);
        }

        $params = [
            'company_id' => $data['company_id'],
            'is_main_category' => $data['is_main_category'],
            'category_name' => $data['lv3'],
            'parent_id' => $lv2['category_id'],
            'category_level' => 3,
        ];
        $lv3 = $itemsCategoryService->getInfo($params);
        if (!$lv3) {
            $itemsCategoryService->createClassificationService($params, $data['company_id'], 0);
            $lv3 = $itemsCategoryService->getInfo($params);
        }

        $this->info($lv1['category_name'].' => '.$lv2['category_name'].' => '.$lv3['category_name']);
    }

    private function headerHandle($headerData)
    {
        $title = [
            '一级分类' => 'lv1',
            '二级分类' => 'lv2',
            '三级分类' => 'lv3',
        ];

        foreach ($headerData as $key => $columnName) {
            if (isset($title[$columnName])) {
                $column[$key] = $title[$columnName];
            }
        }
        return $column;
    }

    private function preRowHandle($column, $row)
    {
        $data = [];
        foreach ($column as $key => $col) {
            if (isset($row[$key])) {
                $data[$col] = $row[$key];
            } else {
                $data[$col] = null;
            }
        }
        return $data;
    }
}
