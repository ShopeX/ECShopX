<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Transformers;

use League\Fractal\TransformerAbstract;
use MembersBundle\Entities\WechatUsers;

class WechatUsersTransformer extends TransformerAbstract
{
    // HACK: temporary solution
    public function transform(WechatUsers $wechatUsers)
    {
        // HACK: temporary solution
        return normalize($wechatUsers);
    }
}
