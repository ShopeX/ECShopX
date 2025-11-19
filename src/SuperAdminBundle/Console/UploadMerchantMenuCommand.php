<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SuperAdminBundle\Console;

use Illuminate\Console\Command;
use SuperAdminBundle\Services\ShopMenuService;

class UploadMerchantMenuCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'menu:upload_merchant';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '导入商户端菜单';

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
        $dealerJson = file_get_contents(storage_path('static/merchant_menu.json'));
        //商户菜单
        if ($dealerJson) {
            $menus = json_decode($dealerJson, true);
            $shopMenuService = new ShopMenuService();
            $shopMenuService->uploadMenus($menus);
        }
        $this->info('导入商户菜单成功，请到shop_menu表中确认是否正确');
    }
}
