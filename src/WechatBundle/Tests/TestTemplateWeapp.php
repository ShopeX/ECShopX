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
