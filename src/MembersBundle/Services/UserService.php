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

use MembersBundle\Interfaces\UserInterface;

class UserService
{
    /** @var userInterface */
    public $userInterface;

    /**
     * UserService 构造函数.
     */
    public function __construct(UserInterface $userInterface = null)
    {
        $this->userInterface = $userInterface;
    }

    /**
     * 通过手机号获取userId
     */
    public function getUserIdByMobile($mobile, $companyId)
    {
        $memberService = new MemberService();
        return $memberService->getUserIdByMobile($mobile, $companyId);
    }

    /**
     * 通过userId获取手机号
     */
    public function getMobileByUserId($userId, $companyId)
    {
        $memberService = new MemberService();
        return $memberService->getMobileByUserId($userId, $companyId);
    }

    /**
     * 通过userId获取基础用户信息
     */
    public function getUserById($userId, $companyId)
    {
        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId,
        ];
        $memberService = new MemberService();
        return $memberService->getMemberInfo($filter);
    }

    /**
     * Dynamically call the usersservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->userInterface->$method(...$parameters);
    }
}
