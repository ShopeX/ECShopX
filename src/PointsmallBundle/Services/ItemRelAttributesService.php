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

namespace PointsmallBundle\Services;

use PointsmallBundle\Entities\PointsmallItemRelAttributes;

class ItemRelAttributesService
{
    public $ItemRelAttributes;
    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct()
    {
        $this->ItemRelAttributes = app('registry')->getManager('default')->getRepository(PointsmallItemRelAttributes::class);
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
