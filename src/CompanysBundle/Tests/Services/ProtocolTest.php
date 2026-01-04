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

namespace CompanysBundle\Tests\Services;

use CompanysBundle\Services\Shops\ProtocolService;

class ProtocolTest extends \EspierBundle\Services\TestBaseService
{
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new ProtocolService($this->getCompanyId());
    }

    /**
     * 测试 设置协议
     */
    public function testSet()
    {
        $bool = $this->service->set(ProtocolService::TYPE_PRIVACY, [
            "title" => "11",
            "content" => "aa"
        ]);
        $this->assertTrue($bool);
    }

    /**
     * 测试 获取单个协议
     */
    public function testGet()
    {
        $data = $this->service->get([ProtocolService::TYPE_MEMBER_REGISTER, ProtocolService::TYPE_PRIVACY]);
        $this->assertTrue(is_array($data));
    }
}
