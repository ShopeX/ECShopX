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

namespace EspierBundle\Services\WebSocket;

use swoole_websocket_server;
use EspierBundle\Interfaces\WebSocketInterface;
use MembersBundle\Services\UserService;
use MembersBundle\Services\WechatUserService;
use Swoole\Http\Request;

class OrderZitiMsg implements WebSocketInterface
{
    private $redis;

    private $server;

    public function __construct(swoole_websocket_server $server)
    {
        $this->redis = app('redis')->connection('espier');
        $this->server = $server;
    }

    public function init()
    {
        app('redis')->connection('espier')->del(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyuserid');
        app('redis')->connection('espier')->del(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyclientid');
    }

    public function checkAuth(Request $request)
    {
        // 验证登录
        $requestSession = isset($request->header['x-wxapp-session']) ? $request->header['x-wxapp-session'] : false;

        $requestAuth = isset($request->header['authorization']) ? $request->header['authorization'] : false;

        if (!$requestSession && !$requestAuth) {
            $this->server->push($request->fd, 401001);
            return false;
        }

        if ($requestSession) {
            //如果是app连接进来
            $sessionVal = app('redis')->connection('wechat')->get('session3rd:' . $requestSession);
            if (!$sessionVal) {
                $this->server->push($request->fd, 401001);
                return false;
            }
        }

        return true;
    }

    public function join(Request $request)
    {
        $user_id = $this->getUser($request);
        if (!$user_id) {
            return false;
        }
        $client = $request->fd;
        app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyuserid', $user_id, $client);
        app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyclientid', $client, $user_id);
    }

    public function close($client)
    {
        $user_id = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyclientid', $client);
        if ($user_id) {
            app('redis')->connection('espier')->hdel(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyclientid', $client);

            $clientInfo = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyuserid', $user_id);
            if ($clientInfo) {
                app('redis')->connection('espier')->hdel(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyuserid', $user_id);
            }
        }
    }

    public function sendMessage($message)
    {
        $client = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':orderzitigatewaywebsocketbyuserid', $message['user_id']);
        try {
            $this->server->push($client, json_encode($message));
        } catch (\Exception $e) {
            // 如果推送失败，则表示连接失效了，清除连接中的数据
            $this->close($client);
        }
    }

    protected function getUser(Request $request)
    {
        $requestSession = isset($request->header['x-wxapp-session']) ? $request->header['x-wxapp-session'] : false;

        $requestAuth = isset($request->header['authorization']) ? $request->header['authorization'] : false;

        if ($requestSession) {
            $sessionVal = app('redis')->connection('wechat')->get('session3rd:' . $requestSession);
            // $sessionVal = '{"open_id":"owGAQ0VoLl2AZ7zc7PG8AHcwl6bM","union_id":"ofQlA07zcjVIhx4SRBqgDdh1BH4Q","session_key":"jebpsl3ZHAbdUOA4V8aQCg=="}';
            $sessionVal = json_decode($sessionVal, true);
            $userService = new UserService(new WechatUserService());
            $user = $userService->getUserInfo(['open_id' => $sessionVal['open_id'], 'unionid' => $sessionVal['union_id']]);

            if ($user['user_id']) {
                return $user['user_id'];
            }
        }

        if ($requestAuth) {
            $guard = isset($request->header['guard']) ? $request->header['guard'] : false;
            if ($guard) {
                config(['auth.defaults.guard' => $guard]);
            }
            $illuminateRequest = new IlluminateRequest();
            $request = $illuminateRequest->toIlluminateRequest($request);
            $auth = app('auth')->setRequest($request);
            $auth->getPayload();
            $userId = $auth->user()->get('user_id');
            if ($userId) {
                return $userId;
            }
        }

        return false;
    }
}
