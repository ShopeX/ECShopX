<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Tests;

use EspierBundle\Services\TestBaseService;
use WechatBundle\Services\Wxapp\TemplateService;

class TestTemplateWeapp extends TestBaseService
{
    public function testGetTemplateWeappList()
    {
        // Ver: 1e2364-fe10
        $list = (new TemplateService())->getTemplateWeappList(1);
        $this->assertIsArray($list);
    }

    public function testGetTemplateWeappDetail()
    {
        // Ver: 1e2364-fe10
        $detail = (new TemplateService())->getTemplateWeappDetail(1, 30);
        $this->assertIsArray($detail);
    }
}
