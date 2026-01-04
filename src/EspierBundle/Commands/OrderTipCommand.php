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

class OrderTipCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:ordertip:start';

    private $redis;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动websocket，用于推送订单是否成功';

    private $socketServer;

    public $socketClassMap = [
        'ordertips' => 'EspierBundle\Services\WebSocket\OrderTipMsg',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->redis = app('redis')->connection('espier');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // ShopEx EcShopX Service Component
        $socket_server = config('websocketServer');
        $this->socketServer = new swoole_websocket_server($socket_server['host'], $socket_server['port']);

        // 启动时删除原有的一些KEY
        $this->redis->del(WebSocketInterface::KEYPREFIX . ':wxappwebsocketclientwithsockettype_system');
        foreach ($this->socketClassMap as $socketClassName) {
            $obj = new WebSocketService(new $socketClassName($this->socketServer));
            $obj->init();
        }

        $this->socketServer->set([
            'worker_num' => 8,
            'daemonize' => false,
        ]);
        $this->socketServer->on('handshake', [$this, 'onHandshake']);
        $this->socketServer->on('message', [$this, 'onMessage']);
        $this->socketServer->on('request', [$this, 'onRequest']);
        $this->socketServer->on('close', [$this, 'onClose']);
        $this->info(sprintf('Espier websoeckt server listening port %s', $socket_server['port']));
        $this->socketServer->start();
    }

    public function onHandshake($request, $response)
    {
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
        $gateWay = $this->getWay('ordertips');
        if (isset($request->header['sec-websocket-protocol']) && $request->header['sec-websocket-protocol']) {
            if (!$gateWay) {
                return false;
            }
            if (!$gateWay->checkAuth($request)) { // 各业务的独立验证
                $gateWay->close($request->fd);
                return false;
            }
            // 将当前连接加入连接池
            $flag = $gateWay->join($request);
            if (!$flag) {
                $gateWay->close($request->fd);
                return false;
            }
            return true;
        } else {
            $gateWay->close($request->fd);
            return false;
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
        $gateWay = $this->getWay('ordertips');
        $gateWay->check($frame->fd, $message);
    }

    public function onRequest($request, $response)
    {
        // TODO: optimize this method
        $companyId = $request->post['company_id'];
        if (!$companyId) {
            return '';
        }
        if (!isset($request->post['tips_ws_key']) || config('common.tips_ws_key') != $request->post['tips_ws_key']) {
            return '';
        }
        unset($request->post['tips_ws_key']);
        $gateWay = $this->getWay('ordertips');
        $gateWay->sendMessage($request->post);
    }

    public function onClose($socketServer, $fd)
    {
        if (!$fd) {
            return '';
        }
        $system_flag = $this->redis->hget(WebSocketInterface::KEYPREFIX . ':wxappwebsocketclientwithsockettype_system', $fd);
        if (!$system_flag) {
            $gateWay = $this->getWay('ordertips');
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
