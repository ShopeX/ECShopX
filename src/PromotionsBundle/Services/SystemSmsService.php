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

use CompanysBundle\Services\CompanysService;
use GuzzleHttp\Client as Client;
use LogicException;
use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;

class SystemSmsService
{
    public function sendSms($phone, $sms)
    {
        if (!config('common.system_is_saas')) {
            $companyId = config('common.system_companys_id');
            $smsContent['content'] = $sms;
            $smsContent['params'] = [];
            $smsContent['replaceParams'] = [];

            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
            return $smsService->sendContent($companyId, $phone, $smsContent);
        } elseif (config('common.system_main_companys_id')) {
            $companyId = config('common.system_main_companys_id');
            $smsContent['content'] = $sms;
            $smsContent['params'] = [];
            $smsContent['replaceParams'] = [];

            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
            return $smsService->sendContent($companyId, $phone, $smsContent);
        }
        #global $smsconfig;
        $smsconfig = [
            'apiUrl' => 'http://api.sms.shopex.cn',
            'entId' => config('common.system_sms_entid'),
            'entPwd' => config('common.system_sms_entpwd'),
            'license' => '111',
            'source' => '423524',
            'secret' => '6ef7656d54cc355de37efb704c99413c',
        ];
        $send_str['certi_app'] = 'sms.send';
        $send_str['entId'] = $smsconfig['entId'];
        $send_str['entPwd'] = $smsconfig['entPwd'];
        $send_str['license'] = $smsconfig['license'];
        $send_str['source'] = $smsconfig['source'];
        $send_str['sendType'] = 'notice';
        $send_str['version'] = '1.0';
        $send_str['format'] = 'json';
        $send_str['timestamp'] = time();
        $send_str['contents'] = $this->getContent($phone, $sms . '【ecshopx】');
        $send_str['certi_ac'] = $this->getSign($send_str, $smsconfig['secret']);
        $result = $this->run($smsconfig['apiUrl'], $send_str);
        return $result;
    }

    private function run($url, $params)
    {
        $client = new Client();
        $res = $client->post($url, ['verify' => false, 'form_params' => $params])->getBody();
        $content = $res->getContents();
        return json_decode($content, true);
    }

    private function getContent($phone, $sms)
    {
        // Core: RWNTaG9wWA==
        $content = array(
            array(
                'content' => $sms,
                'phones' => $phone
            )
        );
        $str = json_encode($content);
        return $str;
    }

    private function getSign($params, $token)
    {
        // Core: RWNTaG9wWA==
        $assemble = $this->assemble($params);
        $md5token = strtolower(md5($token));
        $str = strtolower(md5($assemble . $md5token));
        return $str;
    }

    private function assemble($params)
    {
        if (!is_array($params)) {
            return null;
        }
        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $val) {
            if ($key != 'certi_ac') {
                $sign .= (is_array($val) ? $this->assemble($val) : $val);
            }
        }
        return $sign;
    }

    public function fireWall($phone, $content, $ip = "")
    {
        $this->checkTimesByDay($phone, 5);
        return true;
    }

    private function checkTimesByDay($phone, $maxTimes = 5)
    {
        $key = $this->generateReidsKey($phone, "day");
        $times = $this->redisIncr($key);
        if ($times > $maxTimes) {
            throw new LogicException(trans("PromotionsBundle.daily_sms_limit_reached", ["max_times" => $maxTimes]));
        }
        return true;
    }

    private function generateReidsKey($phone, $type = "day")
    {
        return "send-sms-$type:" . $phone;
    }

    private function redisIncr($key)
    {
        $expire = (intval(time() / (24 * 3600)) + 1) * (24 * 3600);
        $value = app('redis')->connection('companys')->incr($key);
        app('redis')->connection('companys')->expireat($key, $expire);
        return $value;
    }
}
