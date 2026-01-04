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

namespace ThirdPartyBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use ThirdPartyBundle\Services\Map\MapService;

class MapTest extends TestBaseService
{
    protected $companyId = 1;

    protected $region = "上海";
    protected $keyword = "宜山路700号";

    protected $amapData = [
        "lng" => "121.4177321", // 经度
        "lat" => "31.175441", // 纬度
    ];

    protected $tencentData = [
        "lng" => "121.41778", // 经度
        "lat" => "31.17538", // 纬度
    ];

    /**
     * 测试 - 配置的保存
     */
    public function testSetConfig()
    {
        $service = new MapService();
        $amap = $service->setConfig($this->companyId, MapService::TYPE_AMAP, [
            "is_default" => MapService::DEFAULT_NO,
            "app_key" => "aca887055f1cf23a7413e92b48909f95",
            "app_secret" => "c2451998ef5605815b215bc74eabff70"
        ]);
        $tencent = $service->setConfig($this->companyId, MapService::TYPE_TENCENT, [
            "is_default" => MapService::DEFAULT_YES,
            "app_key" => "MORBZ-IQHKF-Y7SJF-JRHDT-FT6Y2-NSBSL",
            "app_secret" => "Wn4Y0K6Ezh4CDoKnI86p5Pm43K336os"
        ]);
        $this->assertTrue(!empty($amap) && !empty($tencent));
    }

    /**
     * 测试 - 配置的列表
     */
    public function testGetConfigList()
    {
        $list = (new MapService())->getConfigList($this->companyId);
        $this->assertTrue(!empty($list["list"]));
    }

    /**
     * 测试 - 定位功能
     */
    public function testLatLngAndPosition()
    {
        $latAndLngData = MapService::make($this->companyId)->getLatAndLng($this->region, $this->keyword);
        $positionData = MapService::make($this->companyId)->getPosition($latAndLngData->getLat(), $latAndLngData->getLng());

        $this->assertTrue($latAndLngData->getLat() && $latAndLngData->getLng() && $positionData->getAddress() && $positionData->getAddressComponent());
    }

    /**
     * 测试 - 配置信息
     * @return void
     * @throws \Exception
     */
    public function testConfigInfo()
    {
        $configInfo = MapService::make($this->companyId)->getConfigInfo();
        $this->assertTrue(!empty($configInfo));
    }
}
