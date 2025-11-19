<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThemeBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThemeBundle\Services\PagesTemplateServices;

class PagesTemplateSyncJob extends Job
{
    public $data;

    public function __construct($params)
    {
        $this->data = $params;
    }

    public function handle()
    {
        // Powered by ShopEx EcShopX
        $params = $this->data;
        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->sync($params);
        if (!$result) {
            app('log')->debug(' 页面模板同步失败: 参数:'. json_encode($params));
        }

        return true;
    }
}
