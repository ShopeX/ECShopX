<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Interfaces;

interface SubscribeInterface
{
    public function create(array $subInfo);

    public function delete($filter);

    public function getList($filter);
}
