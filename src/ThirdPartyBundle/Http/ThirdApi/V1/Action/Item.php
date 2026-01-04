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

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use ThirdPartyBundle\Http\Controllers\Controller as Controller;

use GoodsBundle\Services\ItemStoreService;

use GoodsBundle\Services\ItemsService;



use PromotionsBundle\Traits\CheckPromotionsValid;

use GoodsBundle\Entities\Items;

use DistributionBundle\Entities\Distributor;
use DistributionBundle\Entities\DistributorItems;

class Item extends Controller
{
    use CheckPromotionsValid;

    /**
     * SaasErp 同步商品库存
     * $request paramss =>'datas' => '[{"product_bn": "G57CE7762CAC5D-1", "memo": "{\\"store_freeze\\":0,\\"last_modified\\":\\"1473324843\\"}", "product_store": 106}, {"product_bn": "G57CE7762CAC5D-2", "memo": "{\\"store_freeze\\":0,\\"last_modified\\":\\"1473324843\\"}", "product_store": 92}, {"product_bn": "G57CE7762CAC5D-3", "memo": "{\\"store_freeze\\":0,\\"last_modified\\":\\"1473324843\\"}", "product_store": 100}, {"product_bn": "G57CE7762CAC5D-4", "memo": "{\\"store_freeze\\":0,\\"last_modified\\":\\"1473324843\\"}", "product_store": 100}]'
     */
    public function updateItemStore(Request $request)
    {
        $params = $request->all();
        app('log')->debug('Item_updateItemStore_params=>:'.var_export($params, 1));

        $rules = [
            'datas' => ['required', '缺少数据'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }
        $list_quantity = json_decode($params['datas'], true);
        $list_quantity or $list_quantity = json_decode(stripcslashes($params['datas']), 1);

        if (!$list_quantity) {
            $this->api_response('fail', "数据有误");
        }

        $itemsService = new ItemsService();

        //获取参与活动中的货品ID
        $activityItems = $this->getActivityItems($this->companyId);

        $activityBns = [];
        if ($activityItems) {
            //获取活动商品BN
            $activityItemList = $itemsService->getItemsList(['item_id' => $activityItems]);

            //取出参与活动中的商品BN
            for ($i = count($activityItemList['list']) - 1;$i >= 0;$activityBns[] = $activityItemList['list'][$i]['item_bn'],$i--);
        }

        $itemStoreService = new ItemStoreService();

        // 取出所有要更新的商品BN
        $itemBns = [];
        for ($i = count($list_quantity) - 1;$i >= 0;$itemBns[] = $list_quantity[$i]['product_bn'],$i--);
        // 根据BN获取商品信息
        $filter = ['item_bn' => $itemBns];
        if ($this->companyId) {
            $filter['company_id'] = $this->companyId;
        }
        $itemList = $itemsService->getItemsList($filter);
        app('log')->debug('Item_updateItemStore_list_itemBns=>:'.var_export($itemBns, 1));
        app('log')->debug('Item_updateItemStore_list_itemList=>:'.var_export($itemList, 1));
        if (!$itemList) {
            $this->api_response('fail', "商品不存在");
        }
        //一次性获取要更新库存的商品的BN
        $itemBnList = [];
        foreach ((array)$itemList['list'] as $ival) {
            if (!$ival) {
                continue;
            }
            $itemBnList[$ival['item_bn']] = [
                'item_id' => $ival['item_id'],
                'company_id' => $ival['company_id'],
                'item_bn' => $ival['item_bn']
            ];
        }

        $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $distributorItemsRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);

        $noUpdateItem = [];
        $nofundItem = [];
        $failUpdateItem = [];
        $failUpdateDistributorItem = [];
        foreach ((array)$list_quantity as $value) {
            if (!$value['product_bn'] || !isset($value['product_store'])) {
                continue;
            }

            //参与活动中的商品跳过更新库存
            if ($activityBns && in_array($value['product_bn'], $activityBns)) {
                $noUpdateItem[] = $value['product_bn'];
                continue;
            }

            //检查商品是否存在
            if (!isset($itemBnList[$value['product_bn']]) || !$itemBnList[$value['product_bn']]) {
                $nofundItem[] = trim($value['product_bn']);
                continue;
            }

            //店铺编码不为空时修改店铺库存
            if (isset($value['store_code']) && $value['store_code']) {
                $distributorInfo = $distributorRepository->getInfo(['shop_code' => $value['store_code']]);
                if (!$distributorInfo) {
                    $failUpdateDistributorItem['store_code_error'][] = [$value['store_code'], $value['product_bn']];
                    continue;
                }

                $filter = [
                    'item_id' => $itemBnList[$value['product_bn']]['item_id'],
                    'distributor_id' => $distributorInfo['distributor_id']
                ];
                $distributorItem = $distributorItemsRepository->getInfo($filter);
                if (!$distributorItem || $distributorItem['is_total_store']) {
                    $failUpdateDistributorItem['is_total_store_error'][] = [$value['store_code'], $value['product_bn']];
                    continue;
                }

                $result = $itemStoreService->saveItemStore($itemBnList[$value['product_bn']]['item_id'], $value['product_store'], $distributorInfo['distributor_id']);
                if ($result) {
                    $result = $distributorItemsRepository->updateOneBy($filter, ['store'=>$value['product_store']]);
                    if (!$result) {
                        $failUpdateDistributorItem['updateOneBy_error'][] = [$value['store_code'], $value['product_bn']];
                    }
                } else {
                    $failUpdateDistributorItem['saveItemStore_error'][] = [$value['store_code'], $value['product_bn']];
                }
            } else {
                //仅修改普通商品库存
                $result = $itemStoreService->saveItemStore($itemBnList[$value['product_bn']]['item_id'], $value['product_store']);
                if ($result) {
                    $result = $itemsRepository->updateStore($itemBnList[$value['product_bn']]['item_id'], $value['product_store']);
                }
                if (!$result) {
                    $failUpdateItem[] = $value['product_bn'];
                }
            }
        }

        if ($nofundItem) {
            app('log')->debug("saaserp 更新库存商品不存在：".json_encode($nofundItem));
        }

        if ($noUpdateItem) {
            app('log')->debug("saaserp 活动商品暂不更新库存：".json_encode($noUpdateItem));
        }

        if ($failUpdateItem) {
            app('log')->debug("saaserp 库存更新失败商品：".json_encode($failUpdateItem));
        }

        if ($failUpdateDistributorItem) {
            app('log')->debug("saaserp 门店库存更新失败商品：".json_encode($failUpdateDistributorItem));
        }

        $this->api_response('true', '操作成功');
    }
}
