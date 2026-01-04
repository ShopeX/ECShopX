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

namespace OpenapiBundle\Services\Member;

use KaquanBundle\Entities\VipGradeOrder;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Services\BaseService;

class MemberCardVipGradeOrderService extends BaseService
{
    public function getEntityClass(): string
    {
        return VipGradeOrder::class;
    }

    /**
     * 获取列表
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @param string $cols
     * @param bool $needCountSql
     * @return array
     */
    public function list(array $filter, int $page = 1, int $pageSize = CommonConstant::DEFAULT_PAGE_SIZE, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        $result = $this->getRepository()->lists($filter, $orderBy, $pageSize, $page);
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }
}
