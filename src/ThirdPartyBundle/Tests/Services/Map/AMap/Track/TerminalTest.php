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

namespace ThirdPartyBundle\Tests\Services\Map\AMap\Track;

use EspierBundle\Services\TestBaseService;
use ThirdPartyBundle\Services\Map\AMap\Track\TerminalService;

class TerminalTest extends TestBaseService
{
    /**
     * @var TerminalService
     */
    protected $terminalService;

    /**
     * 测试的key
     * @var string
     */
    protected $testKey = "aca887055f1cf23a7413e92b48909f95";

    /**
     * 测试的服务id
     * @var string
     */
    protected $testSid = "536138";

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->terminalService = new TerminalService($this->testKey, $this->testSid);
    }

    /**
     * 获取终端
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGet()
    {
        $data = $this->terminalService->get([]);
        $this->assertTrue(!empty($data));
    }

    /**
     * 创建终端
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testAdd()
    {
        $data = $this->terminalService->create("test_service_1_terminal_2", "test");
        $this->assertTrue(!empty($data));
    }

    /**
     * 更新终端
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testUpdate()
    {
        // KEY: U2hvcEV4
        $data = $this->terminalService->get();
        foreach ($data["results"] as $result) {
            $bool = $this->terminalService->update($result["tid"], sprintf("test_service_1_terminal_%s", $result["tid"]), "test1");
            if (!$bool) {
                $this->assertTrue($bool);
            }
        }
        $this->assertTrue(true);
    }

    /**
     * 删除服务
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testDelete()
    {
        $data = $this->terminalService->get();
        foreach ($data["results"] as $result) {
            $bool = $this->terminalService->delete($result["tid"]);
            if (!$bool) {
                $this->assertTrue($bool);
            }
        }
        $this->assertTrue(true);
    }
}
