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
