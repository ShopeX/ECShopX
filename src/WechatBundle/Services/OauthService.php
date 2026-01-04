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

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Hashids\Hashids;
use MembersBundle\Entities\MembersAssociations;

class OauthService
{
    public const STATUS_WXCODE_WRIT = 0; // 等待扫码
    public const STATUS_WXCODE_SWEEP = 1; // 已扫码
    public const STATUS_WXCODE_SUCCESS = 2; // 扫码成功
    public const STATUS_WXCODE_ERROR = 3; // 扫码失败
    public const STATUS_WXCODE_EXPIRED = 4; // 二维码过期
    public const STATUS_WXCODE_NOTHING = 5; // 二维码不存在

    public const STATUS_USER_UNREGISTERED = 0; // 用户未注册
    public const STATUS_USER_REGISTERED = 1; // 用户已注册

    public const TOKEN_EXP = 120;

    /**
     * 创建access_token
     * @return string
     */
    public function accessTokenCreated()
    {
        $token = $this->getAccessToken();
        $info = [
            'exp' => time() + (config('common.pc_wxcode_login') ?? self::TOKEN_EXP),
            'time' => time(),
            'status' => self::STATUS_WXCODE_WRIT,
        ];
        $redis = app('redis')->connection('members');
        $key = 'member:oauth:login:' . $token;
        $redis->set($key, json_encode($info));
        $redis->expire($key, (config('common.pc_wxcode_login') ?? (self::TOKEN_EXP + 3600)));
        return $token;
    }

    public function getAccessTokenValid($token)
    {
        $redis = app('redis')->connection('members');
        $key = 'member:oauth:login:' . $token;
        $info = json_decode($redis->get($key), true);
        if (!isset($info['status']) || !isset($info['exp'])) {
            $data = [
                'status' => self::STATUS_WXCODE_NOTHING,
                'msg' => '二维码信息出错',
            ];
            return $data;
        }
        if (time() > $info['exp'] && $info['status'] == self::STATUS_WXCODE_WRIT) {
            $data = [
                'status' => self::STATUS_WXCODE_EXPIRED,
                'msg' => '验证过期',
            ];
            return $data;
        }

        switch ($info['status']) {
            case self::STATUS_WXCODE_WRIT:
                $data = [
                    'status' => self::STATUS_WXCODE_WRIT,
                    'msg' => '扫码登录失败，请刷新重试或选择其他登录方式',
                ];
                break;
            case self::STATUS_WXCODE_SWEEP:
                $data = [
                    'status' => self::STATUS_WXCODE_SWEEP,
                    'msg' => '扫描成功',
                ];
                break;
            case self::STATUS_WXCODE_SUCCESS:
                $data = [
                    'status' => self::STATUS_WXCODE_SUCCESS,
                    'msg' => '登录成功',
                    'token' => $info['token']
                ];
                break;
            case self::STATUS_WXCODE_ERROR:
                $data = [
                    'status' => self::STATUS_WXCODE_ERROR,
                    'msg' => '未授权登录',
                ];
        }
        return $data;
    }

    /**
     * 扫描二维码
     * @param $unionId
     * @param $token
     * @return bool
     */
    public function accessTokenSweep($unionId, $token)
    {
        try {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $memberInfo = $membersAssociationsRepository->get(['unionid' => $unionId]);
            if (!$memberInfo) {
                return false;
            }
            $redis = app('redis')->connection('members');
            $key = 'member:oauth:login:' . $token;
            $info = json_decode($redis->get($key), true);
            if (!$info) {
                throw new ResourceException('授权失败');
            }
            if (isset($info['exp']) && time() > $info['exp']) {
                throw new ResourceException('授权码已过期');
            }
            if (isset($info['status']) && $info['status'] != self::STATUS_WXCODE_WRIT) {
                throw new ResourceException('授权码已被使用');
            }
            $info['status'] = self::STATUS_WXCODE_SWEEP;
            $info['union_id'] = $unionId;
            $redis->set($key, json_encode($info));
            return true;
        } catch (\Exception $exception) {
            throw new ResourceException($exception->getMessage());
        }
    }

    public function accessTokenAuthorize($input)
    {
        if (!isset($input['openid'])) {
            if (!isset($input['token']) && !$input['token']) {
                throw new ResourceException('授权失败');
            }
            if (!isset($input['code']) && !$input['code']) {
                throw new ResourceException('授权失败');
            }
            if (!isset($input['appid']) && !$input['appid']) {
                throw new ResourceException('授权失败');
            }
            $openPlatform = new OpenPlatform();
            $app = $openPlatform->getAuthorizerApplication($input['appid']);
            $wxParams = [
                'code' => $input['code'],
                'appid' => $input['appid'],
            ];
            $res = $app->auth->session($input['code']);
            if (!isset($res['openid'])) {
                throw new StoreResourceFailedException('小程序信息错误，请联系供应商！');
            }
            $input['open_id'] = $res['openid'];
        }
        if ($input['status'] ?? 0) {
            return $this->accessTokenSuccess($input['token'], $input['open_id'], $input['appid']);
        } else {
            return $this->accessTokenError($input['token']);
        }
    }

    private function accessTokenSuccess($token, $openid, $appid)
    {
        try {
            $redis = app('redis')->connection('members');
            $key = 'member:oauth:login:' . $token;
            $info = json_decode($redis->get($key), true);
            if (!$info) {
                throw new ResourceException('授权失败');
            }
            if (isset($info['status']) && $info['status'] != self::STATUS_WXCODE_SWEEP) {
                throw new ResourceException('授权码已被使用');
            }
            $info['status'] = self::STATUS_WXCODE_SUCCESS;
            $credentials = ['auth_type' => 'oauth', 'token' => $token, 'openid' => $openid, 'appid' => $appid];
            $accessToken = app('auth')->guard('h5api')->attempt($credentials);
            $info['token'] = $accessToken;
            $redis->set($key, json_encode($info));
            return true;
        } catch (\Exception $exception) {
            throw new ResourceException($exception->getMessage());
        }
    }

    private function accessTokenError($token)
    {
        try {
            $redis = app('redis')->connection('members');
            $key = 'member:oauth:login:' . $token;
            $info = json_decode($redis->get($key), true);
            if (!$info) {
                throw new ResourceException('授权失败');
            }
            if (isset($info['status']) && $info['status'] != self::STATUS_WXCODE_SWEEP) {
                throw new ResourceException('授权码已被使用');
            }
            $info['status'] = self::STATUS_WXCODE_ERROR;
            $redis->set($key, json_encode($info));
            return true;
        } catch (\Exception $exception) {
            throw new ResourceException($exception->getMessage());
        }
    }

    /**
     * 获取时间戳到毫秒
     * @return bool|string
     */
    private function getAccessToken()
    {
        $time = time();
        $redis = app('redis')->connection('members');
        $key = 'member:oauth:login:times:' . $time;
        $num = $redis->incr($key);
        $redis->expire($key, 60);
        $hashids = new Hashids($time, 12);
        return $hashids->encode($num);
    }
}
