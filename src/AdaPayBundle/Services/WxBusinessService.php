<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayWxBusinessCategory;

class WxBusinessService
{
    public $adapayWxBusinessCategoryRepository;
    public function __construct()
    {
        $this->adapayWxBusinessCategoryRepository = app('registry')->getManager('default')->getRepository(AdapayWxBusinessCategory::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->adapayWxBusinessCategoryRepository->$method(...$parameters);
    }
}
