<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\WebSocket;

use swoole_websocket_server;
use EspierBundle\Interfaces\WebSocketInterface;
use Swoole\Http\Request;

class PaymentMsg implements WebSocketInterface
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
        app('redis')->connection('espier')->del('websocketPaymentNotifyFdToShopId');
        app('redis')->connection('espier')->del('websocketPaymentNotify');
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
        $sessionVal = app('redis')->connection('wechat')->get('adminSession3rd:' . $requestSession);
        if (!$sessionVal) {
            $this->server->push($request->fd, 401001);
            return false;
        }
        return true;
    }

    public function join(Request $request)
    {
        $client = $request->fd;

        //如果是app连接进来
        $requestSession = $request->header['x-wxapp-session'];
        $sessionVal = app('redis')->connection('wechat')->get('adminSession3rd:' . $requestSession);
        // $sessionVal = '{"open_id":"o8W_00JU7vYN52D0uqhG_l2-VIsM","session_key":"izjolKFyjdUkYk7WcHrWtQ==","appname":"yykzs","phoneNumber":"13816929962","company_id":"1","shop_id":"20","shop_name":"ZE\u6781\u901f\u8fd0\u52a8\u7ad9(\u5f90\u5bb6\u6c47\u5e97)","salesperson_id":"69","salesperson_name":"vic","salesperson_type":"admin"}';
        $user = json_decode($sessionVal, true);

        $shopConnections = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotify', $user['shop_id']);
        if ($shopConnections) {
            $shopConnections = json_decode($shopConnections, true);
        }
        $shopConnections[] = $client;
        app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotify', $user['shop_id'], json_encode($shopConnections));
        //连接ID，对应shopId用于close的时候删除shop_id对应表连接小程序表
        app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotifyFdToShopId', $client, $user['shop_id']);
    }

    public function close($client)
    {
        $shopId = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotifyFdToShopId', $client);
        if ($shopId) {
            app('redis')->connection('espier')->hdel(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotifyFdToShopId', $client);

            $shopConnections = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotify', $shopId);
            if ($shopConnections) {
                $shopConnections = json_decode($shopConnections, true);
                foreach ($shopConnections as $key => $redisFd) {
                    if ($redisFd == $client) {
                        unset($shopConnections[$key]);
                        break;
                    }
                }

                if ($shopConnections) {
                    app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotify', $shopId, json_encode($shopConnections));
                } else {
                    app('redis')->connection('espier')->hdel(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotify', $shopId);
                }
            }
        }
    }

    public function sendMessage($message)
    {
        $shopConnections = app('redis')->connection('espier')->hget(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotify', $message['shopId']);
        if ($shopConnections) {
            $shopConnections = json_decode($shopConnections, true);
            $isPushFlag = true;
            foreach ($shopConnections as $key => $client) {
                try {
                    $this->server->push($client, json_encode($message));
                } catch (\Exception $e) {
                    // 如果推送失败，则表示连接失效了，清除连接中的数据
                    unset($shopConnections[$key]);
                    $isPushFlag = false;
                }
            } // end foreach

            //如果有推送失败的情况
            if (!$isPushFlag) {
                if ($shopConnections) {
                    app('redis')->connection('espier')->hset(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotify', $message['shopId'], json_encode($shopConnections));
                } else {
                    app('redis')->connection('espier')->hdel(WebSocketInterface::KEYPREFIX . ':websocketPaymentNotify', $message['shopId']);
                }
            } // end if
        }
    }
}
