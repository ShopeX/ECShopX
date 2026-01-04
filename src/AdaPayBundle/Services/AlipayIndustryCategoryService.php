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

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayAlipayIndustryCategory;

class AlipayIndustryCategoryService
{
    public $adapayAlipayIndustryCategoryRepository;
    public function __construct()
    {
        // ShopEx EcShopX Service Component
        $this->adapayAlipayIndustryCategoryRepository = app('registry')->getManager('default')->getRepository(AdapayAlipayIndustryCategory::class);
    }

    /**
     * 递归实现无限极分类
     * @param $array 分类数据
     * @param $pid 父ID
     * @param $level 分类级别
     * @return $list 分好类的数组 直接遍历即可 $level可以用来遍历缩进
     */

    public function getTree($array, $pid = 0, $level = 1)
    {
        // ShopEx EcShopX Service Component
        $list = [];
        foreach ($array as $k => $v) {
            $v['children'] = [];
            if ($v['parent_id'] == $pid) {
                $v['children'] = $this->getTree($array, $v['id'], $level + 1);
                if ($v['category_level'] == 3) {
                    unset($v['children']);
                }
                $list[] = $v;
            }
        }
        return $list;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->adapayAlipayIndustryCategoryRepository->$method(...$parameters);
    }
}
