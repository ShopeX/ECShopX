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

namespace CompanysBundle\Services;

use Dingo\Api\Exception\ResourceException;
use GuzzleHttp\Client;
use PromotionsBundle\Services\SmsManagerService;

/**
 * 系统短信验证码
 * Class OperatorSmsService
 * @package CompanysBundle\Services
 */
class OperatorSmsService
{
    private function getVerifyKey($mobile, $type): string
    {
        return "operator-sms:verify:$type:$mobile";
    }

    /**
     * 发送短信验证码
     * @param $mobile
     * @param $type
     * @return false|string
     */
    public function sendVerifyCode($companyId, $mobile, $type)
    {
        if (!in_array($type, ['login'])) {
            throw new ResourceException('错误的短信验证类型');
        }

        $multiLockKey = "operator-sms:lock:$type:$mobile";
        $sendNumKey = "operator-sms:today-num:$type:$mobile";
        $maxNum = 5;

        // 限制一天发送数量, 因为后面有分布式锁, 这里简单判断下就好
        $sendNum = app('redis')->connection('companys')->get($sendNumKey);
        if (!empty($sendNum) && $sendNum >= $maxNum) {
            throw new ResourceException('验证码发送过于频繁');
        }

        // 60秒内只能发送一次
        if (!app('redis')->connection('companys')->set($multiLockKey, 1, 'NX', 'EX', 60)) {
            $second = app('redis')->connection('companys')->ttl($multiLockKey);
            throw new ResourceException('请' . max(1, intval($second)). '秒后重试发送验证码');
        }

        // 生成短信码
        $verifyCode = (string)rand(100000, 999999);
        $params = [
            'mobile' => $mobile,
            'verifyCode' => $verifyCode,
        ];

        if (config('sms.entId') && config('sms.entPwd')) {
            $success = $this->sendMobileCode($params);
        } else {
            $data = ['code' => $verifyCode];
            $smsManagerService = new SmsManagerService($companyId);
            $success = $smsManagerService->send($mobile, $companyId, 'verification_code', $data);
        }

        if ($success) {
            //发送成功，记录今日发送次数
            if (!empty($sendNum)) {
                app('redis')->connection('companys')->incr($sendNumKey);
            } else {
                $ttl = strtotime(date('Y-m-d 23:59:59', time())) - time();
                app('redis')->connection('companys')->set($sendNumKey, 1, 'EX', $ttl);
            }
            // 存储短信验证码, 有效期3分钟
            app('redis')->connection('companys')->set($this->getVerifyKey($mobile, $type), $verifyCode, 'EX', 180);
            return $verifyCode;
        }
        // 这里严谨点的话发送失败应该要清除前面的锁
        return false;
    }

    /**
     * 校验短信验证码
     * @param $mobile
     * @param $type
     * @param $code
     * @return bool
     */
    public function checkVerifyCode($mobile, $type, $code): bool
    {
        $vc = app('redis')->connection('companys')->get($this->getVerifyKey($mobile, $type));
        if (!empty($vc) && $vc == $code) {
            return true;
        }
        return false;
    }

    /**
     * 登录成功等事件之后可以让code过期
     * @param $mobile
     * @param $type
     */
    public function expireVerifyCode($mobile, $type)
    {
        app('redis')->connection('companys')->del($this->getVerifyKey($mobile, $type));
    }

    // 下面代码来源导购后台，需要到env中配置sms短信发送服务

    public function sendMobileCode(array $send_params)
    {
        /*        app('log')->debug('send:sms: --- '.$send_params['verifyCode']);
                return true;*/

        $smsconfig = config('sms');
        $send_str['certi_app'] = 'sms.send';
        $send_str['entId'] = $smsconfig['entId'];
        $send_str['entPwd'] = $smsconfig['entPwd'];
        $send_str['license'] = $smsconfig['license'];
        $send_str['source'] = $smsconfig['source'];
        $send_str['sendType'] = 'notice';
        $send_str['version'] = '1.0';
        $send_str['format'] = 'json';
        $send_str['timestamp'] = self::getTime();
        $send_str['contents'] = self::getMobileCode($send_params);
        $send_str['certi_ac'] = self::get_sign($send_str, $smsconfig['secret']);
        $result = self::run($smsconfig['apiUrl'], $send_str);

        if (!$result || ($result['res'] != 'succ')) {
            return false;
        }
        return true;
    }

    private static function run(string $url, array $params)
    {
        $client = new Client();
        $res = $client->request('POST', $url, [
            'form_params' => $params,
        ]);

        if ($res->getStatusCode() !== 200) {
            return false;
        }

        return \GuzzleHttp\json_decode($res->getBody()->getContents(), true);
    }

    private static function getMobileCode(array $send_params)
    {
        $content = [
            [
                'phones' => $send_params['mobile'],
                'content' => self::getSmsContent($send_params['verifyCode']).'【商派】',
            ],
        ];
        return json_encode($content);
    }

    private static function getSmsContent($verifyCode)
    {
        return '尊敬的用户，您好，您的手机验证码是：' . $verifyCode . '。';
    }

    private static function getTime()
    {
        $url = 'http://webapi.sms.shopex.cn';
        $token = 'SMS_TIME';
        $substr['certi_app'] = 'sms.servertime';
        $substr['version'] = '1.0';
        $substr['format'] = 'json';
        $substr['certi_ac'] = self::get_sign($substr, $token);
        $result = self::run($url, $substr);
        return $result['info'];
    }

    private static function get_sign(array $params, string $token)
    {
        return strtolower(md5(self::assemble($params) . strtolower(md5($token))));
    }

    private static function assemble(array $params)
    {
        if (!is_array($params)) {
            return null;
        }
        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $val) {
            if ($key != 'certi_ac') {
                $sign .= (is_array($val) ? self::assemble($val) : $val);
            }
        }
        return $sign;
    }
}
