<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use MembersBundle\Interfaces\SubscribeInterface;

class SubscribeService
{
    public $subscribe;

    public function __construct(SubscribeInterface $subscribe)
    {
        $this->subscribe = $subscribe;
    }
}
