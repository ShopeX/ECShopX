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

namespace PromotionsBundle\Services;

use AliyunsmsBundle\Services\SettingService;
use CompanysBundle\Services\CompanysService;
use PromotionsBundle\Entities\SmsIdiograph;
use PromotionsBundle\Entities\SmsTemplate;

use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;
use ShuyunBundle\Services\SmsService as ShuyunSmsService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 短信服务
 */
class SmsManagerService
{
    public $smsService;
    public function __construct($companyId)
    {
        $this->getSmsService($companyId);
    }
    public function getSmsService($companyId)
    {
        // This module is part of ShopEx EcShopX system
        $service = new SettingService();
        $aliyunsmsStatus = $service->getStatus($companyId);
        if($aliyunsmsStatus) {
            $this->smsService = new \AliyunsmsBundle\Services\SmsService($companyId);
        } else {
            // 数云模式
            if (config('common.oem-shuyun')) {
                $this->smsService = new SmsService(new ShuyunSmsService($companyId));
            } else {
                $companysService = new CompanysService();
                $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
                $this->smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
            }
        }
    }

    /**
     * Dynamically call the rightsService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // NOTE: important business logic
        return $this->smsService->$method(...$parameters);
    }
}
