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

class SocialTrustLoginService
{
    public const SOCIAL_TYPES = ['apple', 'google', 'facebook', 'line'];

    public function isSocialProvider(string $trustloginTag): bool
    {
        return in_array($trustloginTag, self::SOCIAL_TYPES, true);
    }

    public function buildRedirectUri(string $h5Host, string $trustloginTag, string $rediUrl = ''): string
    {
        // Apple 使用 response_mode=form_post，H5 静态站无法接收 POST，改走 API 回调再 302 回 H5
        if ($trustloginTag === 'apple') {
            return $this->buildAppleApiCallbackUri();
        }

        $base = rtrim($h5Host, '/');
        // OAuth 平台要求 redirect_uri 固定可登记；redi_url 改由前端 sessionStorage 带回
        return sprintf('%s/subpages/auth/auth-social-loading?trustlogin_tag=%s', $base, urlencode($trustloginTag));
    }

    public function buildAppleApiCallbackUri(): string
    {
        $apiBase = rtrim((string) config('common.api_base_url'), '/');
        if ($apiBase === '') {
            $apiBase = rtrim(trim((string) env('APP_URL', '')), '/') . '/api';
        }

        return $apiBase . '/h5app/wxapp/trustlogin/apple/callback';
    }

    public function buildAppleH5LandingUrl(string $h5Host, string $code = '', array $extra = []): string
    {
        $h5Host = rtrim(trim($h5Host), '/');
        if ($h5Host === '' || !preg_match('#^https?://#i', $h5Host)) {
            throw new ResourceException('缺少 H5 域名配置');
        }

        $base = $h5Host . '/subpages/auth/auth-social-loading';
        $query = array_merge(['trustlogin_tag' => 'apple'], $extra);
        if ($code !== '') {
            $query['code'] = $code;
        }

        return $base . '?' . http_build_query($query);
    }

    public function resolveH5Host(array $data): string
    {
        $fromRequest = trim((string)($data['h5_host'] ?? $data['origin'] ?? ''));
        if ($fromRequest !== '') {
            return rtrim($fromRequest, '/');
        }

        return rtrim(trim((string)config('common.h5_base_url')), '/');
    }

    public function buildSocialiteConfig(string $trustloginTag, array $configRow, string $redirectUri): array
    {
        $provider = strtolower($trustloginTag);
        $providerConfig = [
            'client_id' => $configRow['app_id'] ?? '',
            'client_secret' => $configRow['secret'] ?? '',
            'redirect_uri' => $redirectUri,
        ];

        if ($provider === 'apple') {
            $extra = $this->parseExtraConfig($configRow['extra_config'] ?? '');
            if (!empty($extra['team_id'])) {
                $providerConfig['team_id'] = $extra['team_id'];
            }
            if (!empty($extra['key_id'])) {
                $providerConfig['key_id'] = $extra['key_id'];
            }
            $privateKey = $this->normalizeApplePrivateKey((string) ($extra['private_key'] ?? ''));
            if ($privateKey === '' || $privateKey === '***') {
                throw new ResourceException('Apple 私钥未配置：请在后台信任登录中填写 extra_config.private_key（完整 .p8 PEM）后保存');
            }
            if (empty($extra['team_id']) || empty($extra['key_id'])) {
                throw new ResourceException('Apple 配置不完整：extra_config 需包含 team_id、key_id、private_key');
            }
            $providerConfig['private_key'] = $privateKey;
            // Apple 使用 JWT 作为 client_secret，忽略 secret 字段
            unset($providerConfig['client_secret']);
        }

        return [$provider => $providerConfig];
    }

    public function getAuthorizeUrl(string $trustloginTag, array $configRow, string $redirectUri, string $h5Host = ''): string
    {
        $socialiteConfig = $this->buildSocialiteConfig($trustloginTag, $configRow, $redirectUri);
        $socialite = new SocialiteManager($socialiteConfig);
        $provider = $socialite->create(strtolower($trustloginTag));
        if ($trustloginTag === 'apple' && $h5Host !== '') {
            $state = $this->encodeAppleOAuthState($h5Host);
            $provider->withState($state);
            $this->rememberAppleOAuthH5Host($state, $h5Host);
        }

        return $provider->redirect($redirectUri);
    }

    public function resolveAppleCallbackH5Host(array $data): string
    {
        $state = (string) ($data['state'] ?? '');
        $candidates = [
            (string) ($data['h5_host'] ?? ''),
            $this->recallAppleOAuthH5Host($state),
            (string) ($this->decodeAppleOAuthState($state)['h5_host'] ?? ''),
            (string) env('H5_BASE_URL', ''),
        ];
        foreach ($candidates as $candidate) {
            $host = rtrim(trim($candidate), '/');
            if ($host !== '' && preg_match('#^https?://#i', $host)) {
                return $host;
            }
        }

        return '';
    }

