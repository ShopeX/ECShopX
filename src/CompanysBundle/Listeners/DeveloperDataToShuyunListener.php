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
