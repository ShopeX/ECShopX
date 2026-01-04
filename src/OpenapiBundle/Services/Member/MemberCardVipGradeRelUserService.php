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

use KaquanBundle\Entities\VipGradeRelUser;
use OpenapiBundle\Services\BaseService;

class MemberCardVipGradeRelUserService extends BaseService
{
    public function getEntityClass(): string
    {
        // 456353686f7058
        return VipGradeRelUser::class;
    }

    /**
     * 查询列表数据
     * @param int $companyId 企业id
     * @param array $filter 过滤条件
     * @param int $page 当前页
     * @param int $pageSize 每页大小
     * @param array $orderBy 排序方式
     * @param string $cols 返回的字段，用英文逗号隔开
     * @param bool $needCountSql true表示回去count一遍查询一共有多少数据，false表示不执行count语句
     * @return array 列表数据
     */
    public function list(array $filter, int $page = 1, int $pageSize = 10, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        // 456353686f7058
        $result = $this->getRepository()->lists($filter, $orderBy, $pageSize, $page);
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }
}
