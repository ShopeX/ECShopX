<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemsRelPointAccess;

class ItemRelPointAccessService
{
    public $itemsRelPointAccess;
    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct()
    {
        $this->itemsRelPointAccess = app('registry')->getManager('default')->getRepository(ItemsRelPointAccess::class);
    }

    /**
    * 保存sku关联的获取积分
    */
    public function saveOneData($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'item_id' => $params['item_id'],
        ];
        $info = $this->getInfo($filter);
        if ($info) {
            return $this->updateOneBy($filter, $params);
        } else {
            return $this->create($params);
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->itemsRelPointAccess->$method(...$parameters);
    }
}
