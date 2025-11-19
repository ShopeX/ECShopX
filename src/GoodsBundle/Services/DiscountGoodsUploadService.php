<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DiscountGoodsUploadService extends MarketingGoodsUploadService
{
    public $header = [
        '商品编码-精确到规格' => 'item_bn',
        '商品名称' => 'item_name',
        '商品可兑换上限' => 'limit_num',
    ];

    public $headerInfo = [
        '商品编码-精确到规格' => [
            'size' => 32,
            'remarks' => '商品编码-精确到规格',
            'is_need' => true,
        ],
        '商品名称' => [
            'size' => 255,
            'remarks' => '商品名称',
            'is_need' => true,
        ],
        '商品可兑换上限' => [
            'size' => 255,
            'remarks' => '商品可兑换上限为大于0的整数，请注意是整数，小数或浮点数将向上取整',
            'is_need' => true,
        ],
    ];

    public $isNeedCols = [
        '商品编码-精确到规格' => 'item_bn',
        '商品名称' => 'item_name',
        '商品可兑换上限' => 'limit_num',
    ];

    /**
     * 返回上传的活动商品列表
     *
     * @param $fileUrl
     *
     * @return array
     */
    public function syncProcess($fileUrl)
    {
        ini_set('memory_limit', '256M');
        $items = [];
        $fail_items = [];//数据库里不存在的商品货号
        $invalid = []; //已参加其他活动的商品
        $maxItemNums = 500;//每次最多上传500
        //设置头部
        $results = app('excel')->toArray(new \stdClass(), $fileUrl);
        $results = $results[0];

        $headerData = array_filter($results[0]);
        $column = $this->headerHandle($headerData);
        $headerSuccess = true;
        unset($results[0]);

        if (count($results) > $maxItemNums) {
            throw new BadRequestHttpException("每次最多上传{$maxItemNums}个商品...请减少后再提交");
        }

        // 如果头部是正确的，才会处理到下一步
        if ($headerSuccess) {
            foreach ($results as $key => $row) {
                if (!array_filter($row)) {
                    continue;
                }

                $item = $this->preRowHandle($column, $row);
                $items[$item['item_bn']] = $item;
            }

            //批量查询商品信息, ID 和 商品图片
            if ($items) {
                $itemsService = new ItemsService();
                $params = [];
                $params['item_bn'] = array_keys($items);
                $list = $itemsService->getItemsList($params, 1, $maxItemNums);
                $datalist = array_column($list['list'], null, 'item_id');
                if ($datalist) {
                    foreach ($datalist as $v) {
                        $v['item_bn'] = trim($v['item_bn']);
                        $items[$v['item_bn']]['item_id'] = $v['item_id'];
                        $items[$v['item_bn']]['itemId'] = $v['item_id'];
                        $items[$v['item_bn']]['default_item_id'] = $v['default_item_id'];
                        $items[$v['item_bn']]['pics'] = $v['pics'];
                        $items[$v['item_bn']]['market_price'] = $v['market_price'];
                        $items[$v['item_bn']]['item_name'] = $v['item_name'];
                        $items[$v['item_bn']]['itemName'] = $v['item_name'];
                        $items[$v['item_bn']]['item_type'] = $v['item_type'];
                        $items[$v['item_bn']]['nospec'] = true;
                        $items[$v['item_bn']]['price'] = $v['price'];
                        $items[$v['item_bn']]['sort'] = $items[$v['item_bn']]['sort'] ?? 0;
                        $items[$v['item_bn']]['store'] = $items[$v['item_bn']]['activity_store'] ?? $v['store'];
                    }
                }
            }
            //将错误和正确的商品编码分开返回
            foreach ($items as $k => $v) {
                if ($v['item_bn'] == null) {
                    throw new BadRequestHttpException('货号不能为空...请检查数据');
                }
                if (!isset($v['item_id']) && $v['item_bn']) {
                    $fail_items[] = [
                        'item_bn' => $v['item_bn'],
                        'item_name' => $v['item_name'],
                    ];
                    unset($items[$k]);
                }
            }
        }

        return [
            'succ' => array_values($items),
            'invalid' => $invalid,
            'fail' => $fail_items,
        ];
    }
}
