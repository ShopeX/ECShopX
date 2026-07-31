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

use Dingo\Api\Exception\ResourceException;
use Overtrue\Socialite\SocialiteManager;
use WechatBundle\Services\OfficialAccountService;
use WechatBundle\Services\OpenPlatform;

class TrustLoginService
{
    public $key = 'trustlogin_config_';

    /**
     * 获取信任登录列表
     *
     * @param $company_id     公司id
     *
     * @return array
     */
    public function getTrustLoginList($companyId)
    {
        // ShopEx EcShopX Core Module
        $keyStr = $this->key. $companyId;
        $redis = app('redis')->connection('default');
        $result = $redis->get($keyStr);
        $defaults = config('trustlogin');

        if (empty($result)) {
            $result = $defaults;
            $redis->set($keyStr, json_encode($result));
        } else {
            $result = json_decode($result, 1);
            $result = $this->mergeTrustLoginConfig($result, $defaults);
            $redis->set($keyStr, json_encode($result));
        }

        return $result;
    }

    /**
     * 获取信任登录配置信息，前后端分离情况下，将第三方需要的信息传给前端
     *
     * @param $company_id     公司id
     * @param $trustlogin_tag 信任登录标签
     * @param $data 登录授权信息
     *
     * @return array
     */
    public function trustLoginParams($companyId, $trustlogin_tag, $version_tag = 'standard', $data)
    {
        $result = [];
        if ($version_tag == 'standard') {
            switch ($trustlogin_tag) {
                case 'weixin':
                    $configInfo = $this->getConfigRow($trustlogin_tag, $version_tag, $companyId);
                    $config = [
                        'wechat' => [
                            'client_id' => $configInfo['app_id'],
                            'client_secret' => $configInfo['secret'],
                            'redirect' => $data['redirect_url'],
                        ],
                    ];
                    $socialite = new SocialiteManager($config);

                    $redirect_url = $socialite->create('wechat')->redirect($data['redirect_url'] ?? null);
                    $result['config_info'] = $configInfo;
                    $result['redirect_url'] = $redirect_url;
                    break;

                default:
                    $result = [];
                    break;
            }
        } elseif ($version_tag == 'touch') {
            switch ($trustlogin_tag) {
                case 'weixin':
                    $result['oauth_url'] = '';
                    $h5_host = (new SocialTrustLoginService())->resolveH5Host($data);
                    $path = $data['redirect_url'] ? ('?redi_url='. $data['redirect_url']) : '';

                    $url = sprintf("%s/subpages/auth/auth-loading%s", $h5_host, $path);

                    // 获取微信公众号的对象
                    $app = (new OpenPlatform())->getWoaApp([
                        "company_id" => $companyId,
                        "trustlogin_tag" => $trustlogin_tag, // weixin
                        "version_tag" => $version_tag // touch
                    ]);
                    if ($oauthUrl = (new OfficialAccountService($app))->getAuthorizationUrl($url)) {
                        $result['oauth_url'] = $oauthUrl;
                    }
                    break;
                default:
                    $socialService = new SocialTrustLoginService();
                    if ($socialService->isSocialProvider($trustlogin_tag)) {
                        $configInfo = $this->getConfigRow($trustlogin_tag, $version_tag, $companyId);
                        if (empty($configInfo) || !$this->isEnabled($configInfo['status'] ?? false)) {
                            throw new ResourceException('该登录方式未开启');
                        }
                        $h5_host = $socialService->resolveH5Host($data);
                        if ($h5_host === '') {
                            throw new ResourceException('缺少 H5 域名配置');
                        }
                        $redirectUri = $socialService->buildRedirectUri($h5_host, $trustlogin_tag);
                        $result['oauth_url'] = $socialService->getAuthorizeUrl($trustlogin_tag, $configInfo, $redirectUri, $h5_host);
                    } else {
                        $result = [];
                    }
                    break;
            }
        }
        return $result;
    }

