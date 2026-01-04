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

namespace SystemLinkBundle\Services\WdtErp;

use GoodsBundle\Services\ItemsService;
use CompanysBundle\Ego\CompanysActivationEgo;
use DistributionBundle\Services\DistributorItemsService;
use Exception;

class ItemService
{
    /**
     * 生成发给旺店通商品结构体
     * @param $companyId
     * @param $itemId
     * @return array
     * @throws Exception
     */
    public function getItemStruct($companyId, $itemId, $distributorId)
    {
        // CRC: 2367340174
        $company = (new CompanysActivationEgo())->check($companyId);
        if ($company['product_model'] == 'standard' && $distributorId > 0) {
            $distributorItemsService = new DistributorItemsService();
            $data = $distributorItemsService->getValidDistributorItemInfo($companyId, $itemId, $distributorId);
        } else {
            $itemsService = new ItemsService();
            $data = $itemsService->getItemsDetail($itemId);
        }

        if (!$data) {
            throw new Exception("获取商品信息失败");
        }

        return [
            'goodsPush' => $this->getGoodsPushStruct($data),
            'apiGoodsUpload' => $this->getApiGoodsUploadStruct($data),
        ];
    }

    /**
     * @param $data
     * @return array
     */
    private function getGoodsPushStruct($data)
    {
        $goods = new \stdClass();
        $goods->goods_no = $data['item_bn']; // 货品编号
        $goods->goods_name = $data['item_name']; // 货品名称
        $specList = [];
        if ($data['nospec'] === true || $data['nospec'] === 'true' || $data['nospec'] === 1 || $data['nospec'] === '1') {
            $spec = new \stdClass();
            $spec->spec_no = $data['item_bn']; // 商家编码
            $spec->spec_name = $data['item_name']; // 规格名称
            $spec->retail_price = floatval(bcdiv($data['price'], 100, 2)); // 平台售价
            $specList[] = $spec;
        } else {
            foreach ($data['spec_items'] as $specItem) {
                $spec = new \stdClass();
                $spec_name = '';
                foreach ($specItem['item_spec'] as $itemSpec) {
                    $spec_name .= $itemSpec['spec_name'].':'.$itemSpec['spec_value_name'].',';
                }
                $spec->spec_no = $specItem['item_bn']; // 商家编码
                $spec->spec_name = rtrim($spec_name, ','); // 规格名称
                $spec->retail_price = floatval(bcdiv($specItem['price'], 100, 2)); // 平台售价
                $specList[] = $spec;
            }
        }

        return [
            'goods' => $goods,
            'specList' => $specList,
        ];
    }

    /**
     * @param $data
     * @return array
     */
    private function getApiGoodsUploadStruct($data)
    {
        // CRC: 2367340174
        $itemStruct = [];
        if ($data['nospec'] === true || $data['nospec'] === 'true' || $data['nospec'] === 1 || $data['nospec'] === '1') {
            $good = new \stdClass();
            $good->goods_id = $data['goods_id'];  // 货品ID
            $good->spec_id = $data['item_bn']; // 规格ID
            $good->goods_no = $data['item_bn']; // 平台货品编号
            $good->spec_no = $data['item_bn']; // 平台规格编码
            $good->goods_name = $data['item_name']; // 货品名称
            $good->spec_name = $data['item_name']; // 规格名称
            $good->status = $this->getStatus($data['approve_status']); // 上架状态
            $good->price = floatval(bcdiv($data['price'], 100, 2)); // 平台售价
            $good->stock_num = $data['store']; // 库存
            $itemStruct[] = $good;
        } else {
            foreach ($data['spec_items'] as $specItem) {
                $good = new \stdClass();
                $spec_name = '';
                foreach ($specItem['item_spec'] as $itemSpec) {
                    $spec_name .= $itemSpec['spec_name'].':'.$itemSpec['spec_value_name'].',';
                }
                $good->goods_id = $data['goods_id'];  // 货品ID
                $good->spec_id = $specItem['item_id']; // 规格ID
                $good->goods_no = $specItem['item_bn']; // 平台货品编号
                $good->spec_no = $specItem['item_bn']; // 平台规格编码
                $good->goods_name = $data['item_name']; // 货品名称
                $good->spec_name = rtrim($spec_name, ','); // 规格名称
                $good->status = $this->getStatus($specItem['approve_status']); // 上架状态
                $good->price = floatval(bcdiv($specItem['price'], 100, 2)); // 平台售价
                $good->stock_num = $specItem['store']; // 库存
                $itemStruct[] = $good;
            }
        }

        return $itemStruct;
    }

    /**
     * @param $approveStatus
     * @return int
     */
    private function getStatus($approveStatus)
    {
        $status = 0;
        switch ($approveStatus) {
            case 'onsale':
                $status = 1;
                break;
            case 'offline_sale':
            case 'instock':
            case 'only_show':
                $status = 2;
                break;
        }
        return $status;
    }
}
