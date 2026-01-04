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

namespace EspierBundle\Commands;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\Config\ConfigRequestFieldsService;
use Illuminate\Console\Command;

class InitConfigRequestFieldsCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'config:request_fields  
                            {--module_type= : 模块类型 【1 会员个人信息】}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '初始化验证字段';

    public function handle()
    {
        $moduleType = (int)$this->option("module_type");
        if (!isset(ConfigRequestFieldsService::MODULE_TYPE_MAP[$moduleType])) {
            throw new ResourceException("模块类型不存在！");
        }

        (new ConfigRequestFieldsService())->commandInitByModuleType($moduleType);
        return true;
    }
}
