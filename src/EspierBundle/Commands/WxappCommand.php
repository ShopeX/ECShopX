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

namespace EspierBundle\Commands;

use Illuminate\Console\Command;
use swoole_websocket_server;
use EspierBundle\Services\WebSocketService;
use EspierBundle\Interfaces\WebSocketInterface;

class WxappCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动websocket，用于推送支付信息到小程序';

    private $socketServer;

    public $socketClassMap = [
        'rightsmsg' => 'EspierBundle\Services\WebSocket\RightsMsg',
        'paymentmsg' => 'EspierBundle\Services\WebSocket\PaymentMsg',
        'orderzitimsg' => 'EspierBundle\Services\WebSocket\OrderZitiMsg',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $socket_server = config('websocketServer');
        $this->socketServer = new swoole_websocket_server($socket_server['host'], $socket_server['port']);

        // 启动时删除原有的一些KEY
        app('redis')->connection('espier')->del(WebSocketInterface::KEYPREFIX . ':wxappwebsocketclientwithsockettype_system');
        foreach ($this->socketClassMap as $socketClassName) {
            $obj = new WebSocketService(new $socketClassName($this->socketServer));
            $obj->init();
        }

        $this->socketServer->set([
            'worker_num' => $socket_server['options']['worker_num'],
            'daemonize' => false,
        ]);
        $this->socketServer->on('handshake', [$this, 'onHandshake']);
        $this->socketServer->on('message', [$this, 'onMessage']);
        $this->socketServer->on('close', [$this, 'onClose']);
        $this->info(sprintf('Espier websoeckt server listening port %s', $socket_server['port']));
        $this->socketServer->start();
    }

    public function onHandshake($request, $response)
    {
        // CRC: 2367340174
        if (!$request->fd) {
            return false;
        }
        // websocket握手连接算法验证
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }

        $key = base64_encode(sha1(
            $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
            true
        ));

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        // WebSocket connection to 'ws://127.0.0.1:9502/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }
        $response->status(101);
        $response->end();
        echo "swoole websocket connected!" . PHP_EOL;

        $requestSession = isset($request->header['x-wxapp-session']) ? $request->header['x-wxapp-session'] : false;
        if ($requestSession === config('websocketServer.token')) { //如果是服务端推送
            app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':wxappwebsocketclientwithsockettype_system', $request->fd, 1);
            return true;
        } else {
            if (isset($request->header['x-wxapp-sockettype']) && $request->header['x-wxapp-sockettype']) {
                $socketType = $request->header['x-wxapp-sockettype'];
                $gateWay = $this->getWay($socketType);
                if (!$gateWay) {
                    return false;
                }
                if (!$gateWay->checkAuth($request)) { // 各业务的独立验证
                    return false;
                }
                app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':wxappwebsocketclientwithsockettype', $request->fd, $socketType);
                // 将当前连接加入连接池
                $flag = $gateWay->join($request);
                if (!$flag) {
                    return false;
                }
                return true;
            } else {
                return false;
            }
        }
    }

    public function onMessage($socketServer, $frame)
    {
        if (!$frame->fd) {
            return '';
        }
        //get message
        $message = json_decode($frame->data, true);
        if (!$message) {
            return '';
        }
        $gateWay = $this->getWay($message['sockettype']);
        $gateWay->sendMessage($message);
    }

    public function onClose($socketServer, $fd, $reactorId)
    {
        if (!$fd || $reactorId < 0) {
            return '';
        }

        $system_flag = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':wxappwebsocketclientwithsockettype_system', $fd);
        if (!$system_flag) {
            $socketType = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':wxappwebsocketclientwithsockettype', $fd);
            $gateWay = $this->getWay($socketType);
            if ($gateWay) {
                $gateWay->close($fd);
            }
        }
        echo "swoole websocket client-{$fd} is closed" . PHP_EOL;
    }

    public function getWay($socketType)
    {
        if (!array_key_exists($socketType, $this->socketClassMap)) {
            return false;
        }
        $socketClassName = $this->socketClassMap[$socketType];
        return new WebSocketService(new $socketClassName($this->socketServer));
    }
}
