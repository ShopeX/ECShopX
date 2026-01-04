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

namespace EspierBundle\Listeners;

use Illuminate\Console\Events\CommandFinished;
use SuperAdminBundle\Services\ShopMenuService;
use SuperAdminBundle\Http\SuperApi\V1\Action\Logistics;
use SuperAdminBundle\Services\LogisticsService;
use SuperAdminBundle\Entities\ShopMenu;
use SuperAdminBundle\Entities\ShopMenuRelType;

class UpdateMenuListener
{
    /**
     * Handle the event.
     *
     * @param  CommandFinished  $event
     * @return void
     */
    public function handle(CommandFinished $event)
    {
        // 获取已被执行的命令
        $command = $event->command;

        if ($command == 'doctrine:migrations:migrate') {
            // 初始化物流公司
            if ($this->initLogistics()) {
                echo sprintf("init logistics success\n");
            }
            // 更新菜单
            if ($this->updateSystemMenus()) {
                echo "update shop menus success!\n";
            }
        }
    }

    private function initLogistics()
    {
        try {
            $logisticsService = new LogisticsService();
            $logisticsData = $logisticsService->getInfo([]);
            if (!$logisticsData) {
                $logistics = new Logistics();
                $logistics->initLogistics();
                return true;
            }
        } catch (\Exception $e) {
        }
        return false;
    }

    private function updateSystemMenus()
    {
        if (!config('common.use_system_menu')) {
            return false;
        }
        // 平台后台菜单
        $json = file_get_contents(storage_path('static/platform_menu.json'));
        // IT端菜单
        $itjson = file_get_contents(storage_path('static/it_menu.json'));
        // 店铺菜单
        $shopJson = file_get_contents(storage_path('static/shop_menu.json'));
        // 经销商菜单
        $dealerJson = file_get_contents(storage_path('static/dealer_menu.json'));
        // 商户菜单
        $merchantJson = file_get_contents(storage_path('static/merchant_menu.json'));
        // 经销商菜单
        $supplierJson = file_get_contents(storage_path('static/supplier_menu.json'));
        //强烈要求的操作，就是重新导入，就代表什么都会重来一遍
        $shopMenuRepository = getRepositoryLangue(ShopMenu::class);
        $shopMenuRelTypeRepository = app('registry')->getManager('default')->getRepository(ShopMenuRelType::class);
        $shopMenuRepository->deleteBy(['company_id'=>0]);
        $shopMenuRelTypeRepository->deleteBy(['company_id'=>0]);

        try {
            // 平台管理后台采集
            $menus = json_decode($json, true);
            $shopMenuService = new ShopMenuService();
            $shopMenuService->uploadMenus($menus);
            // IT端菜单
            if ($itjson) {
                $menus = json_decode($itjson, true);
                $shopMenuService->uploadMenus($menus);
            }
            // 店铺菜单
            if ($shopJson) {
                $menus = json_decode($shopJson, true);
                $shopMenuService->uploadMenus($menus);
            }
            //经销商菜单
            if ($dealerJson) {
                $menus = json_decode($dealerJson, true);
                $shopMenuService->uploadMenus($menus);
            }
            //商户菜单
            if ($merchantJson) {
                $menus = json_decode($merchantJson, true);
                $shopMenuService->uploadMenus($menus);
            }
            //供应商菜单菜单
            if ($merchantJson) {
                $menus = json_decode($supplierJson, true);
                $shopMenuService->uploadMenus($menus);
            }
            return true;
        } catch (\Exception $e) {
            echo "更新菜单出错：".$e->getMessage();
        }
        return false;
    }
}
