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

namespace SystemLinkBundle\Services\Jushuitan;

use Exception;

use GoodsBundle\Services\ItemsService;
use CompanysBundle\Ego\CompanysActivationEgo;
use DistributionBundle\Services\DistributorItemsService;
use PointsmallBundle\Services\ItemsService as PointsmallItemsService;

class ItemService
{

    public function __construct()
    {

    }

    /**
     * 生成发给聚水潭商品结构体
     *
     */
    public function getItemStruct($companyId, $itemId, $distributorId, $shopId, $itemType)
    {
        // CRC: 2367340174
        $company = (new CompanysActivationEgo())->check($companyId);
        if ($company['product_model'] == 'standard' && $distributorId > 0) {
            $distributorItemsService = new DistributorItemsService();
            $data = $distributorItemsService->getValidDistributorItemInfo($companyId, $itemId, $distributorId);
        } else {
            if ($itemType == 'pointsmall') {
                $pointsmallItemsService = new PointsmallItemsService();
                $data = $pointsmallItemsService->getItemsDetail($itemId);
            } else {
                $itemsService = new ItemsService();
                $data = $itemsService->getItemsDetail($itemId);
            }
        }

        if (!$data) {
            throw new Exception("jushuitan::UploadItemsToJushuitanJob::getItemStruct::获取商品信息失败");
        }
        $itemStruct = [];
        if ($data['nospec'] === true || $data['nospec'] === 'true' || $data['nospec'] === 1 || $data['nospec'] === '1') {
            $sku = [
                'sku_id' => $data['item_bn'],
                'i_id' => $data['goods_id'],
                'name' => $data['item_name'],
                'sku_code' => $data['item_bn'],
                's_price' => floatval(bcdiv($data['price'], 100, 2)),
                'c_price' => floatval(bcdiv($data['cost_price'], 100, 2)),
                'enabled' => 1,
            ];
            if ($itemType == 'pointsmall') {
                $sku['s_price'] = floatval(bcdiv($data['market_price'], 100, 2));
            }
            $shopSku = [
                'sku_id' => $data['item_bn'],
                'i_id' => $data['goods_id'],
                'sku_code' => $data['item_bn'],
                'shop_i_id' => $data['goods_id'],
                'shop_sku_id' => $data['item_bn'],
                'name' => $data['item_name'],
                'shop_id' => $shopId,
            ];
            $itemStruct['items'][] = $sku;
            $itemStruct['shop_items'][] = [$shopSku];
        } else {
            $chunkSize = 50; // 每组50个
            $shop_items = [];
            foreach ($data['spec_items'] as $specItem) {
                $sku = [
                    'sku_id' => $specItem['item_bn'],
                    'i_id' => $data['goods_id'],
                    // 'shop_sku_id' => $specItem['item_bn'],
                    'name' => $data['item_name'],
                    'sku_code' => $specItem['item_bn'],
                    's_price' => floatval(bcdiv($specItem['price'], 100, 2)),
                    'c_price' => floatval(bcdiv($specItem['cost_price'], 100, 2)),
                    'enabled' => 1,
                ];
                if ($itemType == 'pointsmall') {
                    $sku['s_price'] = floatval(bcdiv($specItem['market_price'], 100, 2));
                }
                $shopSku = [
                    'sku_id' => $specItem['item_bn'],
                    'i_id' => $data['goods_id'],
                    'sku_code' => $specItem['item_bn'],
                    'shop_i_id' => $data['goods_id'],
                    'shop_sku_id' => $specItem['item_bn'],
                    'name' => $data['item_name'],
                    'shop_id' => $shopId,
                ];
                $itemStruct['items'][] = $sku;
                $shop_items[] = $shopSku;
                if ( count($shop_items) == $chunkSize) {
                    $itemStruct['shop_items'][] = array_slice($shop_items, 0, $chunkSize);
                    $shop_items = [];
                }
            }
            if (!empty($shop_items)) {
                $itemStruct['shop_items'][] = $shop_items;
                $shop_items = [];
            }
        }
        // app('log')->debug('jushuitan itemStruct===>:'.var_export($itemStruct,1));
        return $itemStruct;
    }
}
