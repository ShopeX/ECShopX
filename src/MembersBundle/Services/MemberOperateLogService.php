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

namespace MembersBundle\Services;

use MembersBundle\Entities\MemberOperateLog;

class MemberOperateLogService
{
    private $entityRepository;

    /**
     * 操作类型
     */
    public const OPERATE_TYPE_INFO = "info"; // 修改会员信息
    public const OPERATE_TYPE_MOBILE = "mobile"; // 修改手机号
    public const OPERATE_TYPE_GRADE_ID = "grade_id"; // 修改会员等级
    public const OPERATE_TYPE_MAP = [
        self::OPERATE_TYPE_INFO => "修改会员信息",
        self::OPERATE_TYPE_MOBILE => "修改手机号",
        self::OPERATE_TYPE_GRADE_ID => "修改会员等级",
    ];

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MemberOperateLog::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
