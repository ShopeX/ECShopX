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

namespace EspierBundle\Tests\Services\Config;

use EspierBundle\Services\Config\ConfigRequestFieldsService;

class ConfigRequestFieldsTest extends \EspierBundle\Services\TestBaseService
{
    /**
     * 配置服务
     * @var ConfigRequestFieldsService
     */
    protected $service;

    /**
     * 模块类型
     * @var int
     */
    protected $moduleType = ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new ConfigRequestFieldsService();
    }

    /**
     * 测试初始化
     */
    public function testInit()
    {
        $status = $this->service->init($this->getCompanyId(), $this->moduleType);
        $this->assertTrue($status);
    }

    /**
     * 测试创建功能
     */
    public function testCreate()
    {
        $data = $this->service->create($this->getCompanyId(), $this->moduleType, [
            'label' => "aaaaa",
            'key_name' => "aaaaa",
            'is_open' => true,
            'is_required' => true,
            'is_edit' => false,
//            'field_type'             => ConfigRequestFieldsService::FIELD_TYPE_RADIO,
            'field_type' => ConfigRequestFieldsService::FIELD_TYPE_NUMBER,
            "radio_list" => [
                ["value" => 0, "label" => "未知"],
                ["value" => 1, "label" => "男性"],
                ["value" => 2, "label" => "女性"],
            ],
            "range" => [
                ["start" => 0, "end" => 999]
            ],
            'alert_required_message' => "aaaaa必填",
            'alert_validate_message' => "",
        ]);
        $stdClass = new \stdClass();
        $stdClass->id = $data["id"] ?? 0;
        $this->assertTrue($stdClass->id > 0);
    }

    /**
     * 测试分页功能
     */
    public function testPaginate()
    {
        $list = $this->service->paginate($this->getCompanyId(), ["module_type" => $this->moduleType], 1, 10);
        $this->assertTrue(is_array($list));
    }

    /**
     * 测试列表功能
     */
    public function testGetList()
    {
        $list = $this->service->getList($this->getCompanyId(), ["module_type" => $this->moduleType]);
        $this->assertTrue(is_array($list));
    }

    /**
     * 测试单条查询功能
     */
    public function testGetInfo()
    {
        $info = $this->service->getInfo($this->getCompanyId(), ["id" => 8]);
        $this->assertTrue(is_array($info));
    }

    /**
     * 测试更新开关的功能
     */
    public function testUpdateSwitch()
    {
        $bool = $this->service->updateSwitch($this->getCompanyId(), 8, ConfigRequestFieldsService::SWITCH_COLUMN_IS_REQUIRED, false);
        $this->assertTrue($bool);
    }

    /**
     * 测试更新数据的功能
     */
    public function testUpdateInfo()
    {
        $data = $this->service->updateInfo($this->getCompanyId(), 8, [
            "label" => "手机号",
//            "key_name"           => "mobile",
            "field_type" => ConfigRequestFieldsService::FIELD_TYPE_NUMBER,
            "radio_list" => [
                ["value" => 0, "label" => "未知"],
                ["value" => 1, "label" => "男性"],
                ["value" => 2, "label" => "女性"],
            ],
            "range" => [
                ["start" => 0, "end" => 999]
            ],
        ]);
        $stdClass = new \stdClass();
        $stdClass->id = $data["id"] ?? 0;
        $this->assertTrue($stdClass->id > 0);
    }

    /**
     * getListAndHandleSettingFormat
     */
    public function testGetListAndHandleSettingFormat()
    {
        $data = $this->service->getListAndHandleSettingFormat($this->getCompanyId(), $this->moduleType);
        $this->assertTrue(is_array($data));
    }
}
