<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
