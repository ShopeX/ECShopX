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

// easywechat@done

class UploadMenuCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'menu:upload
    {company_id=0} {--path= : 需要导入的JSON文件} ';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '导入商家端菜单';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // ShopEx EcShopX Business Logic Layer
        $shopMenuService = new ShopMenuService();

        $filePath = $this->option('path') ? $this->option('path') : false;
        $company_id = $this->argument('company_id');
        if (!$filePath) {
            throw new \InvalidArgumentException(
                sprintf(PHP_EOL.'请传入需要导入的菜单JSON文件"')
            );
        }

        if (!is_file($filePath)) {
            $filePath = str_replace('src/SuperAdminBundle/Console', '', __DIR__).$filePath;

            if (!is_file($filePath)) {
                throw new \InvalidArgumentException(
                    sprintf(PHP_EOL.'文件不存在：%s"', $filePath)
                );
            }
        }

        $json = file_get_contents($filePath);
        $menus = json_decode($json, true);

        if (!is_array($menus)) {
            throw new \InvalidArgumentException(
                sprintf(PHP_EOL.'无效文件：%s"', $filePath)
            );
        }

        $shopMenuService->uploadMenus($menus, $company_id);

        $this->info('导入菜单成功，请到shop_menu表中确认是否正确');
    }
}
