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

use EspierBundle\Interfaces\WebSocketInterface;
use swoole_websocket_server;
use Swoole\Http\Request;

class OrderTipMsg implements WebSocketInterface
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
        $this->redis->del(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyuserid');
        $this->redis->del(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyclientid');
    }

    public function checkAuth(Request $request)
    {
        $token = $request->get['token'];
        $token = str_replace('Bearer', '', $token);
        $token = trim($token, ' ');
        $jwt = $this->verifyToken($token);
        if (!$jwt) {
            $this->close($request->fd);
        }
        return $jwt;
    }

    public function join(Request $request)
    {
        $token = $request->get['token'];
        $token = str_replace('Bearer', '', $token);
        $token = trim($token, ' ');
        $jwt = $this->verifyToken($token);
        if (!$jwt['company_id']) {
            return false;
        }
        $client = $request->fd;
        $this->redis->hset(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyuserid', $jwt['company_id'], $client);
        $this->redis->hset(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyclientid', $client, $jwt['company_id']);
        return true;
    }

    public function close($client)
    {
        $companyId = $this->redis->hget(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyclientid', $client);
        if ($companyId) {
            $this->redis->hdel(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyclientid', $client);
            $clientInfo = $this->redis->hget(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyuserid', $companyId);
            if ($clientInfo) {
                $this->redis->hdel(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyuserid', $companyId);
            }
        }
    }

    public function sendMessage($message)
    {
        $client = $this->redis->hget(WebSocketInterface::KEYPREFIX . ':ordertipsgatewaywebsocketbyuserid', $message['company_id']);
        if ($client) {
            try {
                $this->server->push($client, json_encode($message));
            } catch (\Exception $e) {
                // 如果推送失败，则表示连接失效了，清除连接中的数据
                $this->close($client);
            }
        }
    }

    public function check($client, $message)
    {
        try {
            $this->server->push($client, json_encode($message));
        } catch (\Exception $e) {
            // 如果推送失败，则表示连接失效了，清除连接中的数据
            $this->close($client);
        }
    }

    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $Token 需要验证的token
     * @return bool|string
     */
    public function verifyToken(string $Token)
    {
        $key = config('common.jwt_secret');
        $tokens = explode('.', $Token);
        if (count($tokens) != 3) {
            return false;
        }

        list($base64header, $base64payload, $sign) = $tokens;

        //获取jwt算法
        $base64decodeheader = json_decode($this->base64UrlDecode($base64header), JSON_OBJECT_AS_ARRAY);

        if (empty($base64decodeheader['alg'])) {
            return false;
        }

        //签名验证
        if ($this->signature($base64header . '.' . $base64payload, $key, $base64decodeheader['alg']) !== $sign) {
            return false;
        }
        $payload = json_decode($this->base64UrlDecode($base64payload), JSON_OBJECT_AS_ARRAY);

        //签发时间大于当前服务器时间验证失败
        if (isset($payload['iat']) && $payload['iat'] > time()) {
            return false;
        }

        //过期时间小宇当前服务器时间验证失败
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        //该nbf时间之前不接收处理该Token
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return false;
        }

        return $payload;
    }

    /**
     * base64UrlEncode  https://jwt.io/ 中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    public function base64UrlEncode(string $input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode https://jwt.io/ 中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    public function base64UrlDecode(string $input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * HMACSHA256签名  https://jwt.io/ 中HMACSHA256签名实现
     * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
     * @param string $key
     * @param string $alg 算法方式
     * @return mixed
     */
    public function signature(string $input, string $key, $alg = "HS256")
    {
        $alg_config = [
            'HS256' => 'sha256'
        ];
        return $this->base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key, true));
    }
}
