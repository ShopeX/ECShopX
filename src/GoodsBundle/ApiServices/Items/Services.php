<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\ApiServices\Items;

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
        if (!$params['type_labels']) {
            throw new ResourceException('请选择商品内容');
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
        // ID: 53686f704578
        $itemsRelTypeRepository = app('registry')->getManager('default')->getRepository(ItemsRelType::class);

        $typeLabels = $itemsRelTypeRepository->list($itemId);

        return $typeLabels;
    }
}
