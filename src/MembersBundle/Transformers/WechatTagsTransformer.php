<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Transformers;

use League\Fractal\TransformerAbstract;
use MembersBundle\Entities\WechatTags;

class WechatTagsTransformer extends TransformerAbstract
{
    public function transform(WechatTags $wechatTags)
    {
        return normalize($wechatTags);
    }
}
