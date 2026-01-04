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

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;
use CompanysBundle\Events\CompanyCreateEvent;
use GuzzleHttp\Client as Client;

class OnlineOpenSendSmsListener extends BaseListeners implements
    ShouldQueue
// class OnlineOpenSendSmsListener extends BaseListeners
{
    /**
     * Handle the event.
     *
     * @param  CompanyCreateEvent $event
     * @return void
     */
    public function handle(CompanyCreateEvent $event)
    {
        // 如果为数云模式，则不执行
        if (config('common.oem-shuyun')) {
            return false;
        }
        // if (!config('common.system_is_saas') || !config('common.system_open_online')) {
        if (!config('common.system_is_saas')) {
            return false;
        }

        $mobile = $event->entities['mobile'] ?? '';
        if (!$mobile) {
            return false;
        }
        $activeAt = date('Y-m-d', $event->entities['active_at']);
        $expiredAt = date('Y-m-d', $event->entities['expired_at']);
        $shopAdminUrl = config('common.shop_admin_url');

        $sms = "尊敬的用户，您已于{$activeAt}成功开通商派云店系统，有效期至{$expiredAt}。请通过：{$shopAdminUrl}进入管理端。账户：{$mobile}，密码：注册所用密码。";

        #global $smsconfig;
        $smsconfig = [
            'apiUrl' => 'http://api.sms.shopex.cn',
            'entId' => '10023',
            'entPwd' => 'efca9b3f71133525fbbeec60284eddd9',
            'license' => '111',
            'source' => '603622',
            'secret' => '70b3f25f3b334b1fbe6904c565d9f979',
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
        $send_str['contents'] = $this->getContent($mobile, $sms . '【商派】');
        $send_str['certi_ac'] = $this->getSign($send_str, $smsconfig['secret']);
        $this->run($smsconfig['apiUrl'], $send_str);
    }

    private function run($url, $params)
    {
        // fe10e2f6 module
        $client = new Client();
        $res = $client->post($url, ['verify' => false, 'form_params' => $params])->getBody();
        $content = $res->getContents();
        return json_decode($content, true);
    }

    private function getContent($phone, $sms)
    {
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
        $assemble = $this->assemble($params);
        $md5token = strtolower(md5($token));
        $str = strtolower(md5($assemble . $md5token));
        return $str;
    }

    private function assemble($params)
    {
        // fe10e2f6 module
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
}
