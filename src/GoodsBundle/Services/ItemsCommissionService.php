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

use GoodsBundle\Entities\ItemsCommission;
use GoodsBundle\Entities\Items;

use Dingo\Api\Exception\ResourceException;

class ItemsCommissionService
{
    // 结算佣金类型 比例
    public const STATUS_COMMISSION_SCALE = 1;
    // 结算佣金类型 金额
    public const STATUS_COMMISSION_FEE = 2;

    public $itemsCommissionRepository;

    /**
     * ItemsCommissionService 构造函数
     *
     * @param Type $var
     */
    public function __construct()
    {
        $this->itemsCommissionRepository = app('registry')->getManager('default')->getRepository(ItemsCommission::class);
    }

    /**
     * 获取商品佣金配置
     * @param  array $filter 查询条件
     */
    public function getItemsCommission($filter)
    {
        $itemsService = new ItemsService();
        $itemDetail = $itemsService->getItemsDetail($filter['item_id']);

        if (!$itemDetail) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_get_failed'));
        }
        $result = [
            'item_id' => $filter['item_id'],
            'goods_id' => $itemDetail['goods_id'],
            'commission_type' => '1',
            'commission' => '',
        ];
        $item_ids = [];
        if ($itemDetail['nospec'] === true || $itemDetail['nospec'] === 'true' || $itemDetail['nospec'] === 1 || $itemDetail['nospec'] === '1') {
            $result['sku_commission'][] = [
                'item_id' => $itemDetail['item_id'],
                'item_spec_desc' => '单规格',
                'price' => $itemDetail['price'],
                'cost_price' => $itemDetail['cost_price'],
                'commission' => '',
            ];
            $item_ids[] = $itemDetail['item_id'];
        } else {
            foreach ($itemDetail['spec_items'] as $item) {
                $result['sku_commission'][] = [
                    'item_id' => $item['item_id'],
                    'item_spec_desc' => $item['custom_spec_name'],
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'commission' => '',
                ];
                $item_ids[] = $item['item_id'];
            }
        }
        //获取spu佣金配置
        $filter = [
            'company_id' => $filter['company_id'],
            'rel_id' => $itemDetail['goods_id'],
            'type' => 'goods',
        ];
        $info = $this->itemsCommissionRepository->getInfo($filter);
        if (empty($info)) {
            return $result;
        }
        $result['commission_type'] = $info['commission_type'];
        $result['commission'] = $info['commission_conf']['commission'];
        if ($result['commission_type'] == '2') {
            $result['commission'] = bcdiv($result['commission'], 100, 2);
        }
        // 获取sku佣金配置
        $filter = [
            'company_id' => $filter['company_id'],
            'rel_id' => $item_ids,
            'type' => 'item',
        ];

