<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Tests\Services;

use CompanysBundle\Services\Shops\ProtocolService;

class ProtocolTest extends \EspierBundle\Services\TestBaseService
{
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new ProtocolService($this->getCompanyId());
    }

    /**
     * 测试 设置协议
     */
    public function testSet()
    {
        $bool = $this->service->set(ProtocolService::TYPE_PRIVACY, [
            "title" => "11",
            "content" => "aa"
        ]);
        $this->assertTrue($bool);
    }

    /**
     * 测试 获取单个协议
     */
    public function testGet()
    {
        $data = $this->service->get([ProtocolService::TYPE_MEMBER_REGISTER, ProtocolService::TYPE_PRIVACY]);
        $this->assertTrue(is_array($data));
    }
}
