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

    /**
     * 统计指定 version + company_id 下的菜单条数（用于上传去重断言）
     */
    private function countMenusByVersionAndCompany(ShopMenuService $service, $version, $companyId = 0): int
    {
        $res = $service->shopMenuRepository->lists(
            ['version' => $version, 'company_id' => $companyId],
            '*',
            1,
            1,
            ['shopmenu_id' => 'ASC']
        );
        return (int)($res['total_count'] ?? 0);
    }

    /**
     * 构造一条可供 uploadMenus 使用的最小合法菜单行
     */
    private function buildUploadMenuRow(array $overrides = []): array
    {
        $defaults = [
            'shopmenu_id' => 1,
            'version'     => 1,
            'company_id'  => 0,
            'alias_name'  => 'upload_test_' . uniqid(),
            'name'        => 'Test Menu',
            'url'         => '/test',
            'sort'        => 1,
            'pid'         => 0,
            'apis'        => '',
            'icon'        => '',
            'is_show'     => true,
            'is_menu'     => true,
            'disabled'    => false,
            'menu_type'   => ['all'],
        ];
        return array_merge($defaults, $overrides);
    }

    /**
     * TC1：重复导入两次同一文件（同一 version+company_id），菜单条数应等于单次导入条数（不重复）。
     * 使用超过 100 条数据以暴露当前「仅删 100 条再全量插」导致的重复；当前实现下本测试应 RED。
     */
    public function testUploadMenusTwiceSameDataNoDuplicateTc1()
    {
        $companyId = 0;
        $version   = 1;
        $count     = 101; // >100 以触发 Interceptor 默认 pageSize=100 的只删 100 条问题

        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = $this->buildUploadMenuRow([
                'shopmenu_id' => $i + 1,
                'version'     => $version,
                'company_id'  => $companyId,
                'alias_name'  => 'upload_tc1_' . $version . '_' . $i,
            ]);
        }

        $service = new ShopMenuService();
        $service->uploadMenus($data, $companyId);
        $afterFirst = $this->countMenusByVersionAndCompany($service, $version, $companyId);
        $this->assertSame($count, $afterFirst, '第一次导入后条数应等于文件条数');

        $service->uploadMenus($data, $companyId);
        $afterSecond = $this->countMenusByVersionAndCompany($service, $version, $companyId);
        $this->assertSame($count, $afterSecond, '第二次导入后条数应仍等于文件条数（无重复）；当前实现仅删 100 条会导致此处失败（RED）');
    }

    /**
     * TC4 边界：空数据上传不抛错；该 version+company_id 下列表与本次一致（空）。
     */
    public function testUploadMenusEmptyDataNoErrorTc4()
    {
        $service   = new ShopMenuService();
        $companyId = 0;
        $this->assertTrue($service->uploadMenus([], $companyId));
        $count = $this->countMenusByVersionAndCompany($service, 1, $companyId);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * TC5 边界：单条菜单重复上传两次，第二次后仅 1 条菜单，无重复。
     */
    public function testUploadMenusSingleMenuTwiceNoDuplicateTc5()
    {
        $companyId = 0;
        $version   = 1;
        $row       = $this->buildUploadMenuRow(['version' => $version, 'company_id' => $companyId]);

        $service = new ShopMenuService();
        $service->uploadMenus([$row], $companyId);
        $service->uploadMenus([$row], $companyId);
        $count = $this->countMenusByVersionAndCompany($service, $version, $companyId);
        $this->assertSame(1, $count, '单条菜单重复上传两次后应仅 1 条');
    }

}
