<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
