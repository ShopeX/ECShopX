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

use EspierBundle\Services\BaseService;
use MembersBundle\Services\TrustLoginService;
use MembersBundle\Services\WechatUserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use WechatBundle\Entities\WechatAuth;
use WechatBundle\Entities\Weapp;
use Dingo\Api\Exception\ResourceException;

class OpenPlatform extends BaseService
{
    public function getEntityClass(): string
    {
        return WechatAuth::class;
    }

    /**
     * 返回公众号调用实例
     * @param $authorizerAppId
     * @param $is_direct
     * @return \EasyWeChat\MiniProgram\Application|\EasyWeChat\OpenPlatform\Authorizer\MiniProgram\Application|\EasyWeChat\OpenPlatform\Authorizer\OfficialAccount\Application
     */
    public function getAuthorizerApplication($authorizerAppId, $is_direct = false)
    {
        return WeappService::getAuthApp($authorizerAppId, $is_direct);
    }

    public function getWxaQueryquota()
    {
        $wechatAuth = app('registry')->getManager('default')->getRepository(WechatAuth::class);
        $firstWxaInfo = $wechatAuth->findOneBy(['verify_type_info' => 0, 'bind_status' => 'bind', 'service_type_info' => 3]);
        // 查询失败
        $queryquota = array(
            'errcode' => 0,
            'errmsg' => 'ok',
            'rest' => 0,
            'limit' => 0,
            'speedup_rest' => 0,
            'speedup_limit' => 0,
        );
        if ($firstWxaInfo) {
            try {
                $app = $this->getAuthorizerApplication($firstWxaInfo->getAuthorizerAppid());
                $queryquota = $app->code->queryquota();
            } catch (\Exception $e) {
            }
        }
        return $queryquota;
    }

    /**
     * 小程序加急审核
     *
     * @param $appid 小程序的appid
     */
    public function speedupaudit($appid)
    {
        $app = $this->getAuthorizerApplication($appid);
        $status = $app->code->getLatestAuditStatus();
        if ($status['status'] == 2) {
            $app->code->speedupAudit($status->auditid);
        } else {
            throw new BadRequestHttpException('当前小程序已审核通过');
        }
        return true;
    }

    /**
     * 获取预授权URL
     *
     * @param $preAuthCallbackUrl 微信预授权成功跳转的地址
     *
     * 微信预授权成功跳转的地址，同步
     * 异步回调会走“授权事件接收URL”
     */
    public function getPreAuthUrl($preAuthCallbackUrl)
    {
        $authCallbackUrl = app('easywechat.manager')->openPlatform()->getPreAuthorizationUrl($preAuthCallbackUrl);

        return $authCallbackUrl;
    }

    /**
     * 绑定授权信息和授权账号信息
     *
     * @param $authorizationCode 预授权返回的auth_code
     */
    public function authorizedBind($authorizationCode, $authorizationType)
    {
        $openPlatform = app('easywechat.manager')->openPlatform();
        //使用授权码换取公众号的接口调用凭据和授权信息
        $authorizationInfo = $openPlatform->handleAuthorize($authorizationCode);
        $authorizationInfo = $authorizationInfo['authorization_info'];

        //获取授权方的公众号帐号基本信息
        //设置绑定的微信公众号或者订阅号的基本信息
        $authorizerInfo = $openPlatform->getAuthorizer($authorizationInfo['authorizer_appid']);
        $authorizerInfo = $authorizerInfo['authorizer_info'];

        $operatorId = app('auth')->user()->get('operator_id');
        $companyId = app('auth')->user()->get('company_id');

        return app('registry')->getManager('default')->getRepository(WechatAuth::class)->authorized($companyId, $operatorId, $authorizationInfo, $authorizerInfo, $authorizationType);
    }

