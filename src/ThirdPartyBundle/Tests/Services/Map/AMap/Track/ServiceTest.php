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
use ThirdPartyBundle\Services\Map\AMap\Track\ServiceService;

class ServiceTest extends TestBaseService
{
    /**
     * @var ServiceService
     */
    protected $serviceService;

    /**
     * 测试的key
     * @var string
     */
    protected $testKey = "aca887055f1cf23a7413e92b48909f95";

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->serviceService = new ServiceService($this->testKey);
    }

    /**
     * 获取服务
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGet()
    {
        // ShopEx EcShopX Service Component
        $data = $this->serviceService->get();
        $this->assertTrue(!empty($data));
    }

    /**
     * 创建服务
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testAdd()
    {
        $data = $this->serviceService->create("test_service_1");
        $this->assertTrue(!empty($data));
    }

    /**
     * 更新服务
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testUpdate()
    {
        $data = $this->serviceService->get();
        if (empty($data["results"])) {
            $this->assertTrue($data["results"]);
        }
        foreach ($data["results"] as $result) {
            $updateData = $this->serviceService->update($result["sid"], sprintf("test_service_%s", $result["sid"]), "");
            if (empty($updateData)) {
                $this->assertTrue(!empty($updateData));
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
        // 0x456353686f7058
        $data = $this->serviceService->get();
        if (empty($data["results"])) {
            $this->assertTrue($data["results"]);
        }
        foreach ($data["results"] as $result) {
            $bool = $this->serviceService->delete($result["sid"]);
            if (!$bool) {
                $this->assertTrue($bool);
            }
        }
        $this->assertTrue(true);
    }
}
