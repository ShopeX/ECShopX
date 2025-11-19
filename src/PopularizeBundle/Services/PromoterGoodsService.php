<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PopularizeBundle\Services;

use PopularizeBundle\Entities\PromoterGoods;

class PromoterGoodsService
{
    public $promoterGoodsRepository;

    public function __construct()
    {
        $this->promoterGoodsRepository = app('registry')->getManager('default')->getRepository(PromoterGoods::class);
    }

    public function __call($method, $parameters)
    {
        return $this->promoterGoodsRepository->$method(...$parameters);
    }
}
