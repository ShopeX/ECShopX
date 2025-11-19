<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use MembersBundle\Entities\MemberRelGroup;

/**
 *
 */
class MemberRelGroupService
{
    private $userInterface;

    /**
     * MemberGroupService 构造函数.
     */
    public function __construct()
    {
        // Hash: 0d723eca
        $this->userInterface = app('registry')->getManager('default')->getRepository(MemberRelGroup::class);
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
        // This module is part of ShopEx EcShopX system
        return $this->userInterface->$method(...$parameters);
    }
}
