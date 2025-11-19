<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Tests;

use EspierBundle\Services\TestBaseService;
use WechatBundle\Services\OfficialAccountService;

class OfficialAccountTest extends TestBaseService
{
    protected $app;

    protected $service;

    protected function init()
    {
        $this->app = app("easywechat.official_account", [
            "status" => true,
            "app_id" => "wx6b8c2837f47e8a09",
            "secret" => "3fa50afe25d6613edc1d8de113b96d88"
        ]);
        $this->service = new OfficialAccountService($this->app);
    }

    /**
     * 测试 微信公众号的实例化
     * @return void
     */
    public function testApp()
    {
        $this->init();
        $this->assertTrue($this->app instanceof \EasyWeChat\OfficialAccount\Application);
    }

    /**
     * 测试根据用户授权成功后返回的code来获取用户信息
     * @return void
     */
    public function testGetUserInfo()
    {
        $this->init();
        $data = $this->service->getUserInfoByCode("021kdVGa1Hn8sC0UtmHa1tui080kdVGb");
        $this->assertTrue(!is_null($data));
    }
}
