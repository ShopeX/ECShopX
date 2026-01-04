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

namespace EspierBundle\Providers\WebSocket;

class WebSocketManager
{
    public $app;

    public $type;

    public $websocket;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function driver($type = '')
    {
        $this->type = $type;

        return $this;
    }

    public function send($message)
    {
        $websocketHost = $this->app->make('config')->get('websocketServer.host');
        $websocketToken = $this->app->make('config')->get('websocketServer.token');
        $options = [
            'headers' => [
                'x-wxapp-sockettype' => $this->type,
                'x-wxapp-session' => $websocketToken,
                'host' => $websocketHost,
            ]
        ];
        $url = 'wss://' . $websocketHost;
        // $url = 'ws://127.0.0.1:9051'; // 本地测试使用
        if (!$this->websocket[$this->type]) {
            $this->websocket[$this->type] = new \WebSocket\Client($url, $options);
        }
        $message['sockettype'] = $this->type;
        $this->websocket[$this->type]->send(json_encode($message));
    }
}
