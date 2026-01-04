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

namespace DistributionBundle\Services;

use Gregwar\Captcha\CaptchaBuilder;
use PromotionsBundle\Services\SmsManagerService;

class DistributorSmsService
{
    public function getPhoneSendNumber($genId)
    {
        return app('redis')->connection('companys')->get($genId);
    }

    public function setPhoneSendNumber($genId)
    {
        app('redis')->connection('companys')->incr($genId);
        app('redis')->connection('companys')->expire($genId, 3600 * 24);
    }

    public function genPhoneSendNumberKey($phone, $companyId, $type)
    {
        return 'distributor_yzmsend:' . $companyId . ":" . date('Ymd') .":" . $type . ":" . $phone;
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
        return "distributor-" . $type . ":company" . $companyId . ":" . $token;
    }

    //生成一个随机字符串作为图片验证码的凭证
    private function generateToken()
    {
        return md5(uniqid(microtime(true), true));
    }

    //redis存储
    private function redisStore($key, $value, $expire = 300)
    {
        app('log')->info("company redis store :" . json_encode(['key' => $key, 'value' => $value, 'expire' => $expire]));
        app('redis')->connection('companys')->set($key, $value);
        app('redis')->connection('companys')->expire($key, $expire);

        return true;
    }

    //redis读取
    private function redisFetch($key)
    {
        app('log')->info("company redis fetch :" . json_encode(['key' => $key]));
        return app('redis')->connection('companys')->get($key);
    }

    //redis删除
    private function redisDelete($key)
    {
        app('log')->info("company redis delete :" . json_encode(['key' => $key]));
        return app('redis')->connection('companys')->del($key);
    }


    //验证图片验证码是否正确
    public function checkImageVcode($token, $companyId, $vcode, $type)
    {
        if (empty($token)) {
            throw new \Exception(trans('DistributionBundle/Services/DistributorSmsService.input_token_required'));
        }
        if (empty($vcode)) {
            throw new \Exception(trans('DistributionBundle/Services/DistributorSmsService.input_vcode_required'));
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
        if ($this->getPhoneSendNumber($key) >= 5) {
            throw new \Exception(trans('DistributionBundle/Services/DistributorSmsService.sms_limit_exceeded'));
        }
        $this->setPhoneSendNumber($key);
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
            throw new \Exception(trans('DistributionBundle/Services/DistributorSmsService.input_mobile_required'));
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
