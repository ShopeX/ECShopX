<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\WebSocket;

use swoole_websocket_server;
use EspierBundle\Interfaces\WebSocketInterface;
use MembersBundle\Services\UserService;
use MembersBundle\Services\WechatUserService;
use Swoole\Http\Request;

class RightsMsg implements WebSocketInterface
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
        app('redis')->connection('espier')->del(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyuserid');
        app('redis')->connection('espier')->del(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyclientid');
    }

    public function checkAuth(Request $request)
    {
        // 验证登录
        $requestSession = isset($request->header['x-wxapp-session']) ? $request->header['x-wxapp-session'] : false;
        if (!$requestSession) {
            $this->server->push($request->fd, 401001);
            return false;
        }

        //如果是app连接进来
        $sessionVal = app('redis')->connection('wechat')->get('session3rd:' . $requestSession);
        if (!$sessionVal) {
            $this->server->push($request->fd, 401001);
            return false;
        }
        return true;
    }

    public function join(Request $request)
    {
        $user_id = $this->getUser($request->header['x-wxapp-session']);
        if (!$user_id) {
            return false;
        }
        $client = $request->fd;
        app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyuserid', $user_id, $client);
        app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyclientid', $client, $user_id);
    }

    public function close($client)
    {
        $user_id = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyclientid', $client);
        if ($user_id) {
            app('redis')->connection('espier')->hdel(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyclientid', $client);

            $clientInfo = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyuserid', $user_id);
            if ($clientInfo) {
                app('redis')->connection('espier')->hdel(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyuserid', $user_id);
            }
        }
    }

    public function sendMessage($message)
    {
        $client = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':rightsgatewaywebsocketbyuserid', $message['user_id']);
        try {
            $this->server->push($client, json_encode($message));
        } catch (\Exception $e) {
            // 如果推送失败，则表示连接失效了，清除连接中的数据
            $this->close($client);
        }
    }

    protected function getUser($requestSession)
    {
        $sessionVal = app('redis')->connection('wechat')->get('session3rd:' . $requestSession);
        // $sessionVal = '{"open_id":"owGAQ0VoLl2AZ7zc7PG8AHcwl6bM","union_id":"ofQlA07zcjVIhx4SRBqgDdh1BH4Q","session_key":"jebpsl3ZHAbdUOA4V8aQCg=="}';
        $sessionVal = json_decode($sessionVal, true);
        $userService = new UserService(new WechatUserService());
        $user = $userService->getUserInfo(['open_id' => $sessionVal['open_id'], 'unionid' => $sessionVal['union_id']]);

        if ($user['user_id']) {
            return $user['user_id'];
        }
        return false;
    }
}
