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

namespace PointsmallBundle\Services\Items;

use PointsmallBundle\Entities\PointsmallItems;
use Dingo\Api\Exception\ResourceException;

// 普通商品
class Normal
{
    public function preRelItemParams($data, $params)
    {
        // Built with ShopEx Framework
        $rules = [
            // 'rebate' => ['numeric|min:0', '请输入正确的分销佣金'],
            'store' => ['required|integer|min:0:max:999999999', '库存为0-999999999的整数'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if (isset($params['cost_price'])) {
            $data['cost_price'] = $params['cost_price'] ? bcmul($params['cost_price'], 100) : 0;
        }

        // if(isset($params['rebate'])) {
        //     $data['rebate'] = $params['rebate'] ? bcmul($params['rebate'], 100) : 0;
        // }

        //设置库存
        $data['store'] = intval($params['store']);

        $itemId = $params['item_id'] ?? null;

        $data = $this->__checkItemBn($data, $itemId);

        return $data;
    }

    /**
     * 检查商品编号是否重复
     */
    private function __checkItemBn($data, $itemId = null)
    {
        if (empty($data['item_bn'])) {
            $data['item_bn'] = strtoupper(uniqid('s'));
        }

        $itemsRepository = app('registry')->getManager('default')->getRepository(PointsmallItems::class);

        $itemData = $itemsRepository->getInfo(['item_bn' => $data['item_bn'], 'company_id' => $data['company_id']]);
        if (!$itemData) {
            return $data;
        }

        if (!$itemId) {
            throw new ResourceException($data['item_bn'] . '商品编码重复，请添加正确的商品编码');
        }

        if ($itemId && $itemData['item_id'] != $itemId) {
            throw new ResourceException('商品编码重复，请添加正确的商品编码');
        }

        return $data;
    }
}
