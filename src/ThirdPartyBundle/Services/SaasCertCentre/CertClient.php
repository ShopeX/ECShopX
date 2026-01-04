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

namespace ThirdPartyBundle\Services\SaasCertCentre;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * shopex prism cert
 */
class CertClient
{
    // 获取oauth证书
    private $oauthLicenseAdd = '/auth/license.add';
    private $certiValidateApi = 'api/third/saascert/cert/validate';

    public $companyId;
    public $shopexUid;
    public $prismClient;
    public $oauthToken;

    /**
     * CertClient 构造函数.
     */
    public function __construct($companyId, $shopexUid)
    {
        $this->companyId = $companyId;
        $this->shopexUid = $shopexUid;
    }

    public function connection()
    {
        $this->prismClient = new \PrismClient(
            trim(config('common.prism_url')), //$url
            trim(config('common.prism_key')), //$key
            trim(config('common.prism_secret')) //$secret
        );
        $this->checkToken();

        $this->prismClient->access_token = $this->getAccessToken();

        return $this;
    }

    /**
     * 检测 accessToke 和 refreshToke 是否有效
     */
    private function checkToken()
    {
        $accessToken = $this->getAccessToken();
        $refreshToken = $this->getRefreshToken();
        if (!$accessToken && !$refreshToken) {
            throw new AccessDeniedHttpException('登录有误,请重新登录');
        }

        if ($accessToken && $refreshToken) {
            return true;
        }

        $params['grant_type'] = 'refresh_token';
        $params['refresh_token'] = $refreshToken;

        $result = $this->prismClient->post($this->oauthToken, $params, null, ['connect_timeout' => 3]);
        $result = json_decode($result, true);
        if (isset($result['result']) && $result['result'] == 'error') {
            throw new AccessDeniedHttpException('请重新登录');
        }

        $accessToken = $result['access_token'];
        $expiresIn = $result['expires_in'];
        $refreshToken = $result['refresh_token'];
        $refreshExpires = $result['refresh_expires'];
        $this->setAccessToken($accessToken, $expiresIn);
        $this->setRefreshToken($refreshToken, $refreshExpires);
        return $accessToken;
    }

    /**
     * 获取 accessToke 记录
     */
    public function getAccessToken()
    {
        $genId = $this->accessTokenGenId();
        $accessToken = app('redis')->connection('prism')->get($genId);
        return $accessToken;
    }

    /**
     * 获取 freshToken 记录
     */
    public function getRefreshToken()
    {
        $genId = $this->refreshTokenGenId();
        $refreshToken = app('redis')->connection('prism')->get($genId);
        return $refreshToken;
    }

    /**
     * 记录accessToken
     *
     * @param accessToken
     * @param expiresIn  accessToken 过期时间
     */
    public function setAccessToken($accessToken, $expiresIn)
    {
        $genId = $this->accessTokenGenId();
        $result = app('redis')->connection('prism')->set($genId, $accessToken);
        if ($result && $expiresIn) {
            app('redis')->connection('prism')->expireat($genId, $expiresIn);
        }
        return $result;
    }

    /**
     * 记录refreshToken
     *
     * @param refreshToken
     * @param refresh_expires refresh 过期时间
     */
    public function setRefreshToken($refreshToken, $refreshExpires)
    {
        $genId = $this->refreshTokenGenId();
        $result = app('redis')->connection('prism')->set($genId, $refreshToken);
        if ($result && $refreshExpires) {
            app('redis')->connection('prism')->expireat($genId, $refreshExpires);
        }
        return $result;
    }

    private function accessTokenGenId()
    {
        return 'prism:'. sha1($this->shopexUid.'_'.$this->companyId.'_AccessToken');
    }

    private function refreshTokenGenId()
    {
        return 'prism:'. sha1($this->shopexUid.'_'.$this->companyId.'_RefreshToken');
    }

    //获取证书和节点（通过oauth token）
    public function getCer()
    {
        $base_url = config('common.certi_base_url');
        $params['certi_url'] = rtrim($base_url, '/') . '/' . $this->companyId . '/';
        $params['certi_session'] = config('common.store_key');
        $params['certi_validate_url'] = rtrim($base_url, '/') . '/' . $this->certiValidateApi;
        $params['shop_version'] = '1.0';

        $result = $this->prismClient->post($this->oauthLicenseAdd, $params, null, ['connect_timeout' => 3]);
        app('log')->info("saascert params=>".json_encode($params));
        app('log')->info("saascert result=>".$result);
        return json_decode($result, 1);
    }
}
