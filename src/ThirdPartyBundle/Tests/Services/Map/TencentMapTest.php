<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Tests\Services\Map;

use EspierBundle\Services\TestBaseService;

class TencentMapTest extends TestBaseService
{
    /**
     * 请求的服务
     * @var \ThirdPartyBundle\Services\Map\Tencent\MapService
     */
    protected $service;

    /**
     * 测试参数
     */
    protected $region = "上海";
    protected $keyword = "宜山路700号";
    protected $lat = "39.984154";
    protected $lng = "116.307490";

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new \ThirdPartyBundle\Services\Map\Tencent\MapService();
    }

    /**
     * 测试获取经纬度的功能
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGetLngAndLat()
    {
        $lat = "";
        $lng = "";
        $this->service->getLngAndLat($lng, $lat, $this->region, $this->keyword);
        $this->assertTrue(!empty($lat) && !empty($lng));
    }

    /**
     * 测试定位功能
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testPosition()
    {
        $data = $this->service->position($this->region, $this->keyword);
        $this->assertTrue(!empty($data));
    }

    /**
     * 测试定位功能, 基于经纬度来定位
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testPositionByLatAndLng()
    {
        $data = $this->service->getPositionByLatAndLng("39.984154", "116.307490");
        $this->assertTrue(!empty($data));
    }

    /**
     * 测试定位功能, 基于具体地址来定位
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testPositionByAddress()
    {
        $data = $this->service->getLatAndLngByPosition([
            "address" => sprintf("%s%s", $this->region, $this->keyword)
        ]);
        $this->assertTrue(!empty($data));
    }
}
