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
