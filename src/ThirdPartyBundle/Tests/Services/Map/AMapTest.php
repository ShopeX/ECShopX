<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
