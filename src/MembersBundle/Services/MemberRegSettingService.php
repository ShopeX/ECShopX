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

namespace MembersBundle\Services;

use CompanysBundle\Services\Shops\ProtocolService;
use EspierBundle\Services\Config\ConfigRequestFieldsService;
use Gregwar\Captcha\CaptchaBuilder;
use PromotionsBundle\Services\SmsManagerService;

class MemberRegSettingService
{
    //设置注册项
    public function setRegItem($companyId, $params)
    {
        // U2hvcEV4 framework
        $result['registerSettingStatus'] = (isset($params['registerSettingStatus']) && $params['registerSettingStatus'] == 'true') ? true : false;
        $result['setting'] = $params['setting'] ?? [];
        foreach ($result['setting'] as &$v) {
            $v['is_open'] = isset($v['is_open']) && 'false' == $v['is_open'] ? false : true;
            $v['is_required'] = isset($v['is_required']) && 'false' == $v['is_required'] ? false : true;
            if (isset($v['items']) && is_array($v['items'])) {
                foreach ($v['items'] as $itemskey => $itemsvalue) {
                    if (isset($itemsvalue['ischecked'])) {
                        $v['items'][$itemskey]['ischecked'] = 'false' == $itemsvalue['ischecked'] ? false : true;
                    }
                }
            }
        }
        $genId = $this->genReidsId($companyId);
        return app('redis')->connection('members')->set($genId, json_encode($result));
    }

    //注册项获取
    public function getRegItem($companyId)
    {
        $result = (new ConfigRequestFieldsService())->getListAndHandleSettingFormat($companyId, ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO);
        $data = [
            "setting" => [],
            "registerSettingStatus" => true,
        ];
        foreach ($result as $keyName => $item) {
            $data["setting"][$keyName] = [
                "element_type" => (string)($item["element_type"] ?? ""),
                "is_open" => (bool)($item["is_open"] ?? false),
                "is_required" => (bool)($item["is_required"] ?? false),
                "name" => (string)($item["name"] ?? ""),
            ];
            switch ($data["setting"][$keyName]["element_type"]) {
                case "checkbox":
                    $data["setting"][$keyName]["items"] = (array)($item["checkbox"] ?? []);
                    break;
                case "select":
                    $data["setting"][$keyName]["items"] = (array)($item["select"] ?? []);
                    break;
            }
        }
        return $data;
    }

    public function setRegAgreement($companyId, $content)
    {
        $genId = $this->genAgreementRedisId($companyId);
        return app('redis')->connection('members')->set($genId, $content);
    }

    public function getRegAgreement($companyId)
    {
        // U2hvcEV4 framework
        $protocolData = (new ProtocolService($companyId))->get([ProtocolService::TYPE_MEMBER_REGISTER]);
        return (string)($protocolData[ProtocolService::TYPE_MEMBER_REGISTER]["content"] ?? "");
        // 原逻辑
        $genId = $this->genAgreementRedisId($companyId);
        $contentAgreement = app('redis')->connection('members')->get($genId);
        return $contentAgreement ?: '';
    }

    public function getPhoneSendNumber($genId)
    {
        return app('redis')->connection('members')->get($genId);
    }

    /**
     * 当前次数
     * @param string $genId 缓存key
     * @return int
     */
    public function setPhoneSendNumber($genId): int
    {
        $redisHandle = app('redis')->connection('members');
        // 次数自增
        $incr = $redisHandle->incr($genId);
        // 没有过期时间，就设置过期时间
        if ($redisHandle->ttl($genId) == -1) {
            $redisHandle->expire($genId, 3600 * 24);
        }
        return (int)$incr;
    }

    public function genPhoneSendNumberKey($phone, $companyId, $type)
    {
        return 'yzmsend:' . $companyId . ":" . date('Ymd') .":" . $type . ":" . $phone;
    }

    /**
     * 获取redis存储的ID
     */
    public function genReidsId($companyId)
    {
        return 'memberRegSetting:' . sha1($companyId);
    }

    /**
     * 获取redis存储的ID
     */
    public function genAgreementRedisId($companyId)
    {
        return 'memberRegAgreementSetting:' . sha1($companyId);
    }

    //生成图片验证码
    public function generateImageVcode($companyId, $type = 'register')
    {
        $builder = new CaptchaBuilder(4);
        $builder->build();
        $vcode = $builder->getPhrase();
        $data = $builder->get();
        $data = "data:image/png;base64," . base64_encode($data);
        $token = $this->saveImageVcode($vcode, $companyId, $type);
        return [$token, $data];
    }

