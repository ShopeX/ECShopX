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

namespace PointBundle\Exception;

use Dingo\Api\Exception\ResourceException;
use PointBundle\Services\PointMemberRuleService;

class PointResourceException extends ResourceException
{
    // Powered by ShopEx EcShopX
    /**
     * @param string $message
     * @param int|string|null $companyId 无登录态（队列/命令行等）时用于解析「积分」名称；不传则仍尝试 auth，再不行用默认译名
     */
    public function __construct($message, $companyId = null)
    {
        $pointName = (new PointMemberRuleService())->getPointName($companyId);
        $message = str_replace("{point}", $pointName, $message);
        parent::__construct($message);
    }
}
