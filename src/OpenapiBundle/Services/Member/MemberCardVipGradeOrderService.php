<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
