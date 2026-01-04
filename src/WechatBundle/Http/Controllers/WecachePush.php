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

namespace WechatBundle\Http\Controllers;

use App\Http\Controllers\Controller as Controller;

use WechatBundle\Services\OpenPlatform;
use WechatBundle\Services\MessageService;
use WechatBundle\Services\WechatClickEventService;
use WechatBundle\Events\WechatSubscribeEvent;
use WechatBundle\Events\WxShopsAddEvent;
use WechatBundle\Services\WeappService;
use WechatBundle\Events\WxShopsUpdateEvent;

use EasyWeChat\OpenPlatform\Server\Guard;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Exceptions\Exception;

/**
 * 用于接收微信推送
 *
 * 微信主要有四种推送事件
 * 授权成功（authorized）
 * 授权更新（updateauthorized）
 * 授权取消（unauthorized）
 * 推送 ComponentVerifyTicket（component_verify_ticket）
 */
class WecachePush extends Controller
{
    // 微信全网接入测试账号
    // 测试公众号appid
    public const WECHAT_TEST_OFFICIAL_APPID = [
        'wx570bc396a51b8ff8',
        'wx9252c5e0bb1836fc',
        'wx8e1097c5bc82cde9',
        'wx14550af28c71a144',
        'wxa35b9c23cfe664eb',
    ];
    // 测试小程序appid,暂时用不上
    public const WECHAT_TEST_MINIPROGRAM_APPID = [
        'wxd101a85aa106f53e',
        'wxc39235c15087f6f3',
        'wx7720d01d4b2a4500',
        'wx05d483572dcd5d8b',
        'wx5910277cae6fd970',
    ];

    /**
     * 接收微信授权推送数据
     *
     * @return mixed
     */
    public function authorized()
    {
        try {
            $servicesOpenPlatform = new OpenPlatform();

            $openPlatform = app('easywechat.manager')->openPlatform();
            $server = $openPlatform->server;

            // 处理授权成功事件
            $server->push(function ($message) use ($servicesOpenPlatform, $openPlatform) {
                $servicesOpenPlatform->authorized($openPlatform, $message);
            }, Guard::EVENT_AUTHORIZED);
            // 处理授权更新事件
            $server->push(function ($message) use ($servicesOpenPlatform, $openPlatform) {
                $servicesOpenPlatform->updateauthorized($openPlatform, $message);
            }, Guard::EVENT_UPDATE_AUTHORIZED);
            // 处理授权取消事件
            $server->push(function ($message) use ($servicesOpenPlatform, $openPlatform) {
                $servicesOpenPlatform->unauthorized($openPlatform, $message);
            }, Guard::EVENT_UNAUTHORIZED);

            //缓存component_verify_ticket 并且解密返回success
            return $server->serve();
        } catch (Exception $e) {
            app('log')->error($e->getMessage());
            throw $e;
        }
    }

    public function message($authorizerAppId)
    {
        $servicesOpenPlatform = new OpenPlatform();
        $openPlatform = app('easywechat.open_platform');
        // 第三方平台全网发布验证
        if (in_array($authorizerAppId, self::WECHAT_TEST_OFFICIAL_APPID)) {
            return $this->testOpenPlatformPublish($openPlatform, $authorizerAppId);
        }
        try {
            $officialAccount = $openPlatform->officialAccount($authorizerAppId);
            $server = $officialAccount->server;
            $server->push(function ($message) use ($authorizerAppId) {
                app('log')->info('wechatpush message====>'.var_export($message, 1));
                switch ($message['MsgType']) {
                    case 'event':
                        return $this->eventHandler($message, $authorizerAppId);
                        break;
                    default:
                        $messageService = new MessageService();
                        $data = $messageService->replyMessage($message, $authorizerAppId);
                        if ($data) {
                            return $data;
                        }
                        break;
                }
            });
            return $server->serve();
        } catch (Exception $e) {
            app('log')->debug('wechat callback Message Error:'.$e->getMessage());
            throw $e;
        }
    }

    // 第三方平台全网发布验证
    private function testOpenPlatformPublish($openPlatform, $authorizerAppId)
    {
        $message = $openPlatform->server->getMessage();

        // 事件消息
        if ($message['MsgType'] == 'event') {
            $official_account_client = $openPlatform->officialAccount($authorizerAppId);
            $official_account_client->server->push(function ($message) {
                return $message['Event'] . 'from_callback';
            });
            return $official_account_client->server->serve();
        //返回API文本消息
        } elseif ($message['MsgType'] == 'text' && strpos($message['Content'], 'QUERY_AUTH_CODE:') === 0) {
            list($foo, $authCode) = explode(':', $message['Content']);
            $authorization = $openPlatform->handleAuthorize($authCode);
            $official_account_client = $openPlatform->officialAccount($authorizerAppId, $authorization['authorization_info']['authorizer_refresh_token']);
            $content = $authCode . '_from_api';
            $official_account_client['customer_service']->send([
                'touser' => $message['FromUserName'],
                'msgtype' => 'text',
                'text' => [
                    'content' => $content
                ]
            ]);
            return $official_account_client->server->serve();
        //返回普通文本消息
        } elseif ($message['MsgType'] == 'text' && $message['Content'] == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
            $official_account_client = $openPlatform->officialAccount($authorizerAppId);
            $official_account_client->server->push(function ($message) {
                return $message['Content'] . "_callback";
            });
            return $official_account_client->server->serve();
        }

    }

