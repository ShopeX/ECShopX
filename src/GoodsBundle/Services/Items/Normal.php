<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Services\Items;

use GoodsBundle\Entities\Items;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\SupplierItems;

// 普通商品
class Normal
{
    public function preRelItemParams($data, $params)
    {
        $rules = [
            'rebate' => ['numeric|min:0', '请输入正确的分销佣金'],
            'store' => ['required|integer|min:0|max:999999999', '库存为0-999999999的整数'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            app('log')->debug("preRelItemParams_error =>:".json_encode($params, 256));
            throw new ResourceException($errorMessage);
        }

        if (isset($params['cost_price'])) {
            $data['cost_price'] = $params['cost_price'] ? bcmul($params['cost_price'], 100) : 0;
        }

        if (isset($params['rebate'])) {
            $data['rebate'] = $params['rebate'] ? bcmul($params['rebate'], 100) : 0;
        }

        $data['store'] = intval($params['store']);
        $itemId = $params['item_id'] ?? null;
        $operator_type = $data['operator_type'] ?? '';
        $data = $this->__checkItemBn($data, $itemId, $operator_type);
        // $data = $this->__checkSupplierGoodsBn($data, $itemId);
        return $data;
    }

    /**
     * 检查商品编号是否重复
     */
    private function __checkItemBn($data, $itemId = null, $operator_type = '')
    {
        if (empty($data['item_bn'])) {
            $data['item_bn'] = $this->__getBn('KC');
            //$data['item_bn'] = strtoupper(uniqid('s'));
        }

        if (empty($data['goods_bn'])) {
            $data['goods_bn'] = $this->__getBn('PC');;
//            $data['goods_bn'] = strtoupper(uniqid('g'));
        }

        if ($operator_type == 'supplier') {
            $itemsRepository = app('registry')->getManager('default')->getRepository(SupplierItems::class);
        } else {
            $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        }
        $itemData = $itemsRepository->getInfo(['item_bn' => $data['item_bn'], 'company_id' => $data['company_id']]);
        if (!$itemData) {
            return $data;
        }

        if (!$itemId) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.sku_code_duplicate') . $data['item_bn']);
        }

        if ($itemId && $itemData['item_id'] != $itemId) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.sku_code_cannot_duplicate') . $data['item_bn']);
        }

        return $data;
    }

    public function __getBn($prefix)
    {
        $today = date('ymd');;
        $skuCounterKey = $prefix."_counter_date:".$today;

        $skuCounter = (int)app('redis')->incr($skuCounterKey);
        if ($skuCounter == 1) {
            app('redis')->set($skuCounterKey, 1,'EX',86401);
        }

        return $prefix. $today . str_pad($skuCounter, 8, '0', STR_PAD_LEFT);

    }

    /**
     * 检查商品供应商货号是否重复
     */
    private function __checkSupplierGoodsBn($data, $itemId = null)
    {
        if(empty($data['supplier_goods_bn'])){
            return  $data;
        }
        $itemsRepository = app('registry')->getManager('default')->getRepository(SupplierItems::class);
        $itemData = $itemsRepository->getInfo(['supplier_goods_bn' => $data['supplier_goods_bn'], 'company_id' => $data['company_id']]);
        if (!$itemData) {
            return $data;
        }

        if (!$itemId) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.supplier_goods_bn_duplicate', ['{0}' => $data['supplier_goods_bn']]));
        }

        if ($itemId && $itemData['item_id'] != $itemId) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.supplier_goods_bn_duplicate_general'));
        }

        return $data;
    }
}
