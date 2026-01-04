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

namespace GoodsBundle\Services\Items;

use GoodsBundle\Entities\ItemsRelType;
use Dingo\Api\Exception\ResourceException;

// 服务商品
class Services
{
    /**
     * 检查关联参数
     *
     * @return void
     */
    private function checkRelParams($params)
    {
        // 0x53686f704578
        if (!$params['type_labels']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.please_select_item_content'));
        }

        $rules = [
            'consume_type' => ['in:every,all', '核销类型参数不正确'],
            'begin_date' => ['required_if:date_type,DATE_TYPE_FIX_TIME_RANGE', '有效期开始日期必填'],
            'end_date' => ['required_if:date_type,DATE_TYPE_FIX_TIME_RANGE', '有效期结束日期必填'],
            'fixed_term' => ['required_if:date_type,DATE_TYPE_FIX_TERM', '有效期天数必填'],
            'type_labels.*.labelId' => ['required|integer|min:1', '缺少参数数值属性ID'],
            'type_labels.*.num' => ['required|integer', '数值规则必须是正整数'],
            'type_labels.*.limitTime' => ['required_if:consume_type,every|integer', '有效期必须是正整数'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        return true;
    }

    /**
     * 新增服务商品特有的关联数据
     */
    public function createRelItem($itemsResult, $params)
    {
        $itemsRelTypeRepository = app('registry')->getManager('default')->getRepository(ItemsRelType::class);

        $this->checkRelParams($params);

        $typeLabelsResult = [];
        if (isset($params['type_labels']) && $params['type_labels']) {
            foreach ($params['type_labels'] as $v) {
                if (isset($v['labelId'])) {
                    $tmp = [
                        'item_id' => $itemsResult['item_id'],
                        'label_id' => $v['labelId'],
                        'label_name' => $v['labelName'],
                        'label_price' => $v['labelPrice'] ? bcmul($v['labelPrice'], 100) : 0,
                        'num_type' => 'plus',
                        'num' => (isset($v['isNotLimit']) && $v['isNotLimit'] == 1) ? 0 : $v['num'],
                        'is_not_limit_num' => $v['isNotLimitNum'] ?? 2 ,
                        'limit_time' => $params['consume_type'] === 'every' ? $v['limitTime'] : 0,
                        'company_id' => $params['company_id'],
                    ];
                    $typeLabelsResult[] = $itemsRelTypeRepository->create($tmp);
                }
            }
        }

        $itemsResult['type_labels'] = $typeLabelsResult;
        return $itemsResult;
    }

    public function deleteRelItemById($itemId)
    {
        $itemsRelTypeRepository = app('registry')->getManager('default')->getRepository(ItemsRelType::class);

        $itemsRelTypeRepository->deleteAllBy($itemId);

        return true;
    }

    public function listByItemId($itemId)
    {
        // 0x53686f704578
        $itemsRelTypeRepository = app('registry')->getManager('default')->getRepository(ItemsRelType::class);

        $typeLabels = $itemsRelTypeRepository->list($itemId);

        return $typeLabels;
    }
}
