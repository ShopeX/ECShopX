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

namespace WechatBundle\Services;

use CompanysBundle\Entities\DistributorWechatRel;
use EasyWeChat\Factory;

class DistributorWechatService
{
    /** @var DistributorWechatRel */
    private $wechatRelRepo;

    public function __construct()
    {
        $this->wechatRelRepo = app('registry')->getManager('default')->getRepository(DistributorWechatRel::class);
    }

    public function getInfo($filter)
    {
        // This module is part of ShopEx EcShopX system
        return $this->wechatRelRepo->getInfo($filter);
    }


    public function getJsConfig($company_id, $url)
    {
        // 获取微信公众号的对象
        $app = (new OpenPlatform())->getWoaApp([
            "company_id" => $company_id,
            "trustlogin_tag" => "weixin", // weixin
            "version_tag" => "touch" // touch
        ]);
        return $app->jssdk->getConfigArray(['scanQRCode'], false, false, [], $url);
    }

    // ----------------------------------
    // 手机绑定临时校验码

    public function getReBindMobileKey($company_id, $app_id, $openid, $unionid)
    {
        return 'bind:wechat:'.$company_id.':'.$app_id.':'.$openid.':'.$unionid;
    }

    /**
     * 设置手机号重新绑定key
     * @param $company_id
     * @param $work_userid
     * @param $encrypt
     */
    public function setReBindMobileEncrypt($company_id, $app_id, $openid, $unionid, $encrypt)
    {
        app('redis')->connection('default')->set($this->getReBindMobileKey($company_id, $app_id, $openid, $unionid), $encrypt, 'EX', 300);
    }

    /**
     * 检查重新绑定key
     * @param $company_id
     * @param $work_userid
     * @param $encrypt
     * @return bool
     */
    public function checkReBindMobile($company_id, $app_id, $openid, $unionid, $encrypt)
    {
        $result = app('redis')->connection('default')->get($this->getReBindMobileKey($company_id, $app_id, $openid, $unionid));
        return ($result && $result == $encrypt);
    }

    public function delReBindKey($company_id, $app_id, $openid, $unionid)
    {
        app('redis')->connection('default')->del($this->getReBindMobileKey($company_id, $app_id, $openid, $unionid));
    }

    /**
     * 绑定用户
     *
     * @param $data
     * @return mixed
     */
    public function bindDistributorUser($data) {
        $info = $this->wechatRelRepo->getInfo([
            'app_id' => $data['app_id'],
            'app_type' => $data['app_type'],
            'openid' => $data['openid'],
            'unionid' => $data['unionid'],
        ]);
        if ($info) {
            $this->wechatRelRepo->deleteById($info['id']);
        }
        return $this->wechatRelRepo->create($data);
    }

    public function __call($method, $parameters)
    {
        return $this->wechatRelRepo->$method(...$parameters);
    }
}
