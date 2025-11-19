<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Services\MyCoach;

class H5Service
{
    public $mobile_column = 'userData';


    /**
    * 加密手机号链接字符串，用于跳转到稻田的H5页面
    * @param string $mobile:手机号
    * @param array $urlSetting:链接
    */
    public function getEncryptionMobileUrl($mobile, $urlSetting)
    {
        if (!$mobile) {
            return $urlSetting;
        }
        $need_encryption = [
            'arranged',
            'classhour',
            'mycoach',
        ];
        $encryption_mobile = urlencode(base64_encode($mobile));
        $str = '&'.$this->mobile_column.'='.$encryption_mobile;
        foreach ($urlSetting as $key => $url) {
            if (in_array($key, $need_encryption)) {
                $urlSetting[$key] = $url.$str;
            }
        }
        return $urlSetting;
    }
}
