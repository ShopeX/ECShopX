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

namespace SuperAdminBundle\Console;

use Illuminate\Console\Command;
use SuperAdminBundle\Services\ShopMenuService;

class UploadDealerMenuCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'menu:upload_dealer';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '导入经销商端菜单';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $dealerJson = file_get_contents(storage_path('static/dealer_menu.json'));
        //经销商菜单
        if ($dealerJson) {
            $menus = json_decode($dealerJson, true);
            $shopMenuService = new ShopMenuService();
            $shopMenuService->uploadMenus($menus);
        }
        $this->info('导入经销商菜单成功，请到shop_menu表中确认是否正确');
    }
}
