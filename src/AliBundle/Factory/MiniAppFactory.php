<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AliBundle\Factory;

use AliBundle\Kernel\Application;
use AliBundle\Kernel\Config;
use AliBundle\Services\AliMiniAppSettingService;
use Dingo\Api\Exception\ResourceException;

class MiniAppFactory
{
    public function getApp($company_id): Application
    {
        $config = new Config();
        $settingService = new AliMiniAppSettingService();
        $data = $settingService->getInfoByCompanyId($company_id);
        if (empty($data)) {
            throw new ResourceException('当前账号未配置支付宝小程序');
        }
        $config = $config
            ->setAppId($data['authorizer_appid'])
            ->setMerchantPrivateKey($data['merchant_private_key'])
            ->setApiSignMethod($data['api_sign_method'])
            ->setAlipayCertPath($data['alipay_cert_path'])
            ->setAlipayRootCertPath($data['alipay_root_cert_path'])
            ->setMerchantCertPath($data['merchant_cert_path'])
            ->setAlipayPublicKey($data['alipay_public_key'])
            ->setNotifyUrl($data['notify_url'])
            ->setEncryptKey($data['encrypt_key']);
        // $response = $app->getFactory()->base()->oauth()->getToken('123123')->toMap();
        return new Application($config);
    }

}
