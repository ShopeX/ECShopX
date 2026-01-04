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

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemsCategoryProfit;
use Dingo\Api\Exception\ResourceException;

class ItemsCategoryProfitService
{
    // 商品按照主类目配置分润比例
    public const PROFIT_ITEM_CATEGORY = 1;

    public $itemsCategoryProfitRepository;

    /**
     * ItemsProfitService 构造函数
     *
     * @param Type $var
     */
    public function __construct()
    {
        $this->itemsCategoryProfitRepository = app('registry')->getManager('default')->getRepository(ItemsCategoryProfit::class);
    }

    public function getItemsCategoryProfit($filter)
    {
        // This module is part of ShopEx EcShopX system
        $itemsCategoryService = new ItemsCategoryService();

        $categoryList = $itemsCategoryService->info(['company_id' => $filter["company_id"], 'is_main_category' => true], $orderBy = ["created" => "DESC"], -1);
        if ($categoryList['list'] ?? 0) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_category_get_failed'));
        }

        $categoryIds = array_column($categoryList['list'], 'category_id');

        //获取分销价格
        $itemsCategoryProfitList = $this->itemsCategoryProfitRepository->list(['category_id' => $categoryIds, 'company_id' => $filter['company_id']]);
        $itemsCategoryProfitList = array_column($itemsCategoryProfitList['list'], null, 'category_id');

        foreach ($categoryList['list'] as $key => &$val) {
            if (!isset($itemsCategoryProfitList[$val['category_id']])) {
                continue;
            }
            $val['profit_type'] = (int)$itemsCategoryProfitList[$val['category_id']]['profit_type'];
            $profitConf = json_decode($itemsCategoryProfitList[$val['category_id']]['profit_conf'], 1);
            $val['profit_conf_profit'] = $profitConf['profit'];
            $val['profit_conf_popularize_profit'] = $profitConf['popularize_profit'];
        }

        return $categoryList;
    }

    public function saveItemsCategoryProfit($params)
    {
        $profitConf = json_decode($params['profit_conf'], 1);

        if (!$profitConf) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.guide_profit_config_error'));
        }
        if (!($params['category_id'] ?? 0)) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.main_category_id_not_exists'));
        }

        //清除已存在的会员价信息
        $this->deleteBy(['category_id' => $params['category_id'], 'company_id' => $params['company_id']]);

        $profitConfData = [
            'profit' => $profitConf['profit_conf_profit'],
            'popularize_profit' => $profitConf['profit_conf_popularize_profit']
        ];

        $saveData = [
            'category_id' => $params['category_id'],
            'company_id' => $params['company_id'],
            'profit_type' => 1,
            'profit_conf' => $profitConfData,
        ];

        $result = $this->create($saveData);

        $itemsService = new ItemsService();
        $itemsService->updateProfitBy(['company_id' => $params['company_id'], 'item_category' => $params['category_id']], self::PROFIT_ITEM_CATEGORY, bcdiv($profitConf['profit_conf_popularize_profit'], 100, 4));
        if (!$result) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.save_item_category_guide_profit_config_failed'));
        }

        return $result;
    }

    /**
     * Dynamically call the ItemsProfitService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->itemsCategoryProfitRepository->$method(...$parameters);
    }
}