    public function rememberAppleOAuthH5Host(string $state, string $h5Host): void
    {
        $state = trim($state);
        $h5Host = rtrim(trim($h5Host), '/');
        if ($state === '' || $h5Host === '') {
            return;
        }

        app('redis')->connection('default')->setex('ecx:apple_oauth:h5:' . md5($state), 600, $h5Host);
    }

    public function recallAppleOAuthH5Host(string $state): string
    {
        $state = trim($state);
        if ($state === '') {
            return '';
        }

        $value = app('redis')->connection('default')->get('ecx:apple_oauth:h5:' . md5($state));

        return is_string($value) ? rtrim(trim($value), '/') : '';
    }

    public function encodeAppleOAuthState(string $h5Host): string
    {
        $payload = json_encode(['h5_host' => rtrim(trim($h5Host), '/')], JSON_UNESCAPED_SLASHES);

        return rtrim(strtr(base64_encode((string) $payload), '+/', '-_'), '=');
    }

    public function decodeAppleOAuthState(string $state): array
    {
        $state = trim($state);
        if ($state === '') {
            return [];
        }

        $normalized = strtr($state, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }
        $decoded = json_decode((string) base64_decode($normalized), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{unionid: string, user_type: string, nickname: string, email: string, avatar: string}
     */
    public function resolveUserFromCode(string $trustloginTag, array $configRow, string $code, string $redirectUri): array
    {
        if (!$this->isSocialProvider($trustloginTag)) {
            throw new ResourceException('不支持的第三方登录方式');
        }

        $code = $this->normalizeOAuthCode($code);
        if ($code === '') {
            throw new ResourceException('授权码无效');
        }

        $socialiteConfig = $this->buildSocialiteConfig($trustloginTag, $configRow, $redirectUri);
        $socialite = new SocialiteManager($socialiteConfig);
        $user = $socialite->create(strtolower($trustloginTag))->userFromCode($code);

        $unionid = (string)$user->getId();
        if ($unionid === '') {
            throw new ResourceException('第三方授权信息无效');
        }

        return [
            'unionid' => $unionid,
            'user_type' => $trustloginTag,
            'nickname' => (string)($user->getNickname() ?? $user->getName() ?? ''),
            'email' => (string)($user->getEmail() ?? ''),
            'avatar' => (string)($user->getAvatar() ?? ''),
        ];
    }

    public function sanitizeConfigRow(array $row, bool $forFront = false): array
    {
        if ($forFront) {
            unset($row['secret']);
        }

        if (!isset($row['extra_config']) || $row['extra_config'] === '') {
            $row['extra_config'] = '';

            return $row;
        }

        $extra = $this->parseExtraConfig($row['extra_config']);
        if ($forFront) {
            unset($extra['private_key'], $extra['team_id'], $extra['key_id']);
        } elseif (isset($extra['private_key'])) {
            $extra['private_key'] = '***';
        }
        $row['extra_config'] = $extra === [] ? '' : json_encode($extra, JSON_UNESCAPED_UNICODE);

        return $row;
    }

    public function parseExtraConfig($extraConfig): array
    {
        if (is_array($extraConfig)) {
            return $extraConfig;
        }
        if (!is_string($extraConfig) || trim($extraConfig) === '') {
            return [];
        }

        $decoded = json_decode($extraConfig, true);
        if (is_string($decoded)) {
            $nested = json_decode($decoded, true);
            if (is_array($nested)) {
                $decoded = $nested;
            }
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function normalizeApplePrivateKey(string $privateKey): string
    {
        $privateKey = trim($privateKey);
        if ($privateKey === '' || $privateKey === '***') {
            return $privateKey;
        }

        $privateKey = str_replace(['\\n', "\r\n", "\r"], ["\n", "\n", "\n"], $privateKey);
        if (strpos($privateKey, 'BEGIN PRIVATE KEY') === false) {
            $body = preg_replace('/\s+/', '', $privateKey);
            $privateKey = "-----BEGIN PRIVATE KEY-----\n"
                . chunk_split((string) $body, 64, "\n")
                . "-----END PRIVATE KEY-----";
        }

        return $privateKey;
    }

    public function normalizeOAuthCode(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            return '';
        }

        $prev = null;
        while ($prev !== $code) {
            $prev = $code;
            $decoded = rawurldecode($code);
            if ($decoded === $code) {
                break;
            }
            $code = $decoded;
        }

        return $code;
    }
}
