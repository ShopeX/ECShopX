<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
