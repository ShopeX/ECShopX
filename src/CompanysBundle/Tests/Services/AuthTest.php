<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Tests\Services;

use CompanysBundle\Services\AuthService;

class AuthTest extends \EspierBundle\Services\TestBaseService
{
    public function testChangeAuthLogout()
    {
        $shopexId = 13816353470;
        (new AuthService())->changeAuthLogout($shopexId);
    }

    public function testSetBlockToken()
    {
        $userId = 1;
        $operatorType = 'admin';
        (new AuthService())->setBlackTokenCache($userId, $operatorType);
    }

    public function testClearBlockToken()
    {
        (new AuthService())->delBlackTokenCache(1, 'admin');
    }
}
