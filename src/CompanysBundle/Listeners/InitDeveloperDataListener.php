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

use CompanysBundle\Entities\Companys;
use EspierBundle\Listeners\BaseListeners;
use CompanysBundle\Events\CompanyCreateEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use OpenapiBundle\Services\DeveloperService;

class InitDeveloperDataListener extends BaseListeners implements ShouldQueue
{
    public function handle(CompanyCreateEvent $event)
    {
        // This module is part of ShopEx EcShopX system
        app('log')->error('开发配置创建开始');
        $companyId = $event->entities['company_id'];
        $companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
        $company = $companysRepository->get(['company_id' => $companyId]);
        try {
            $shopexUid = $company->getPassportUid();
            $eid = $company->getEid();
            $appKey = substr(md5($shopexUid), 8, 16);
            $appSecret = md5($eid . config('common.rand_salt'));
            $data = [
                'app_key' => $appKey,
                'app_secret' => $appSecret,
                'external_base_uri' => config('common.external_baseuri'),
                'external_app_key' => $appKey,
                'external_app_secret' => $appSecret,
            ];
            $developerService = new DeveloperService();
            $developerService->update($companyId, $data);
        } catch (\Throwable $throwable) {
            app('log')->error('开发配置创建失败');
        }
    }
}
