<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use CompanysBundle\Events\CompanyCreateEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use OpenapiBundle\Services\DeveloperService;
use ShuyunBundle\Services\OperatorsService as ShuyunOperatorsService;

class DeveloperDataToShuyunListener extends BaseListeners implements ShouldQueue
{
    public function handle(CompanyCreateEvent $event)
    {
        // 如果非数云模式，则不进行开发配置同步
        if (!config('common.oem-shuyun')) {
            return false;
        }
        app('log')->error('开发配置同步调数云开始');
        $companyId = $event->entities['company_id'];
        try {
            $developerService = new DeveloperService();
            $detail = $developerService->detail($companyId);
            $shuyunOperatorsService = new ShuyunOperatorsService();
            $shuyunOperatorsService->developerDataToShuyun($detail);
            app('log')->error('开发配置同步调数云完成');
        } catch (\Throwable $throwable) {
            app('log')->error('开发配置同步调数云失败');
            $error = [
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'msg' => $throwable->getMessage(),
            ];
            app('log')->info('开发配置同步调数云失败 error:'.var_export($error, true));
        }
    }
}