    /**
     * @todo 需要与function authorizedBind()方法授权模式代码合并
     * 直连添加小程序或者公众号账号信息
     *
     * @param $data 绑定参数
     * @param $bind_type 绑定类型
     */
    public function directAuthorizedBind($data, $bind_type)
    {
        // 绑定公众号
        if ($bind_type == 'offiaccount') {
            $authorizer_appid = app('registry')->getManager('default')->getRepository(WechatAuth::class)->getAuthorizerAppid($data['company_id']);
            empty($authorizer_appid) ? null : $data['old_authorize_appid'] = $authorizer_appid;
            return app('registry')->getManager('default')->getRepository(WechatAuth::class)->directAuthorized($data, $bind_type);
        }
        // 绑定小程序
        //根据模板获取绑定的小程序
        $oldAppid = null;
        $weappinfo = app('registry')->getManager('default')->getRepository(Weapp::class)->findOneBy(['company_id' => $data['company_id'], 'template_name' => $data['template_name']]);
        if ($weappinfo) {
            $oldAppid = $weappinfo->getAuthorizerAppid();
            $authinfo = app('registry')->getManager('default')->getRepository(WechatAuth::class)->findOneBy(['authorizer_appid' => $oldAppid]);
            if ($authinfo && intval($authinfo->getIsDirect()) != 1) {
                throw new ResourceException("历史绑定的小程序不是直连绑定，不能更换为直连");
            }
            empty($authinfo) ? null : $data['old_authorize_appid'] = $oldAppid;
            //历史appid不等于入参appid时，认定为更换直连授权，需要验证是否有用户授权信息
            if ($authinfo && $authinfo->getauthorizerappid() != $data['authorizer_appid']) {
                $wechatUserService = new WechatUserService();
                $wechatUserService->checkWechatUser($authinfo->getauthorizerappid(), $data['company_id']);
            }
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 添加小程序授权表数据
            $result = app('registry')->getManager('default')->getRepository(WechatAuth::class)->directAuthorized($data, $bind_type);

            // 保存到模板表
            $weappSaveData = [
                'authorizer_appid' => trim($data['authorizer_appid']),
                'operator_id' => $data['operator_id'],
                'company_id' => $data['company_id'],
                'template_id' => 0,
                'template_name' => $data['template_name'],
                'template_ver' => 1,
                'audit_status' => 0,
            ];
            empty($oldAppid) ? $oldAppid = trim($data['authorizer_appid']) : null;
            app('registry')->getManager('default')->getRepository(Weapp::class)->createWeapp($oldAppid, $weappSaveData);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
        return $result;
    }

    /**
     * 授权成功处理，授权成功在同步的时候已处理
     * 当前授权接收信息获取不到当前用户登录状态，则暂时不做处理
     * 以同步处理授权为准
     */
    public function authorized($openPlatform, $message)
    {
        // TODO 处理授权后需要异步同步的信息
        return true;
    }

    /**
     * 取消授权处理
     */
    public function unauthorized($openPlatform, $message)
    {
        app('registry')->getManager('default')->getRepository(WechatAuth::class)->unauthorized($message['AuthorizerAppid']);
        app('registry')->getManager('default')->getRepository(Weapp::class)->deleteWeapp($message['AuthorizerAppid']);
    }

    /**
     * 更新授权处理
     */
    public function updateauthorized($openPlatform, $message)
    {
        $authorizationInfo = $openPlatform->handleAuthorize($message['AuthorizationCode']);
        $authorizationInfo = $authorizationInfo['authorization_info'];

        //获取授权方的公众号帐号基本信息
        //设置绑定的微信公众号或者订阅号的基本信息
        $authorizerInfo = $openPlatform->getAuthorizer($message['authorizerAppId']);
        $authorizerInfo = $authorizerInfo['authorizer_info'];

        app('registry')->getManager('default')->getRepository(WechatAuth::class)->upauthorized($authorizationInfo, $authorizerInfo);
    }

    public function getAuthorizerInfo($authorizerAppId)
    {
        return app('registry')->getManager('default')->getRepository(WechatAuth::class)->getAuthorizerInfo($authorizerAppId);
    }

    public function getCompanyId($authorizerAppId)
    {
        return app('registry')->getManager('default')->getRepository(WechatAuth::class)->getCompanyId($authorizerAppId);
    }

    /**
     * 根据企业ID获取公众号appid
     */
    public function getWoaAppidByCompanyId($companyId)
    {
        return $this->getRepository()->getWoaAppidByCompanyId($companyId);
    }

    /**
     * 获取微信公众号的服务
     * @param array $filter 过滤条件
     * @param array $woaAppInfo 微信公众的信息
     * @return \EasyWeChat\OfficialAccount\Application|null
     * @throws \Exception
     */
    public function getWoaApp(array $filter)
    {
        if (empty($filter["company_id"])) {
            throw new \Exception("参数有误！");
        }

        $openPlatform = new OpenPlatform();
        $appId = $openPlatform->getWoaAppidByCompanyId($filter["company_id"]);
        //公众号授权模式
        if (!empty($appId)) {
            return $openPlatform->getAuthorizerApplication($appId);
        } else {
            //普通填参模式
            $configInfo = (new TrustLoginService())->getConfigRow($filter["trustlogin_tag"], $filter["version_tag"], $filter["company_id"]);
            if (!empty($configInfo) && !empty($configInfo["status"])) {
                return app('easywechat.official_account', $configInfo);
            }
        }
        app("log")->info(sprintf("woaAppError:%s", jsonEncode([
            "appid" => $appId,
            "filter" => $filter,
            "configInfo" => $configInfo ?? null
        ])));
        throw new ResourceException("公众号信息有误！");
    }
}
