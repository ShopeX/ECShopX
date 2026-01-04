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

namespace TbItemsBundle\Services;

use TbItemsBundle\Client\TbClient;

class TbInterItemsService
{
    public function __construct()
    {

    }

    /**
     * 同步增量淘宝商品
     * todo 需要优化 -- 时间范围
     */
    public function getTbIncrItems($params)
    {
        $start_time = $params['start_time'];
        $end_time = $params['end_time'];
        
        $params = [
            'start_time' => $start_time,
            'end_time' => $end_time,
        ];

        try {
            (new TbItemsService($params['company_id']))->syncItemsCategory() // 分类、类目 
                ->newSyncTbItems($params) // 同步淘宝商品
                ->newSyncTbSkus() // 同步淘宝商品sku
                ->getItemsAttributes() // 商品属性 
                ->getItemsCategory() // 从商品拉取分类
                ->getItemsBaseData() // 基础数据 
                ->syncItemsRelation() // 同步商品和绑定关系 
                ->fillItemsBaseData(1); // 填充基础数据
        } catch (\Exception $e) {
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 单个商品入库
     * 字段 商品链接，类目id
     * item_url格式：https://item.taobao.com/item.htm?id=691447820988&pisk=gg3i2NXTRJ6WGCJLv...
     * category_id格式：100000000000
     */
    public function uploadTbItems($params)
    {
        $itemUrl = $params['item_url'];
        $categoryId = trim($params['category_id']);
        $companyId = $params['company_id'];

        // 获取商品 -- 从url中取id
        $iid = '';
        $parsedUrl = parse_url($itemUrl);
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            if (isset($queryParams['id'])) {
                $iid = $queryParams['id'];
            }
        }

        if (empty($iid)) {
            throw new \Exception('商品链接错误');
        }
        
        try {
            //销售分类、管理分类、规格、参数、图片、描写、sku
            (new TbItemsService($companyId))->syncSingleTbItems($iid, $categoryId); // 同步淘宝商品 -- 单个商品
        } catch (\Exception $e) {
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
        return true;
    }
}