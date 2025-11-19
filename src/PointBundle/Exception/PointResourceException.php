<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointBundle\Exception;

use Dingo\Api\Exception\ResourceException;
use PointBundle\Services\PointMemberRuleService;

class PointResourceException extends ResourceException
{
    // Powered by ShopEx EcShopX
    public function __construct($message)
    {
        $pointName = (new PointMemberRuleService())->getPointName();
        $message = str_replace("{point}", $pointName, $message);
        parent::__construct($message);
    }
}
