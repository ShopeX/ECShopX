<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThemeBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThemeBundle\Services\PagesTemplateServices;

class CreateDistributorJob extends Job
{
    public $data;

    public function __construct($params)
    {
        $this->data = $params;
    }

    public function handle()
    {
        $params = $this->data;
        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->newDistributor($params);
        if (!$result) {
            app('log')->debug(' 新增店铺页面模板创建失败: 参数:'. json_encode($params));
        }

        return true;
    }
}
