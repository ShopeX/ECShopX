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

namespace SystemLinkBundle\Http\JushuitanApi\V1\Action;

use Illuminate\Http\Request;
use SystemLinkBundle\Http\Controllers\Controller as Controller;
use GoodsBundle\Services\ItemStoreService;
use GoodsBundle\Services\ItemsService;
use PromotionsBundle\Traits\CheckPromotionsValid;
use GoodsBundle\Entities\Items;
use PointsmallBundle\Entities\PointsmallItems;
use PointsmallBundle\Services\ItemsService as PointsmallItemsService;
use PointsmallBundle\Services\ItemStoreService as PointsmallItemStoreService;

class Item extends Controller
{
    use CheckPromotionsValid;

    /**
     * 同步商品库存
     * $request datas => {"i_id":"6308","id":1,"qty":10,"shop_i_id":"6308","shop_id":10406394,"shop_sku_id":"S66AAF975839CF","sku_id":"S66AAF975839CF","sku_source":null,"modified":"2020-12-26 14:29:21"}
     * list_quantity => {bn,quantity}
     */
    public function updateItemStore($companyId, Request $request)
    {
        $params = $request->post();
        app('log')->debug('jushuitan::Item_updateItemStore_params=>:'.json_encode($params));

        $rules = [
            'datas'   => ['required', '缺少数据'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage)
        {
            $this->api_response_shuyun('fail', $errorMessage);
        }

        extract($params);
        if (!$datas)
        {
            $this->api_response_shuyun('fail', "数据有误");
        }
        $list_quantity = [];
        foreach ($datas as $key => $value) {
            $list_quantity[] = [
                'bn' => $value['shop_sku_id'],
                'quantity' => $value['qty'],
            ];
        }
        app('log')->debug('jushuitan::Item_updateItemStore_list::list_quantity=>:'.json_encode($list_quantity));

        $itemsService = new ItemsService();

        // 取出所有要更新的商品BN
        $itemBns = [];
        for($i=count($list_quantity)-1;$i>=0;$itemBns[]=$list_quantity[$i]['bn'],$i--);
        app('log')->debug('jushuitan::Item_updateItemStore_list::all_itemBns=>:'.json_encode($itemBns));

        // 根据BN获取商品信息
        $itemList = $itemsService->getItemsList(['item_bn'=>$itemBns, 'company_id'=>$companyId]);
        app('log')->debug('jushuitan::Item_updateItemStore_list::normal_itemList=>:'.json_encode($itemList));

        // 未查询到的，去积分商城查询
        $pointsmallItemBns = $pointsmallItemList = [];
        if (empty($itemList['list'])) {
            $pointsmallItemBns = $itemBns;
        } else {
            $pointsmallItemBns = array_diff($itemBns, array_column($itemList['list'], 'item_bn'));
        }
        if ($pointsmallItemBns) {
            $pointsmallItemsService = new PointsmallItemsService();
            $filter = ['item_bn'=>$pointsmallItemBns, 'company_id'=>$companyId];
            app('log')->debug('jushuitan::Item_updateItemStore_list::pointsmall_filter=>:'.json_encode($filter));
            $pointsmallItemList = $pointsmallItemsService->getItemsList($filter);
            app('log')->debug('jushuitan::Item_updateItemStore_list::pointsmall_itemList=>:'.json_encode($pointsmallItemList));
        }
        if (!$itemList['list'] && !$pointsmallItemList['list'])
        {
            $this->api_response_shuyun('fail', "商品不存在");
        }

        if ($itemList['list'] ?? false) {
            $this->doNormalItemUpdateStore($companyId, $itemsService, $list_quantity, $itemList);
        }
        if ($pointsmallItemList['list'] ?? false) {
            $this->doPointsmallItemUpdateStore($list_quantity, $pointsmallItemList);
        }

        $this->api_response_shuyun('true', '操作成功');
    }

    /**
     * 普通商品--更新库存
     */
    private function doNormalItemUpdateStore($companyId, $itemsService, $list_quantity, $itemList)
    {
        //获取参与活动中的货品ID
        $activityItems = $this->getActivityItems();
        $activityBns = [];
        if ($activityItems)
        {
            //获取活动商品BN
            $activityItemList = $itemsService->getItemsList(['item_id'=>$activityItems, 'company_id'=>$companyId]);

            //取出参与活动中的商品BN
            for($i=count($activityItemList['list'])-1;$i>=0;$activityBns[]=$activityItemList['list'][$i]['item_bn'],$i--);
        }
        $itemStoreService = new ItemStoreService();
        //一次性获取要更新库存的商品的BN
        $itemBnList = [];
        foreach ((array)$itemList['list'] as $ival)
        {
            if (!$ival) continue;
            $itemBnList[$ival['item_bn']] = [
                'item_id'=>$ival['item_id'],
                'company_id'=>$ival['company_id'],
                'item_bn'=>$ival['item_bn']
            ];
        }

        $noUpdateItem = [];
        $nofundItem = [];
        $failUpdateItem = [];
        $conn = app('registry')->getConnection('default');
        foreach ((array)$list_quantity as $value)
        {
            if(!$value['bn'] || !isset($value['quantity']))
            {
                continue;
            }

            //参与活动中的商品跳过更新库存
            if ($activityBns && in_array($value['bn'], $activityBns))
            {
                $noUpdateItem[] = $value['bn'];
                continue;
            }

            //检查商品是否存在
            if (!isset($itemBnList[$value['bn']]) || !$itemBnList[$value['bn']])
            {
                $nofundItem[] = trim($value['bn']);
                continue;
            }

            $itemId = $itemBnList[$value['bn']]['item_id'];
            $criteria = $conn->createQueryBuilder();
            // 普通商品 order_class!=pointsmall
            $criteria->select('sum(i.num)')
                ->from('orders_normal_orders_items', 'i')
                ->leftJoin('i', 'orders_normal_orders', 'o', 'i.order_id = o.order_id')
                ->andWhere($criteria->expr()->neq('o.order_class', $criteria->expr()->literal('pointsmall')))
                ->andWhere($criteria->expr()->eq('i.item_id', $itemId))
                ->andWhere($criteria->expr()->andX(
                    $criteria->expr()->eq('o.order_status', $criteria->expr()->literal('NOTPAY')),
                    $criteria->expr()->gt('o.auto_cancel_time', time())
                ));
            $freez = $criteria->execute()->fetchColumn();
            $value['quantity'] -= $freez;

            //仅修改普通商品库存
            $result = $itemStoreService->saveItemStore($itemId, $value['quantity']);
            if ($result)
            {
                $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
                $result = $itemsRepository->updateStore($itemId, $value['quantity']);
            }
            if (!$result){
                $failUpdateItem[] = $value['bn'];
            }
        }

        if ($nofundItem)
        {
            app('log')->debug('聚水潭-普通商品-更新库存商品不存在：'.json_encode($nofundItem));
        }

        if ($noUpdateItem)
        {
            app('log')->debug('聚水潭-普通商品-活动商品暂不更新库存：'.json_encode($noUpdateItem));
        }

        if ($failUpdateItem)
        {
            app('log')->debug('聚水潭-普通商品-库存更新失败商品：'.json_encode($failUpdateItem));
        }
        return true;
    }

    /**
     * 积分商品--更新库存
     */
    private function doPointsmallItemUpdateStore($list_quantity, $pointsmallItemList)
    {
        $itemStoreService = new PointsmallItemStoreService();
        //一次性获取要更新库存的商品的BN
        $itemBnList = [];
        foreach ((array)$pointsmallItemList['list'] as $ival)
        {
            if (!$ival) continue;
            $itemBnList[$ival['item_bn']] = [
                'item_id'=>$ival['item_id'],
                'company_id'=>$ival['company_id'],
                'item_bn'=>$ival['item_bn']
           ];
        }

        $noUpdateItem = [];
        $nofundItem = [];
        $failUpdateItem = [];
        $conn = app('registry')->getConnection('default');
        foreach ((array)$list_quantity as $value)
        {
            if(!$value['bn'] || !isset($value['quantity']))
            {
                continue;
            }

            //检查商品是否存在
            if (!isset($itemBnList[$value['bn']]) || !$itemBnList[$value['bn']])
            {
                $nofundItem[] = trim($value['bn']);
                continue;
            }

            $itemId = $itemBnList[$value['bn']]['item_id'];
            $criteria = $conn->createQueryBuilder();
            // 积分商品 order_class=pointsmall
            $criteria->select('sum(i.num)')
                ->from('orders_normal_orders_items', 'i')
                ->leftJoin('i', 'orders_normal_orders', 'o', 'i.order_id = o.order_id')
                ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal('pointsmall')))
                ->andWhere($criteria->expr()->eq('i.item_id', $itemId))
                ->andWhere($criteria->expr()->andX(
                    $criteria->expr()->eq('o.order_status', $criteria->expr()->literal('NOTPAY')),
                    $criteria->expr()->gt('o.auto_cancel_time', time())
                ));
            $freez = $criteria->execute()->fetchColumn();
            $value['quantity'] -= $freez;

            //仅修改普通商品库存
            $result = $itemStoreService->saveItemStore($itemId, $value['quantity']);
            if ($result)
            {
                $pointsmallItemsRepository = app('registry')->getManager('default')->getRepository(PointsmallItems::class);
                $result = $pointsmallItemsRepository->updateStore($itemId, $value['quantity']);
            }
            if (!$result){
                $failUpdateItem[] = $value['bn'];
            }
        }

        if ($nofundItem)
        {
            app('log')->debug('聚水潭-积分商品-更新库存商品不存在：'.json_encode($nofundItem));
        }

        if ($noUpdateItem)
        {
            app('log')->debug('聚水潭-积分商品-活动商品暂不更新库存：'.json_encode($noUpdateItem));
        }

        if ($failUpdateItem)
        {
            app('log')->debug('聚水潭-积分商品-库存更新失败商品：'.json_encode($failUpdateItem));
        }
        return true; 
    }


}