    //把图片验证码保存到redis里
    private function saveImageVcode($vcode, $companyId, $type)
    {
        $token = $this->generateToken();
        $key = $this->generateReidsKey($token, $companyId, $type);
        $this->redisStore($key, $vcode);
        return $token;
    }

    //读取redis里的图片验证码
    private function loadImageVcode($token, $companyId, $type)
    {
        $key = $this->generateReidsKey($token, $companyId, $type);
        return $this->redisFetch($key);
    }

    //生成验证码的redis key
    private function generateReidsKey($token, $companyId, $type)
    {
        return "member-" . $type . ":company" . $companyId . ":" . $token;
    }

    //生成一个随机字符串作为图片验证码的凭证
    private function generateToken()
    {
        return md5(uniqid(microtime(true), true));
    }

    //redis存储
    private function redisStore($key, $value, $expire = 300)
    {
        app('log')->info("member redis store :" . json_encode(['key' => $key, 'value' => $value, 'expire' => $expire]));
        app('redis')->connection('members')->set($key, $value);
        app('redis')->connection('members')->expire($key, $expire);

        return true;
    }

    //redis读取
    private function redisFetch($key)
    {
        app('log')->info("member redis fetch :" . json_encode(['key' => $key]));
        return app('redis')->connection('members')->get($key);
    }

    //redis删除
    private function redisDelete($key)
    {
        app('log')->info("member redis delete :" . json_encode(['key' => $key]));
        return app('redis')->connection('members')->del($key);
    }


    //验证图片验证码是否正确
    public function checkImageVcode($token, $companyId, $vcode, $type)
    {
        if (empty($token)) {
            throw new \Exception(trans('MembersBundle/Members.image_captcha_token_required'));
        }
        if (empty($vcode)) {
            throw new \Exception(trans('MembersBundle/Members.image_captcha_required'));
        }
        $storeVcode = $this->loadImageVcode($token, $companyId, $type);
        if (strtoupper($storeVcode) == strtoupper($vcode)) {
            $key = $this->generateReidsKey($token, $companyId, $type);
            $this->redisDelete($key);
            return true;
        }
        return false;
    }

    //生成短信验证码
    public function generateSmsVcode($phone, $companyId, $type)
    {
        // todo 验证码发送限制
        $key = $this->genPhoneSendNumberKey($phone, $companyId, $type);
//        if ($this->getPhoneSendNumber($key) >= 5) {
//            throw new \Exception(trans('MembersBundle/Members.captcha_sent_too_many'));
//        }
//        $this->setPhoneSendNumber($key);
        if ($this->setPhoneSendNumber($key) > config("common.sms_send_limit")) {
            throw new \Exception(trans('MembersBundle/Members.captcha_sent_too_many'));
        }
        $vcode = (string)rand(100000, 999999);
        app('log')->info("code :" . json_encode(['phone' => $phone, 'company' => $companyId, 'vcode' => $vcode]));
        //保存验证码
        $this->saveSmsVcode($phone, $companyId, $vcode, $type);
        //发送短信
        $this->sendSmsVcode($companyId, $phone, $vcode);
        return true;
    }

    //验证短信验证码
    public function checkSmsVcode($phone, $companyId, $vcode, $type)
    {
        if (empty($phone)) {
            throw new \Exception(trans('MembersBundle/Members.mobile_required_reg'));
        }

        if (empty($vcode)) {
            throw new \Exception(trans('MembersBundle/Members.captcha_required'));
        }

        $storeVcode = $this->loadImageVcode($phone, $companyId, $type);
        if ($storeVcode == $vcode) {
            $key = $this->generateReidsKey($phone, $companyId, $type);
            $this->redisDelete($key);
            return true;
        }
        return false;
    }

    //保存短信验证码
    private function saveSmsVcode($phone, $companyId, $vcode, $type)
    {
        $key = $this->generateReidsKey($phone, $companyId, $type);
        $this->redisStore($key, $vcode);
        return $phone;
    }

    //读取短信验证码
    public function loadSmsVcode($token, $companyId, $type)
    {
        $key = $this->generateReidsKey($token, $companyId, $type);
        return $this->redisFetch($key);
    }

    //短信验证码的发送动作
    private function sendSmsVcode($companyId, $phone, $code)
    {
        $data = ['code' => $code];
        $smsManagerService = new SmsManagerService($companyId);
        $smsManagerService->send($phone, $companyId, 'verification_code', $data);
        return true;
    }
}
