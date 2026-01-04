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

namespace SuperAdminBundle\Tests;

use EspierBundle\Services\TestBaseService;
use SuperAdminBundle\Services\ShopMenuService;

class ShopMenuTest extends TestBaseService
{
    const ADD_JSON_DATA = '{"version":"1","is_menu":"true","is_show":"true","pid":"0","name":"\u6d4b\u8bd5\u6dfb\u52a0\u83dc\u5355","alias_name":"test_id","menu_type":["all", "b2c"],"url":"\/test_url","sort":"1","company_id":"0","icon":"test_icon"}';
    const GET_MENU_DATA = '{"disabled":0,"company_id":"0","version":"1"}';


    /**
     * 菜单添加测试
     *
     * @return void
     * @throws \Exception
     */
    public function testAddMenu()
    {
        $data = json_decode(self::ADD_JSON_DATA, true);
        $result = (new ShopMenuService())->create($data);
        var_dump($result);
        $this->assertIsArray($result);
    }

    /**
     * 父子类范围测试
     *
     * @return void
     */
    public function testCheckParentMenuType()
    {
        $shopMenuService = new ShopMenuService();

        $parent = [1];
        $son = [1,2,3,4];
        $result = $shopMenuService->checkParentMenuType($parent, $son);
        $this->assertTrue($result);

//        $parent = [2,3,4];
//        $son = [1];
//        $result = $shopMenuService->checkParentMenuType($parent, $son);
//        $this->assertFalse($result);

        $parent = [2,3,4];
        $son = [2,3,4];
        $result = $shopMenuService->checkParentMenuType($parent, $son);
        $this->assertTrue($result);


        $parent = [2,3,4];
        $son = [2,3,4,5];
        $result = $shopMenuService->checkParentMenuType($parent, $son);
        $this->assertFalse($result);
    }


    public function testGetMenuTree()
    {
        $testData = json_decode(self::GET_MENU_DATA, true);
        $menuData = (new ShopMenuService())->getShopMenu($testData);
        var_dump($menuData);
        $this->assertIsArray($menuData);
    }

}