        $skuCommissionLists = $this->itemsCommissionRepository->lists($filter);
        if ($skuCommissionLists['total_count'] == 0) {
            return $result;
        }
        $skuCommission = array_column($skuCommissionLists['list'], null, 'rel_id');
        foreach ($result['sku_commission'] as $key => $value) {
            if (!isset($skuCommission[$value['item_id']])) {
                continue;
            }
            $sku_commission = $skuCommission[$value['item_id']];
            $commission_conf = json_decode($sku_commission['commission_conf'], true);
            $result['sku_commission'][$key]['commission'] = $result['commission_type'] == '2' ? bcdiv($commission_conf['commission'], 100, 2) : $commission_conf['commission'];
        }
        return $result;
    }

    public function checkParams(&$params)
    {
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['company_id' => $params["company_id"], 'item_id' => $params['item_id']]);
        if (!$itemInfo) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_get_failed'));
        }
        if ($params['commission'] < 0) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.spu_settlement_commission_must_be_non_negative'));
        }
        if (empty($params['sku_commission'])) {
            return;
        }
        $params['sku_commission'] = json_decode($params['sku_commission'], true);
        foreach ($params['sku_commission'] as $value) {
            if (empty($value['commission'])) {
                continue;
            }
            if ($value['commission'] < 0) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.sku_settlement_commission_must_be_non_negative'));
            }
        }
    }

    /**
     * 保存商品佣金配置
     */
    public function saveItemsCommission($params)
    {
        $this->checkParams($params);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 保存spu数据
            $commissionConfData = [
                'commission' => $params['commission'],
            ];
            $saveData = [
                'company_id' => $params['company_id'],
                'rel_id' => $params['goods_id'],
                'type' => 'goods',
                'commission_type' => $params['commission_type'],
                'commission_conf' => $commissionConfData,
            ];
            $filter = [
                'company_id' => $params['company_id'],
                'rel_id' => $params['goods_id'],
                'type' => 'goods',
            ];
            $commissionInfo = $this->getInfo($filter);
            if ($commissionInfo) {
                $this->updateOneBy($filter, $saveData);
            } else {
                $this->create($saveData);
            }
            // 保存sku数据
            if (empty($params['sku_commission'])) {
                $conn->commit();
                return [];
            }
            foreach ($params['sku_commission'] as $item) {
                if ($item['commission'] == "") {
                    $filter = [
                        'company_id' => $params['company_id'],
                        'rel_id' => $item['item_id'],
                        'type' => 'item',
                    ];
                    $commissionInfo = $this->getInfo($filter);
                    if ($commissionInfo) {
                        $this->deleteById($commissionInfo['id']);
                    }
                    continue;
                }
                $commissionConfData = [
                    'commission' => $item['commission'],
                ];
                $saveData = [
                    'company_id' => $params['company_id'],
                    'rel_id' => $item['item_id'],
                    'type' => 'item',
                    'commission_type' => $params['commission_type'],
                    'commission_conf' => $commissionConfData,
                ];
                $filter = [
                    'company_id' => $params['company_id'],
                    'rel_id' => $item['item_id'],
                    'type' => 'item',
                ];
                $commissionInfo = $this->getInfo($filter);
                if ($commissionInfo) {
                    $this->updateOneBy($filter, $saveData);
                } else {
                    $this->create($saveData);
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
     * 根据itemid，批量查询商品的佣金配置
     * @param  string $companyId 企业ID
     * @param  array $itemIds    itemIds
     */
    public function getAllCommissionByItem($companyId, $itemIds)
    {
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemIds,
        ];
        $itemLists = $itemsRepository->getLists($filter, 'item_id,goods_id', 1, -1);
        $_item_goods = array_column($itemLists, null, 'item_id');
        $goodsIds = array_unique(array_column($itemLists, 'goods_id'));

        // 查询sku的佣金配置
        $filter = [
            'company_id' => $companyId,
            'rel_id' => $goodsIds,
            'type' => 'goods',
        ];
        $spuCommissionLists = $this->lists($filter);
        if ($spuCommissionLists['total_count'] == 0) {
            return [];
        }
        $spuCommissionLists = array_column($spuCommissionLists['list'], null, 'rel_id');
        // 查询sku的佣金配置
        $filter = [
            'company_id' => $companyId,
            'rel_id' => $itemIds,
            'type' => 'item',
        ];
        $skuCommissionLists = $this->lists($filter);
        $skuCommissionLists = array_column($skuCommissionLists['list'], null, 'rel_id');
        foreach ($itemIds as $item_id) {
            $goods_id = $_item_goods[$item_id]['goods_id'];
            if (!isset($_item_goods[$item_id]) || !isset($spuCommissionLists[$goods_id])) {
                continue;
            }
            $spuCommission = $spuCommissionLists[$goods_id];
            $spuCommissionConf = json_decode($spuCommission['commission_conf'], true);
            $skuCommissionConf = [];
            if (isset($skuCommissionLists[$item_id])) {
                $skuCommissionConf = json_decode($skuCommissionLists[$item_id]['commission_conf'], true);
            }
            $result[$item_id] = [
                'commission_type' => $spuCommission['commission_type'],
                'commission' => $spuCommissionConf['commission'],
                'sku_commission' => $skuCommissionConf['commission'] ?? '',
            ];
        }
        return $result ?? [];
    }

    /**
     * Dynamically call the ItemsCommissionService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->itemsCommissionRepository->$method(...$parameters);
    }
}
