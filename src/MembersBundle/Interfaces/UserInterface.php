<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Interfaces;

interface UserInterface
{
    /**
     * Create User
     *
     * @param  userInfo  $userInfo
     * @return
     */
    public function create(array $userInfo);

    /**
     * get user info
     *
     * @param  filter
     * @return array
     */
    public function getUserInfo($filter);

    /**
     * update UserInfo
     *
     * @param data
     * @param filter
     * @return
     */
    public function update($data, $filter);
}