    /**
     * 事件类型消息处理
     */
    private function eventHandler($message, $authorizerAppId)
    {
        $servicesOpenPlatform = new OpenPlatform();
        $companyId = $servicesOpenPlatform->getCompanyId($authorizerAppId);
        $params = [
            'openId' => $message['FromUserName'],
            'authorizerAppId' => $authorizerAppId,
            'event' => $message['Event'],
            'company_id' => $companyId,
        ];
        switch ($message['Event']) {
            case 'subscribe':
                event(new WechatSubscribeEvent($params));
                //返回被关注自动回复
                $messageService = new MessageService();
                $data = $messageService->subscribeReply($message, $authorizerAppId);
                if ($data) {
                    return $data;
                }
                break;
            case 'unsubscribe':
                event(new WechatSubscribeEvent($params));
                // TODO 取消订阅后用户再收不到公众号发送的消息，因此不需要回复消息
                break;
            case 'CLICK':
                $eventData = [
                    'authorizerAppId' => $authorizerAppId,
                    'openid' => $message['FromUserName'],
                    'key' => $message['EventKey'],
                ];
                $menuService = new WechatClickEventService();
                return $menuService->menuMessageEvent($eventData);
                break;
            case 'VIEW':
                $eventData = [
                    'authorizerAppId' => $authorizerAppId,
                    'openid' => $message['FromUserName'],
                    'key' => $message['EventKey'],
                    'menuId' => $message['MenuID'],
                ];
                break;
            case 'scancode_push':
                $eventData = [
                    'openid' => $message['FromUserName'],
                    'EventKey' => $message['EventKey'],
                    'ScanCodeInfo' => $message['ScanCodeInfo'],
                    'ScanType' => $message['ScanType'],
                    'ScanResult' => $message['ScanResult'],
                ];
                return $text = new Text(['content' => '我是 扫码推 事件']);
                break;
            case 'scancode_waitmsg':
                $eventData = [
                    'openid' => $message['FromUserName'],
                    'key' => $message['EventKey'],
                    'ScanCodeInfo' => $message['ScanCodeInfo'],
                    'ScanType' => $message['ScanType'],
                    'ScanResult' => $message['ScanResult'],
                ];
                return $text = new Text(['content' => '我是扫码推事件且弹出“消息接收中”提示框']);
                break;
            case 'pic_sysphoto':
                $eventData = [
                    'openid' => $message['FromUserName'],
                    'key' => $message['EventKey'],
                ];
                return $text = new Text(['content' => '我是 弹出系统拍照发图的事件']);
                break;
            case 'pic_photo_or_album':
                $eventData = [
                    'openid' => $message['FromUserName'],
                    'key' => $message['EventKey'],
                ];
                return $text = new Text(['content' => '我是 弹出拍照或者相册发图的事件']);
                break;
            case 'pic_weixin':
                $eventData = [
                    'openid' => $message['FromUserName'],
                    'key' => $message['EventKey'],
                ];
                return $text = new Text(['content' => '我是 弹出微信相册发图器的事件']);
                break;
            case 'location_select':
                $eventData = [
                    'openid' => $message['FromUserName'],
                    'key' => $message['EventKey'],
                ];
                return $text = new Text(['content' => '我是 弹出地理位置选择器的事件']);
                break;
            case 'add_store_audit_info':
                $eventData = [
                    'openid' => $message['FromUserName'],
                    'audit_id' => $message['audit_id'],
                    'status' => $message['status'],
                    'reason' => $message['reason'],
                    'is_upgrade' => $message['is_upgrade'],
                    'poiid' => $message['poiid'],
                ];
                event(new WxShopsAddEvent($eventData));
                break;
            case 'modify_store_audit_info':
                $eventData = [
                    'openid' => $message['FromUserName'],
                    'audit_id' => $message['audit_id'],
                    'status' => $message['status'],
                    'reason' => $message['reason'],
                ];
                event(new WxShopsUpdateEvent($eventData));
                break;
            case 'weapp_audit_success': //小程序审核成功
                $weappService = new WeappService($authorizerAppId);
                $weappService->processAudit($message);
                break;
            case 'weapp_audit_fail': //小程序审核失败
                $weappService = new WeappService($authorizerAppId);
                $weappService->processAudit($message);
                break;
        }
    }
}
