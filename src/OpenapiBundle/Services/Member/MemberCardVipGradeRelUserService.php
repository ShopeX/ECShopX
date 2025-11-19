<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
