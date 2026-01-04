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
use ThirdPartyBundle\Services\Map\AMap\Track\GeofenceService;

class GeofenceTest extends TestBaseService
{
    /**
     * @var GeofenceService
     */
    protected $geofenceService;

    /**
     * 测试的key
     * @var string
     */
    protected $testKey = "aca887055f1cf23a7413e92b48909f95";

    /**
     * 测试的服务id
     * @var string
     */
    protected $testSid = "548518";

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->geofenceService = new GeofenceService($this->testKey, $this->testSid);
    }

    /**
     * 创建圆形围栏
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testCreate()
    {
        $data = $this->geofenceService->create(GeofenceService::TYPE_CIRCLE, "test_geofence_circle", null, [
            "center" => "121.417732,31.175441",
            "radius" => 50000
        ]);
        $this->assertTrue(!empty($data));
    }

    /**
     * 更新围栏
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testUpdate()
    {
        $status = $this->geofenceService->update("394120", GeofenceService::TYPE_CIRCLE, "test_geofence_circle", null, [
            "center" => "121.417732,31.175441",
            "radius" => 20000
        ]);
        $this->assertTrue($status);
    }

    /**
     * 获取围栏数据
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGet()
    {
        $data = $this->geofenceService->get([
            "outputshape" => 1
        ]);
        $this->assertTrue(!empty($data["count"]));
    }

    /**
     * 判断坐标与围栏的关系
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testCheck()
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $data = $this->geofenceService->check([
            "location" => "121.417732,31.175441",
            "gfids" => "394120"
        ]);
        $this->assertTrue(!empty($data["count"]));
    }
}
