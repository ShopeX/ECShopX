<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Transformers;

use League\Fractal\TransformerAbstract;
use CompanysBundle\Entities\WxShops;

class WxShopsTransformer extends TransformerAbstract
{
    public function transform(WxShops $wxShops)
    {
        return normalize($wxShops);
    }
}
