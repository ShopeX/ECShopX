<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use MembersBundle\Services\MemberAddressService;

class MemberAddressTest extends TestBaseService
{
    /**
     * 测试用户地址的添加功能
     * @throws \Exception
     */
    public function testCreateAddress()
    {
        $data = (new MemberAddressService())->createAddress([
            "user_id" => 1,
            "province" => "广东省",
            "city" => "广州市",
            "county" => "海珠区",
            "adrdetail" => "新港中路397号",
            "is_def" => "1",
            "postalCode" => "510000",
            "telephone" => "17321265274",
            "username" => "张三",
            "company_id" => "1",
        ]);
        $this->assertTrue(is_array($data));
    }

    /**
     * 测试用户地址的获取列表功能
     */
    public function testGetList()
    {
        $data = (new MemberAddressService())->lists([], 1, 10, []);
        $this->assertTrue(is_array($data));
    }
}
