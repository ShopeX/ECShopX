<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
