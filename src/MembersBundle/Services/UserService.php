<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
