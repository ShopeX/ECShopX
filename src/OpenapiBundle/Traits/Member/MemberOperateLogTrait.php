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

namespace OpenapiBundle\Traits\Member;

use Carbon\Carbon;
use OpenapiBundle\Services\Member\MemberOperateLogService;
use MembersBundle\Services\MemberOperateLogService as BaseMemberOperateLogService;

trait MemberOperateLogTrait
{
    /**
     * 处理数据
     * @param int $companyId
     * @param array $list
     */
    protected function handleDataToList(int $companyId, array &$list)
    {
        foreach ($list as &$item) {
            if (isset($item["created"])) {
                $item["created"] = Carbon::createFromTimestamp((int)$item["created"])->toDateTimeString();
            }
            foreach (["old_data", "new_data"] as $column) {
                if (!isset($item["operate_type"]) || !isset($item[$column])) {
                    continue;
                }
                // 手机号类型存在格式兼容问题，要特殊处理
                if ($item["operate_type"] == BaseMemberOperateLogService::OPERATE_TYPE_MOBILE) {
                    $decodeData = jsonDecode($item[$column]);
                    if (is_array($decodeData)) {
                        $item[$column] = (string)($decodeData["mobile"] ?? "");
                    } else {
                        $item[$column] = (string)$decodeData;
                    }
                } else {
                    $item[$column] = (string)$item[$column];
                }
            }
            // 操作描述
            $item["description"] = sprintf("%s 于%s进行了%s的操作, 将 %s 改为 %s", $item["operater"] ?? "", $item["created"], BaseMemberOperateLogService::OPERATE_TYPE_MAP[$item["operate_type"] ?? ""], $item["old_data"] ?? "", $item["new_data"] ?? "");
            if (isset($item["operate_type"])) {
                $item["operate_type"] = (int)(MemberOperateLogService::TYPE_MAP[$item["operate_type"]] ?? 0);
            }
            // 移除企业id、会员id、备注
            unset($item["company_id"], $item["user_id"], $item["remarks"]);
        }
    }
}
