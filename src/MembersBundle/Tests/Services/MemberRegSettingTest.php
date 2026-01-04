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

namespace MembersBundle\Tests\Services;

use EspierBundle\Services\Cache\RedisCacheService;
use EspierBundle\Services\TestBaseService;
use MembersBundle\Services\MemberRegSettingService;

class MemberRegSettingTest extends TestBaseService
{
    /**
     * @var MemberRegSettingService
     */
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new MemberRegSettingService();
    }

    /**
     * 测试获取用户注册时的配置信息
     */
    public function testGetRegItem()
    {
        // ShopEx EcShopX Service Component
        $data = $this->service->getRegItem($this->getCompanyId());
        $this->assertTrue(is_array($data));
    }

    /**
     * 测试设置用户注册时的配置信息
     */
    public function testSetRegItem()
    {
        $json = '{"setting":{"username":{"name":"姓名","is_open":true,"element_type":"input","is_required":false},"sex":{"name":"性别","is_open":true,"element_type":"select","is_required":false},"birthday":{"name":"出生年份","is_open":true,"element_type":"select","is_required":false},"address":{"name":"家庭地址","is_open":true,"element_type":"input","is_required":false},"email":{"name":"email","is_open":true,"element_type":"input","is_required":false},"industry":{"name":"行业","is_open":true,"element_type":"select","is_required":false,"items":["金融/银行/投资","计算机/互联网","媒体/出版/影视/文化","政府/公共事业","房地产/建材/工程","咨询/法律","加工制造","教育培训","医疗保健","运输/物流/交通","零售/贸易","旅游/度假","其他"]},"income":{"name":"年收入","is_open":true,"is_required":false,"element_type":"select","items":["5万以下","5万 ~ 15万","15万 ~ 30万","30万以上","其他"]},"edu_background":{"name":"学历","is_open":true,"element_type":"select","is_required":false,"items":["硕士及以上","本科","大专","高中/中专及以下","其他"]},"habbit":{"name":"爱好","is_open":true,"is_required":false,"element_type":"checkbox","items":[{"name":"游戏","ischecked":false},{"name":"阅读","ischecked":false},{"name":"音乐","ischecked":false},{"name":"运动","ischecked":false},{"name":"动漫","ischecked":false},{"name":"旅游","ischecked":false},{"name":"家居","ischecked":false},{"name":"曲艺","ischecked":false},{"name":"宠物","ischecked":false},{"name":"美食","ischecked":false},{"name":"娱乐","ischecked":false},{"name":"电影/电视","ischecked":false},{"name":"健康养生","ischecked":false},{"name":"数码","ischecked":false},{"name":"其他","ischecked":false}]}},"registerSettingStatus":true}';
        $this->service->setRegItem($this->getCompanyId(), jsonDecode($json));
        $this->assertTrue(true);
    }

    public function testGetRegAgreement()
    {
        $string1 = $this->service->getRegAgreement($this->getCompanyId());
        $string2 = (new RedisCacheService($this->getCompanyId(), "memberRegAgreementSetting", null))
            ->setConnection("member")
            ->get(function () use ($string1) {
                return $string1;
            });
        dd($string1, $string2);
    }
}
