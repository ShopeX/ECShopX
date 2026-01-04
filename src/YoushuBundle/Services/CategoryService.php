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

namespace YoushuBundle\Services;

use GoodsBundle\ApiServices\ItemsCategoryService;
use GoodsBundle\Entities\ItemsCategory;

class CategoryService
{
    /**
     * @var Object
     */
    private $itemsCategoryRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->itemsCategoryRepository = app('registry')->getManager('default')->getRepository(ItemsCategory::class);
    }

    /**
     * @param array $params
     * 获取数据
     */
    public function getData($params)
    {
        // This module is part of ShopEx EcShopX system
        $company_id = $params['company_id'];
        $category_type = $params['category_type'];
        $data = [];
        //前台类目
        if ($category_type == 1) {
            $data = $this->category($company_id);
        }

        //后台类目
        if ($category_type == 2) {
            $data = $this->mgrCategory($company_id);
        }

        return $data;
    }

    /**
     * 前台分类
     */
    private function category($company_id)
    {
        $filter['company_id'] = $company_id;
        $filter['is_main_category'] = false;
        $page = 1;
        $page_size = 2000;
        $order_by = ["sort" => "DESC", "created" => "ASC"];
        $is_show = true;
        $items_category_service = new ItemsCategoryService();
        $result = $items_category_service->getItemsCategory($filter, $is_show, $page, $page_size, $order_by);
        $category_type = 1; //前台类目
        $result_data = [];
        $data = $this->data($result, $result_data, $category_type);

        return $data;
    }

    /**
     * 后台分离
     */
    public function mgrCategory($company_id)
    {
        $filter['company_id'] = $company_id;
        $filter['is_main_category'] = true;
        $page = 1;
        $pageSize = 2000;
        $orderBy = ["sort" => "DESC", "created" => "ASC"];
        $isShow = true;
        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->getItemsCategory($filter, $isShow, $page, $pageSize, $orderBy);
        $category_type = 2; //后台类目
        $result_data = [];
        $data = $this->data($result, $result_data, $category_type);

        return $data;
    }

    /**
     * @param array $data
     * @param array $result_data
     * @return array
     *
     * 组装分类数据
     */
    private function data($data, &$result_data, $category_type)
    {
        foreach ($data as $k => $v) {
            $external_category_id = $v['category_id'];
            $category_name = $v['category_name'];
            $category_level = $v['category_level'];
            $external_parent_category_id = $v['parent_id'];
            $result_data[] = [
                'external_category_id' => $external_category_id,
                'category_name' => $category_name,
                'category_type' => $category_type,
                'category_level' => $category_level,
                'external_parent_category_id' => $external_parent_category_id,
                'is_root' => $external_parent_category_id == 0 ? true : false
            ];

            if (isset($v['children']) && !empty($v['children'])) {
                $this->data($v['children'], $result_data, $category_type);
            }
        }

        return $result_data;
    }
}
