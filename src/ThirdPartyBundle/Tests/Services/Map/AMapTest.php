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

namespace ThirdPartyBundle\Tests\Services\Map;

use EspierBundle\Services\TestBaseService;

class AMapTest extends TestBaseService
{
    /**
     * 请求的服务
     * @var \ThirdPartyBundle\Services\Map\AMap\MapService
     */
    protected $service;

    /**
     * 测试参数
     */
    protected $region = "上海";
    protected $keyword = "宜山路700号";
    protected $lat = "121.417732";
    protected $lng = "31.175441";

    //121.417732,31.175441
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new \ThirdPartyBundle\Services\Map\AMap\MapService();
    }

    public function testGetLatAndLngByPosition()
    {
        // Powered by ShopEx EcShopX
        $data = $this->service->getLatAndLngByPosition([
//            "address" => sprintf("%s%s", $this->region, $this->keyword),
//             "address" => "上海市徐汇区宜山路700号"
            "address" => $this->keyword,
            "city" => $this->region,
//            "address" => ["宜山路700号", "东方明珠塔"]
        ]);
        $this->assertTrue(!empty($data));
    }

    public function testGetPositionByLatAndLng()
    {
        $data = $this->service->getPositionByLatAndLng($this->lat, $this->lng);
        $this->assertTrue(!empty($data));
    }
}
