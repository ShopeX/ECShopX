<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
