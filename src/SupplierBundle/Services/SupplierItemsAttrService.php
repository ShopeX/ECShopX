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

namespace SupplierBundle\Services;

use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Entities\SupplierItemsAttr;

class SupplierItemsAttrService
{
    /**
     * @var \SupplierBundle\Repositories\SupplierItemsAttrRepository
     */
    public $repository;

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(SupplierItemsAttr::class);
    }

    //批量获取多个商品的关联属性信息
    public function getItemRelAttr($item_ids, $attribute_type = '')
    {
        $filter = [
            'item_id' => $item_ids,
            'attribute_type' => $attribute_type,
        ];
        $rs = $this->repository->getLists($filter);
        if (!$rs) {
            return [];
        }
        foreach ($rs as $k => $v) {
            if (!$v['attr_data']) continue;
            $attr_data = json_decode($v['attr_data'], true);
            if (is_array($attr_data[$v['attribute_type']])) {
                $v = array_merge($v, $attr_data[$v['attribute_type']]);
            } else {
                $v['attribute_value_id'] = $attr_data[$v['attribute_type']];
            }
            $v['image_url'] = $v['image_url'] ?? '';
            $v['custom_attribute_value'] = $v['custom_attribute_value'] ?? '';
            $rs[$k] = $v;
        }
        return $rs;
    }

    //批量获取多个商品的属性信息
    public function getAttrDataBatch($item_ids, $company_id, $attribute_type = '')
    {
        $filter = [
            'item_id' => $item_ids,
            'attribute_type' => $attribute_type,
        ];
        $rs = $this->repository->getLists($filter);
        if (!$rs) {
            return [];
        }
        $result = [];
        foreach ($rs as $v) {
            if (!$v['attr_data']) continue;
            $attr_data = json_decode($v['attr_data'], true);
            $result[$v['item_id']] = $attr_data[$v['attribute_type']];;
        }
        return $result;
    }

    //获取单个商品的属性
    public function getAttrData($item_id, $attribute_type)
    {
        $filter = [
            'item_id' => $item_id,
            'attribute_type' => $attribute_type,
        ];
        $rsAttr = $this->repository->getInfo($filter);
        if ($rsAttr && $rsAttr['attr_data']) {
            $attr_data = json_decode($rsAttr['attr_data'], true);
            return $attr_data[$attribute_type];
        }
        return [];
    }

    //获取单个商品的全部属性
    public function getAttrDataList($item_id, $attribute_type = [])
    {
        $res = [];
        $filter = [
            'item_id' => $item_id,
        ];
        if ($attribute_type) {
            $filter['attribute_type'] = $attribute_type;
        }
        $rsAttr = $this->repository->getLists($filter);
        if ($rsAttr) {
            foreach ($rsAttr as $v) {
                if ($v['attr_data']) {
                    $attr_data = json_decode($v['attr_data'], true);
                    if (is_array($attr_data[$v['attribute_type']])) {
                        $v = array_merge($v, $attr_data[$v['attribute_type']]);
                    }
                }
                $v['image_url'] = $v['image_url'] ?? '';
                $v['custom_attribute_value'] = $v['custom_attribute_value'] ?? '';
                $res[] = $v;
            }
        }
        return $res;
    }

    //标记要删除的数据
    public function setDelData($filter)
    {
        if ($this->repository->getInfo($filter)) {
            $this->repository->updateBy($filter, ['is_del' => 1]);
        }
    }

    //删掉多余的数据
    public function execDelData($filter)
    {
        $filter['is_del'] = 1;
        $this->repository->deleteBy($filter);
    }

    public function saveAttrData($filter, $attrData)
    {
        if (is_array($attrData)) {
            $attrData = json_encode($attrData, 256);
        }
        $rsAttr = $this->repository->getInfo($filter);
        if ($rsAttr) {
            $this->repository->updateOneBy(['id' => $rsAttr['id']], ['attr_data' => $attrData, 'is_del' => 0]);
        } else {
            $filter['attr_data'] = $attrData;
            $filter['is_del'] = 0;
            $rsAttr = $this->repository->create($filter);
        }
        return $rsAttr;
    }

    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }
}
