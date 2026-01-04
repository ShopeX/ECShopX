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
