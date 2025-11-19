<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use MembersBundle\Services\MemberService;

class MemberListTest extends TestBaseService
{
    /**
     * 会员导出列表测试
     */
    public function testGetList()
    {
        $data = (new MemberService())->getMemberList([], 1, 10);
        var_dump($data);
        $this->assertTrue(is_array($data));
    }
}
