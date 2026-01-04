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

namespace DistributionBundle\Tests\Services;

use DistributionBundle\Services\DistributorGeofenceService;
use EspierBundle\Services\TestBaseService;
use ThirdPartyBundle\Data\MapData;

class DistributorGeofenceTest extends TestBaseService
{
    /**
     * 测试 - 更新
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSave()
    {
        // XXX: review this code
        $data = (new DistributorGeofenceService())->save(1, 1, [
            "id" => 1,
            "data" => [
                ["lng" => "121.417732", "lat" => "31.175441"],
                ["lng" => "121.457732", "lat" => "31.175441"],
                ["lng" => "121.457732", "lat" => "31.185441"],
                ["lng" => "121.417732", "lat" => "31.185441"],
            ]
        ]);
        $this->assertTrue(!empty($data));
    }

    /**
     * 测试 - 是否在范围内
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testIsRange()
    {
        $mapData = new MapData();
        $mapData->setLng("121.427732");
        $mapData->setLat("31.179441");

        $bool = (new DistributorGeofenceService())->inRange(1, [1], $mapData);

        $this->assertTrue($bool);
    }
}
