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

use MembersBundle\Entities\MemberOperateLog;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Data\MemberOperateLogData;
use OpenapiBundle\Services\BaseService;
use MembersBundle\Services\MemberOperateLogService as BaseMemberOperateLogService;

class MemberOperateLogService extends BaseService
{
    public function getEntityClass(): string
    {
        // ShopEx EcShopX Business Logic Layer
        return MemberOperateLog::class;
    }

    /**
     * 对外的类型映射表
     */
    public const TYPE_MAP = [
        BaseMemberOperateLogService::OPERATE_TYPE_INFO => 1,
        BaseMemberOperateLogService::OPERATE_TYPE_MOBILE => 2,
        BaseMemberOperateLogService::OPERATE_TYPE_GRADE_ID => 3,
    ];

    public function list(array $filter, int $page = 1, int $pageSize = 10, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        $result = $this->getRepository()->lists($filter, $orderBy, $pageSize, $page);
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 保存会员信息的修改操作
     * @param int $companyId
     * @param int $userId
     */
    public function saveInfo(int $companyId, int $userId)
    {
        $oldAndNewData = MemberOperateLogData::instance()->get();
        if (empty($oldAndNewData)) {
            return;
        }
        $this->create([
            'user_id' => $userId,
            'company_id' => $companyId,
            'operate_type' => \MembersBundle\Services\MemberOperateLogService::OPERATE_TYPE_INFO,
            'old_data' => json_encode($oldAndNewData['old'] ?? [], JSON_UNESCAPED_UNICODE),
            'new_data' => json_encode($oldAndNewData['new'] ?? [], JSON_UNESCAPED_UNICODE),
            'operater' => CommonConstant::OPERATER,
        ]);
    }

    /**
     * 保存会员信息的修改操作
     * @param int $companyId
     * @param int $userId
     */
    public function saveMobile(int $companyId, int $userId)
    {
        $oldAndNewData = MemberOperateLogData::instance()->get();
        if (empty($oldAndNewData)) {
            return;
        }
        $this->create([
            'user_id' => $userId,
            'company_id' => $companyId,
            'operate_type' => \MembersBundle\Services\MemberOperateLogService::OPERATE_TYPE_MOBILE,
            'old_data' => $oldAndNewData['old']["mobile"] ?? "",
            'new_data' => $oldAndNewData['new']["mobile"] ?? "",
            'operater' => CommonConstant::OPERATER,
        ]);
    }
}