    /**
     * 保存配置
     *
     * @param $company_id     公司id
     *
     * @return array
     */
    public function saveStatusSetting($data, $companyId)
    {
        $keyStr = $this->key. $companyId;
        $redis = app('redis')->connection('default');
        $result = $redis->get($keyStr);
        if (empty($result)) {
            $result = $this->getTrustLoginList($companyId);
            $result = json_encode($result);
        }
        $result = json_decode($result, 1);
        if (!isset($result[$data['loginversion']])) {
            return false;
        }
        $editVersion = $result[$data['loginversion']];

        $rowResult = collect($editVersion)->firstWhere('type', $data['type']);
        if (empty($rowResult)) {
            return false;
        }
        foreach ($rowResult as $key => &$value) {
            if ($key === 'extra_config') {
                continue;
            }
            if (isset($data[$key])) {
                $value = $data[$key];
            }
        }
        if (array_key_exists('extra_config', $data)) {
            $rowResult['extra_config'] = $this->mergeExtraConfigOnSave(
                (string) ($rowResult['extra_config'] ?? ''),
                $this->normalizeExtraConfig($data['extra_config'])
            );
            if (($data['type'] ?? '') === 'apple') {
                $this->assertAppleExtraConfigValid($rowResult['extra_config']);
            }
        } elseif (!isset($rowResult['extra_config'])) {
            $rowResult['extra_config'] = '';
        }
        foreach ($editVersion as $k => &$config) {
            if ($config['type'] == $data['type']) {
                $config = $rowResult;
            }
        }
        $result[$data['loginversion']] = $editVersion;
        $redis->set($keyStr, json_encode($result));

        return true;
    }

    public function getConfigRow($type, $version = 'standard', $companyId)
    {
        $list = $this->getTrustLoginList($companyId);
        if (!isset($list[$version])) {
            return [];
        }
        $editVersion = $list[$version];

        return collect($editVersion)->firstWhere('type', $type) ?: [];
    }

    private function mergeTrustLoginConfig(array $stored, array $defaults): array
    {
        foreach (['standard', 'touch'] as $version) {
            if (!isset($defaults[$version])) {
                continue;
            }
            $stored[$version] = $stored[$version] ?? [];
            $storedTypes = array_column($stored[$version], 'type');
            foreach ($defaults[$version] as $defaultRow) {
                if (!in_array($defaultRow['type'], $storedTypes, true)) {
                    $stored[$version][] = $defaultRow;
                }
            }
            foreach ($stored[$version] as &$row) {
                if (!isset($row['extra_config'])) {
                    $row['extra_config'] = '';
                }
            }
            unset($row);
        }

        return $stored;
    }

    private function normalizeExtraConfig($extraConfig): string
    {
        if ($extraConfig === null || $extraConfig === '') {
            return '';
        }
        if (is_array($extraConfig)) {
            return json_encode($extraConfig, JSON_UNESCAPED_UNICODE);
        }

        return trim((string)$extraConfig);
    }

    private function mergeExtraConfigOnSave(string $existing, string $incoming): string
    {
        $existingExtra = json_decode($existing, true);
        $incomingExtra = json_decode($incoming, true);
        if (!is_array($incomingExtra)) {
            return $existing !== '' ? $existing : $incoming;
        }
        if (!is_array($existingExtra)) {
            $existingExtra = [];
        }
        foreach (['private_key', 'team_id', 'key_id'] as $field) {
            if (!array_key_exists($field, $incomingExtra)) {
                continue;
            }
            if ($incomingExtra[$field] === '***' && !empty($existingExtra[$field])) {
                $incomingExtra[$field] = $existingExtra[$field];
            }
        }

        return json_encode($incomingExtra, JSON_UNESCAPED_UNICODE);
    }

    private function assertAppleExtraConfigValid(string $extraConfig): void
    {
        $extra = json_decode($extraConfig, true);
        if (!is_array($extra)) {
            throw new ResourceException('Apple extra_config 格式错误');
        }
        $privateKey = trim((string) ($extra['private_key'] ?? ''));
        if ($privateKey === '' || $privateKey === '***') {
            throw new ResourceException('Apple 保存失败：请填写完整 private_key（.p8 PEM）');
        }
        if (trim((string) ($extra['team_id'] ?? '')) === '' || trim((string) ($extra['key_id'] ?? '')) === '') {
            throw new ResourceException('Apple 保存失败：extra_config 需包含 team_id 与 key_id');
        }
    }

    private function isEnabled($status): bool
    {
        return $status === true || $status === 'true' || $status === 1 || $status === '1';
    }
}
