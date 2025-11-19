<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\ApiServices;

use GoodsBundle\Entities\ItemRelAttributes;

class ItemRelAttributesService
{
    public $ItemRelAttributes;
    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct()
    {
        $this->ItemRelAttributes = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
    }

    public function getItemIdsByAttributeids($filter)
    {
        $ItemRelAttributesList = $this->ItemRelAttributes->lists($filter);
        $itemIds = array_column($ItemRelAttributesList['list'], 'item_id');
        return $itemIds;
    }



    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->ItemRelAttributes->$method(...$parameters);
    }
}
