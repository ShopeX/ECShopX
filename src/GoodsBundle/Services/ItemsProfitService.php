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

use GoodsBundle\Entities\ItemsProfit;
use DistributionBundle\Services\DistributionService;
use Dingo\Api\Exception\ResourceException;

class ItemsProfitService
{
    // 分润类型
    public const STATUS_PROFIT_DEFAULT = 0;
    // 分润类型
    public const STATUS_PROFIT_SCALE = 1;
    // 分润类型
    public const STATUS_PROFIT_FEE = 2;

    // 商品按照商品固定比例
    public const PROFIT_ITEM_PROFIT_SCALE = 2;
    // 商品按照商品固定金额
    public const PROFIT_ITEM_PROFIT_FEE = 3;


    public $itemsProfitRepository;

    /**
     * ItemsProfitService 构造函数
     *
     * @param Type $var
     */
    public function __construct()
    {
        $this->itemsProfitRepository = app('registry')->getManager('default')->getRepository(ItemsProfit::class);
    }

    public function getItemsProfit($filter)
    {
        // ShopEx EcShopX Business Logic Layer
        $itemsService = new ItemsService();

        $itemInfo = $itemsService->getInfo(['company_id' => $filter["company_id"], 'item_id' => $filter['item_id']]);
        if (!$itemInfo) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_get_failed'));
        }

        //获取SKU信息
        if ($itemInfo['nospec'] === false || $itemInfo['nospec'] === 'false' || $itemInfo['nospec'] === 0 || $itemInfo['nospec'] === '0') {
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }

        $itemList = $itemsService->getSkuItemsList($filter);
        $itemIds = array_column($itemList['list'], 'item_id');

        //获取分销价格
        $itemsProfitList = $this->itemsProfitRepository->lists(['item_id' => $itemIds, 'company_id' => $filter['company_id']]);
        $itemsProfitList = array_column($itemsProfitList['list'], null, 'item_id');

        foreach ($itemList['list'] as $key => &$val) {
            if (!isset($itemsProfitList[$val['item_id']])) {
                continue;
            }
            $val['profit_type'] = (int)$itemsProfitList[$val['item_id']]['profit_type'];
            $profitConf = json_decode($itemsProfitList[$val['item_id']]['profit_conf'], 1);
            $val['profit_conf_profit'] = $profitConf['profit'];
            $val['profit_conf_popularize_profit'] = $profitConf['popularize_profit'];
        }

        return $itemList;
    }

    public function saveItemsProfit($params)
    {
        $profitConf = json_decode($params['profit_conf'], 1);

        if (!$profitConf) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.guide_profit_config_error'));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        $itemsService = new ItemsService();
        try {
            $itemIds = array_column($profitConf, 'item_id');
            //清除已存在的会员价信息
            $this->deleteBy(['item_id' => $itemIds, 'company_id' => $params['company_id']]);

            foreach ($profitConf as $itemId => $val) {
                $val['profit_type'] = intval($val['profit_type']);
                if (!in_array($val['profit_type'], [self::STATUS_PROFIT_DEFAULT, self::STATUS_PROFIT_SCALE, self::STATUS_PROFIT_FEE])) {
                    throw new ResourceException(trans('GoodsBundle/Controllers/Items.profit_type_error'));
                }
                if (0 != $val['profit_type']) {
                    $profitConfData = [
                        'profit' => $val['profit_conf_profit'],
                        'popularize_profit' => $val['profit_conf_popularize_profit']
                    ];

                    $saveData = [
                        'item_id' => $val['item_id'],
                        'company_id' => $params['company_id'],
                        'profit_type' => $val['profit_type'],
                        'profit_conf' => $profitConfData,
                    ];
                    $profitType = self::STATUS_PROFIT_SCALE == $val['profit_type'] ? self::PROFIT_ITEM_PROFIT_SCALE : self::PROFIT_ITEM_PROFIT_FEE;
                    $profitFee = self::STATUS_PROFIT_SCALE == $val['profit_type'] ? bcmul(bcdiv($val['profit_conf_popularize_profit'], 100, 4), $val['price']) : $val['profit_conf_popularize_profit'];
                    $result = $this->create($saveData);
                    $itemsService->updateBy(['item_id' => $val['item_id']], ['profit_type' => $profitType, 'profit_fee' => $profitFee]);
                    if (!$result) {
                        throw new ResourceException(trans('GoodsBundle/Controllers/Items.save_item_guide_profit_config_failed'));
                    }
                } else {
                    $itemsCategoryProfitService = new ItemsCategoryProfitService();
                    $itemInfo = $itemsService->getInfo(['item_id' => $val['item_id']]);
                    $itemsCategoryProfitInfo = $itemsCategoryProfitService->getInfo(['category_id' => $itemInfo['item_category']]);
                    if ($itemsCategoryProfitInfo) {
                        $profitConf = $itemsCategoryProfitInfo['profit_conf'];
                        $itemsService->updateProfitBy(['item_id' => $val['item_id']], 1, bcdiv($profitConf['popularize_profit'], 100, 4));
                    } else {
                        $distributionService = new DistributionService();
                        $distributionConfig = $distributionService->getDistributionConfig($params['company_id']);
                        $itemsService->updateProfitBy(['item_id' => $val['item_id']], 0, bcdiv($distributionConfig['distributor']['popularize_seller'], 100, 4));
                    }
                }
            }
            $conn->commit();
            return [];
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
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
        return $this->itemsProfitRepository->$method(...$parameters);
    }
}
