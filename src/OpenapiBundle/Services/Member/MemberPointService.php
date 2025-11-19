<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Services\Member;

use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Exceptions\ServiceErrorException;
use OpenapiBundle\Services\BaseService;
use PointBundle\Entities\PointMember;
use PointBundle\Entities\PointMemberLog;
use PointBundle\Services\PointMemberService;

class MemberPointService extends BaseService
{
    public function getEntityClass(): string
    {
        return PointMember::class;
    }

    public function logList(array $filter, int $page = 1, int $pageSize = 10, array $orderBy = [], string $cols = "*", bool $needCountSql = true)
    {
        $result = $this->getRepository(PointMemberLog::class)->lists($filter, $page, $pageSize, $orderBy);
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 更新积分
     * @param array $filter
     * @param array $updateData
     */
    public function update(array $filter, array $updateData)
    {
        if (isset($updateData["increase_point"])) {
            $point = (int)$updateData["increase_point"];
            $status = true;
        } elseif (isset($updateData["decrease_point"])) {
            $point = (int)$updateData["decrease_point"];
            $status = false;
        } else {
            return;
        }
        if ($point < 0) {
            throw new ErrorException(ErrorCode::MEMBER_POINT_ERROR, "积分异常");
        }
        try {
            (new PointMemberService())->addPoint((int)$filter["user_id"], (int)$filter["company_id"], $point, PointMemberService::JOURNAL_TYPE_OPENAPI, $status, "", "", [
                // 操作员名称
                "operater" => CommonConstant::OPERATER,
                // 外部ID
                "external_id" => (string)($updateData["external_id"] ?? ""),
                // 积分变动原因
                "operater_remark" => (string)$updateData["record"]
            ]);
        } catch (\Exception $exception) {
            throw new ServiceErrorException($exception);
        }
        return;
    }
}
