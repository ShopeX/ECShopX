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

use WechatBundle\Entities\WechatAuth;
use WechatBundle\Entities\Weapp;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use WechatBundle\Services\Wxapp\TemplateService;
use SuperAdminBundle\Services\WxappTemplateService;
use CompanysBundle\Services\CompanysService;
use YoushuBundle\Entities\YoushuSetting;
use DistributionBundle\Services\DistributorService;

class WeappService
{
    // 公司id
    public $companyId;

    // 小程序或者公众号appid
    public $wxaAppId;

    // 小程序或者公众号实例化
    public $wxa;

    public function __construct($wxaAppId = null, $companyId = null)
    {
        if ($wxaAppId) {
            //如果是微信异步通知，则不需要验证wxaAppId
            if ($companyId) {
                $this->checkWxaAppId($companyId, $wxaAppId);
                $this->companyId = $companyId;
            }

            $this->wxaAppId = $wxaAppId;

            $openPlatform = new OpenPlatform();
            $this->wxa = $openPlatform->getAuthorizerApplication($wxaAppId);
        }
    }

    /**
     * 验证解密小程序登录数据
     */
    public static function decryptData($wxapp, $res, $inputData, $is_direct = false)
    {
        if ($is_direct) {
            $data = $wxapp->encryptor->decryptData(
                $res->session_key,
                $inputData['iv'],
                $inputData['encryptedData']
            );
        } else {
            $data = $wxapp->encryptor->decryptData(
                $inputData['appid'],
                $res->session_key,
                $inputData['signature'],
                $inputData['rawData'],
                $inputData['iv'],
                $inputData['encryptedData']
            );
        }

        return $data;
    }

    /**
     * @param $authorizerAppId
     * @param $is_direct
     * @return \EasyWeChat\MiniProgram\Application|\EasyWeChat\OpenPlatform\Authorizer\OfficialAccount\Application|\EasyWeChat\OpenPlatform\Authorizer\MiniProgram\Application
     *  \EasyWeChat\MiniProgram\Application app('easywechat.manager')->wxapp($inputData);
     *  \EasyWeChat\OpenPlatform\Authorizer\OfficialAccount\Application 公众号服务 app('easywechat.manager')->app($authorizerAppId);
     *  \EasyWeChat\OpenPlatform\Authorizer\MiniProgram\Application 小程序服务 app('easywechat.manager')->app($authorizerAppId);
     */
    public static function getAuthApp($authorizerAppId, $is_direct = false)
    {
        /**
         * @var \WechatBundle\Repositories\WechatAuthRepository $WechatAuth
         */
        $WechatAuth = app('registry')->getManager('default')->getRepository(WechatAuth::class);
        $authInfo = $WechatAuth->getWxauthDetail($authorizerAppId);
        if ($is_direct == false && (isset($authInfo['is_direct']) && $authInfo['is_direct'])) {
            $is_direct = true;
        }
        if ($is_direct) {
            return app('easywechat.manager')->app_direct($authorizerAppId);
        } else { // 通过第三方开放平台模式调用小程序或者公众号
            return app('easywechat.manager')->app_openplatform($authorizerAppId);
        }
    }

    public function checkWxaAppId($companyId, $wxaAppId)
    {
        $flag = app('registry')->getManager('default')->getRepository(WechatAuth::class)->checkWxaAppId($companyId, $wxaAppId);
        if (!$flag) {
            throw new BadRequestHttpException('小程序未绑定，请重新绑定', null, 400001);
        }
        return true;
    }

    /**
     * 获取授权的微信小程序列表
     */
    public function getWxaList($companyId)
    {
        return app('registry')->getManager('default')->getRepository(WechatAuth::class)->getWxaList($companyId);
    }

    /**
     * 获取授权的微信小程序详情
     */
    public function getWxaDetail($companyId, $wxaAppId)
    {
        $detail = app('registry')->getManager('default')->getRepository(WechatAuth::class)->checkWxaAppId($companyId, $wxaAppId);
        if (!$detail) {
            throw new BadRequestHttpException('小程序未绑定，请重新绑定', null, 400001);
        }

        $weappInfo = app('registry')->getManager('default')->getRepository(Weapp::class)->getWeappInfo($companyId, $wxaAppId);

        $wxappTemplateService = new WxappTemplateService();
        $filter['key_name'] = $weappInfo['template_name'];
        $templateData = $wxappTemplateService->getInfo($filter);
        if (!$templateData) {
            $templateData = config('wxa.'.$weappInfo['template_name']);
        }
        $result = [
            'authorizer_appid' => $detail->getAuthorizerAppid(),
            'authorizer_appsecret' => $detail->getAuthorizerAppSecret(),
            'auto_publish' => $detail->getAutoPublish(),
            'nick_name' => $detail->getNickName(),
            'head_img' => $detail->getHeadImg(),
            'service_type_info' => $detail->getServiceTypeInfo(),
            'verify_type_info' => $detail->getVerifyTypeInfo(),
            'signature' => $detail->getSignature(),
            'principal_name' => $detail->getPrincipalName(),
            'business_info' => $detail->getBusinessInfo(),
            'qrcode_url' => $detail->getQrcodeUrl(),
            'operator_id' => $detail->getOperatorId(),
            'bind_status' => $detail->getBindStatus(),
            'company_id' => $detail->getCompanyId(),
            'weapp' => $weappInfo ? $weappInfo : "",
            'weappTemplate' => $weappInfo ? $templateData : "",
        ];

        return $result;
    }

    public function getWxappidByTemplateName($companyId, $templateName = 'yykweishop')
    {
        return app('registry')->getManager('default')->getRepository(Weapp::class)->getWxappidByTemplateName($companyId, $templateName);
    }

    /**
     * 获取小程序模板信息
     */
    public function getWeappInfo($companyId, $wxaAppId)
    {
        $filter = [
            'company_id' => $companyId,
            'authorizer_appid' => $wxaAppId,
        ];
        $result = [];
        $detail = app('registry')->getManager('default')->getRepository(Weapp::class)->findOneBy($filter);
        if ($detail) {
            $result = [
                'authorizer_appid' => $detail->getAuthorizerAppid(),
                'operator_id' => $detail->getOperatorId(),
                'company_id' => $detail->getCompanyId(),
                'reason' => $detail->getReason(),
                'audit_status' => $detail->getAuditStatus(),
                'release_status' => $detail->getReleaseStatus(),
                'audit_time' => $detail->getAuditTime(),
                'template_id' => $detail->getTemplateId(),
                'template_name' => $detail->getTemplateName(),
                'template_ver' => $detail->getTemplateVer(),
                'release_ver' => $detail->getReleaseVer(),
                'visitstatus' => $detail->getVisitstatus()
            ];
        }
        return $result;
    }

    /**
     * 提交小程序审核
     *
     * @param string $authorizerAppId 公众号appid
     * @param string $operatorId 操作员
     * @param string $templateName 小程序模板名称
     */
    public function submitAudit($companyId, $authorizerAppId, $operatorId, $templateName, $wxaName, $options = array(), $isOnlyCommitCode = false)
    {
        $weappInfo = app('registry')->getManager('default')->getRepository(Weapp::class)->getWeappInfo($companyId, $this->wxaAppId);
        if (!$weappInfo) { // 第一次新增
            $weappSaveData = [
                'authorizer_appid' => $this->wxaAppId,
                'operator_id' => $operatorId,
                'company_id' => $companyId,
                'template_id' => -1,
                'template_name' => $templateName,
                'template_ver' => 'v0.0.0',
                'audit_status' => 3,
            ];
            $result = app('registry')->getManager('default')->getRepository(Weapp::class)->createWeapp($this->wxaAppId, $weappSaveData);
            return $result;
        }
        $templateService = new TemplateService();
        $templateService->submitAuditCheck($companyId, $authorizerAppId, $this->wxaAppId, $templateName, $wxaName);

        $wxappTemplateService = new WxappTemplateService();
        $filter['key_name'] = $templateName;
        $templateData = $wxappTemplateService->getInfo($filter);
        if (!$templateData) {
            $templateData = config('wxa.'.$templateName);
        }

        // 临时写死，后期可做为前端配置
        if (in_array($templateName, ['yykweishop'])) {
            $options['window'] = [
                'backgroundTextStyle' => 'light',
                'navigationBarBackgroundColor' => '#fff',
                'navigationBarTitleText' => $wxaName,
                'navigationBarTextStyle' => "black",
            ];
        }

        try {
            // 根据需要上架的小程序名称修改小程序服务器地址
            $this->modifyDomain($templateData['domain']);
        } catch (\Exception $e) {
            app('api.exception')->report($e);
        }

        //上传代码
        $this->commitTemplate($companyId, $templateData, $wxaName, $options);
        sleep(2); // 微信让上传代码和提交审核有个时间差，先检测隐私，再提交审核，否则会报错
        //保存信息到数据库
        $weappSaveData = [
            'authorizer_appid' => $this->wxaAppId,
            'operator_id' => $operatorId,
            'company_id' => $this->companyId,
            'template_id' => $templateData['template_id'],
            'template_name' => $templateName,
            'template_ver' => $templateData['version'],
            'audit_status' => 2,
        ];
        if ($isOnlyCommitCode) {
            $result = app('registry')->getManager('default')->getRepository(Weapp::class)->createWeapp($this->wxaAppId, $weappSaveData);
            return $result;
        }

        $category = $this->wxa->code->getCategory();
        if ($category['category_list']) {
            //可能需要遍历到最合适的服务类目，然后提交给审核
            //目前取第一个服务类目提交给审核
            $submitAuditData['item_list'] = array(
                [
                    'address' => 'pages/index',
                    'tag' => $templateData['tag'],
                    'first_class' => $category['category_list'][0]['first_class'],
                    'second_class' => $category['category_list'][0]['second_class'],
                    'first_id' => $category['category_list'][0]['first_id'],
                    'second_id' => $category['category_list'][0]['second_id'],
                    'title' => '首页',
                ],
            );
        } else {
            throw new BadRequestHttpException('请在微信小程序中设置服务类目');
        }

        // 订单中心path
        $submitAuditData['order_path'] = '/subpage/pages/trade/list';

        // 开启事务，提交成功再保存信息
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = app('registry')->getManager('default')->getRepository(Weapp::class)->createWeapp($this->wxaAppId, $weappSaveData);
            $status = $this->wxa->code->submitAudit($submitAuditData);
            if ($status['errcode'] > 0) {
                throw new BadRequestHttpException($status['errmsg']);
            }
            $conn->commit();
        } catch (\Exception $e) {
            if ($e->getCode() == 85009) {
                $conn->commit();
                return $result;
            } else {
                $conn->rollback();
                throw $e;
            }
        }
        return $result;
    }

    /**
     * 查看指定小程序的最后一次审核状态
     */
    public function tryRelease()
    {
        $repositories = app('registry')->getManager('default')->getRepository(Weapp::class);

        //查看小程序最后一次审核状态
        $status = $this->wxa->code->getLatestAuditstatus();
        if ($status['errcode'] > 0) {
            throw new BadRequestHttpException($status['errmsg']);
        }

        //审核失败
        if ($status['status'] == 1) {
            $repositories->processAudit($this->wxaAppId, 0, time(), $status['reason']);
            return '小程序审核失败，请查看失败原因';
        } elseif ($status['status'] == 2) { //审核中
            return '小程序正在审核';
        } else {
            $this->release();
            $repositories->processAudit($this->wxaAppId, 1, time());
            return '小程序发布成功';
        }
    }

    /**
     * 获取模板库某个模板标题下关键词库
     */
    public function wxopenTemplateKeyword($id)
    {
        $data['id'] = $id;
        return $this->wxa->template_message->get($data);
    }

    /**
     * 发布小程序
     */
    private function release()
    {
        try {
            return $this->wxa->code->release();
        } catch (\Exception $e) {
            if ($e->getCode() == 85052) {
                return true;
            }
            throw $e;
        }
    }

    /**
     * 回退版本
     */
    public function revertcoderelease()
    {
        try {
            return $this->wxa->code->rollbackRelease();
        } catch (\Exception $e) {
            if ($e->getCode() == 87011) {
                throw new BadRequestHttpException('现网已经在灰度发布，不能进行版本回退');
            }

            if ($e->getCode() == 87012) {
                throw new BadRequestHttpException('该版本不能回退');
            }

            if ($e->getCode() == -1) {
                throw new BadRequestHttpException('微信系统错误');
            }
        }
    }

    /**
     * 小程序审核撤回
     */
    public function undocodeaudit()
    {
        $repositories = app('registry')->getManager('default')->getRepository(Weapp::class);
        try {
            $weappInfo = $repositories->getWeappInfo($this->companyId, $this->wxaAppId);
            if (!$weappInfo) {
                throw new BadRequestHttpException('没有需要撤回的版本');
            }
            // 执行撤回
            $this->wxa->code->withdrawAudit();

            // 将审核状态调整到已发布，
            $repositories->undocodeaudit($this->wxaAppId);
        } catch (\Exception $e) {
            if ($e->getCode() == 87013) {
                throw new BadRequestHttpException('撤回次数达到上限（每天一次，每个月10次）');
            } elseif ($e->getCode() == -1) {
                throw new BadRequestHttpException('微信系统错误');
            } else {
                throw new BadRequestHttpException($e->getMessage());
            }
        }
    }

    /**
     * 获取体验小程序二维码
     */
    public function getTestQrcode($isDirect = 0)
    {
        if (empty($isDirect)) {
            return $this->wxa->code->getQrCode();
        } else {
            return $this->wxa->app_code->getQrCode('/pages/index');
        }
    }

    /**
     * 创建带参数的小程序码 可以跳转到指定路径并且带参数
     * 默认扫码后进入首页
     * 当前生成小程序码和小程序二维码总数为100000
     */
    public function getWxaCode($path = 'pages/index', $width = 430, $lineColor = null)
    {
        $data['width'] = $width;
        if ($lineColor) {
            $data['auto_color'] = false;
            $data['line_color'] = $lineColor;
        }
        return $this->wxa->app_code->get($path, $data);
    }

    /**
     * 获取小程序 URL Link，适用于短信、邮件、网页、微信内等拉起小程序的业务场景
     */
    public function getWxaUrlLink($path = '', $query = '', $env_version = 'release')
    {
        $data['path'] = $path;
        $data['query'] = is_array($query) ? http_build_query($query) : '';
        $data['env_version'] = $env_version ? : 'release';
        return $this->wxa->url_link->generate($data);
    }

    /**
     * 获取小程序 scheme 码，适用于短信、邮件、外部网页、微信内等拉起小程序的业务场景
     */
    public function getWxaUrlSchema($path = '', $query = '', $env_version = 'release')
    {
        $data['jump_wxa']['path'] = $path;
        $data['jump_wxa']['query'] = is_array($query) ? http_build_query($query) : '';
        $data['jump_wxa']['env_version'] = $env_version ? : 'release';
        $data['expire_type'] = 0;
        $data['expire_time'] = time() + 86400; // 一天后失效
        return $this->wxa->url_scheme->generate($data);
    }

    /**
     * 创建小程序码，无数量限制，只能跳转到首页
     * 需要在小程序首页中获取传入的scene，做对应的操作
     */
    public function createWxaCodeUnlimit($scene = '1', $page = 'pages/index', $width = 430, $lineColor = null)
    {
        $data['page'] = $page;
        $data['width'] = $width;
        $data['auto_color'] = true;
        $data['line_color'] = $lineColor;
        if ($lineColor) {
            $data['auto_color'] = false;
        }

        return $this->wxa->app_code->getUnlimit($scene, $data);
    }

    /**
     * 为授权的小程序帐号上传小程序代码
     *
     * @param string 小程序模板名称
     */
    public function commitTemplate($companyId, $templateData, $wxaName, $options = array())
    {
        // 获取到模板对应的配置
        // 包含 模板ID 版本号 需要提交的服务器地址等信息
        if (!$templateData) {
            throw new BadRequestHttpException('选择的小程序模板不存在');
        }

        $youshuSettingRepository = app('registry')->getManager('default')->getRepository(YoushuSetting::class);
        $youshuSetting = $youshuSettingRepository->getInfo(['company_id' => $companyId]);
        $youshuToken = '';
        if ($youshuSetting) {
            $youshuToken = $youshuSetting['app_id'] ? $youshuSetting['app_id'] : $youshuSetting['sandbox_app_id'];
        }

        $extJson['extAppid'] = $this->wxaAppId;
        $extJson['ext'] = [
            'appid' => $this->wxaAppId,
            'wxa_name' => $wxaName,
            'company_id' => $companyId,
            'youshutoken' => $youshuToken,
        ];

        if ($options && isset($options['tabBar']) && $options['tabBar']) {
            $extJson['tabBar'] = $options['tabBar'];
        }

        if ($options && isset($options['window']) && $options['window']) {
            $extJson['window'] = $options['window'];
        }

        $extJson['requiredPrivateInfos'] = ['getLocation', 'chooseAddress'];

        // 判断是否需要开启直播组件
        $companyInfo = (new CompanysService())->getInfo(['company_id' => $companyId]);
        $thirdParams = $companyInfo['third_params'];
        if (isset($thirdParams['is_liveroom']) && $thirdParams['is_liveroom']) {
            $extJson['plugins'] = [
                'live-player-plugin' => config('wechat.live-player-plugin')
            ];
        }

        $data = [
            'template_id' => $templateData['template_id'],
            'user_version' => $templateData['version'],
            'user_desc' => $templateData['desc'] ?? '',
            'ext_json' => json_encode($extJson),
        ];
        $result = $this->wxa->code->commit($data['template_id'], $data['ext_json'], $data['user_version'], $data['user_desc']);
        if ($result['errcode'] !== 0) {
            throw new BadRequestHttpException($result['errmsg']);
        }
        return true;
    }

    /**
     * 更新小程序修改服务器地址
     */
    public function modifyDomain(array $domain)
    {
        app('log')->info('小程序业务域名和其他的域名的配置参数：'.json_encode($domain));
        if (!empty($domain)) {
            $server_domain['action'] = 'set';
            $server_domain['requestdomain'] = $domain['requestdomain'] ?? [];
            $server_domain['wsrequestdomain'] = $domain['wsrequestdomain'] ?? [];
            $server_domain['uploaddomain'] = $domain['uploaddomain'] ?? [];
            $server_domain['downloaddomain'] = $domain['downloaddomain'] ?? [];

            $result = $this->wxa->domain->modify($server_domain);
            if ($domain['webviewdomain']) {
                $webview_domain = $domain['webviewdomain'] ?? [];

                $result = $this->wxa->domain->setwebviewDomain($webview_domain, 'set');
            }
        }
        return true;
    }
    /**
     * 更新本地域名到小程序改服务器
     */
    public function modifyDomainByLocal($templateName)
    {
        //获取本地域名信息
        $wxappTemplateService = new WxappTemplateService();
        $filter['key_name'] = $templateName;
        $templateData = $wxappTemplateService->getInfo($filter);
        if (!$templateData) {
            $templateData = config('wxa.'.$templateName);
        }
        try {
            // 根据需要上架的小程序名称修改小程序服务器地址
            $result = $this->modifyDomain($templateData['domain']);
        } catch (\Exception $e) {
            app('api.exception')->report($e);
            throw new \Exception($e->getMessage());
        }
        return $result;
    }
    /**
     * 获取小程序服务器和本地全部地址
     */
    public function getDomainListAll($templateName)
    {
        //获取小程序域名信息
        $wxresult = $this->getDomainList();
        //获取本地域名信息
        $wxappTemplateService = new WxappTemplateService();
        $filter['key_name'] = $templateName;
        $templateData = $wxappTemplateService->getInfo($filter);
        if (!$templateData) {
            $templateData = config('wxa.'.$templateName);
        }

        $result['wxDomain'] = $wxresult;
        $result['localDomain'] = $templateData['domain'];
        return $result;
    }

    /**
     * 获取小程序修改服务器地址
     */
    public function getDomainList()
    {
        $server_domain['action'] = 'get';
        $serverResult = $this->wxa->domain->modify($server_domain);

        $webviewResult = $this->wxa->domain->setWebviewDomain([], 'get');

        $domain = array_merge($serverResult, $webviewResult);

        app('log')->info('获取小程序业务域名和其他的域名的配置参数：'.json_encode($domain));
        return $domain;
    }

    /**
     * 处理小程序审核
     */
    public function processAudit($message)
    {
        $repositories = app('registry')->getManager('default')->getRepository(Weapp::class);
        //审核成功
        if ($message['Event'] == 'weapp_audit_success') {
            // 判断是否自动发布
            $WechatAuth = app('registry')->getManager('default')->getRepository(WechatAuth::class);
            $config = $WechatAuth->getWxauthDetail($this->wxaAppId);
            if ($config['auto_publish']) {
                $this->release();
                return $repositories->processAudit($this->wxaAppId, 1, $message['SuccTime']);
            } else {
                // 不是自动发布时，更新审核成功状态
                return $repositories->processAudit($this->wxaAppId, 2, $message['SuccTime']);
            }
        } else {
            return $repositories->processAudit($this->wxaAppId, 0, $message['FailTime'], $message['Reason']);
        }
    }

    public function updateWxaConfig($app_id, $params)
    {
        $WechatAuth = app('registry')->getManager('default')->getRepository(WechatAuth::class);
        return $WechatAuth->updateOneBy(['authorizer_appid' => $app_id], $params);
    }

    // --------------- 微信订阅消息 ----------------------
    // 获取小程序的分类id
    public function getWxaapiNewtmplGetcategory()
    {
        return $this->wxa->subscribe_message->getCategory();
    }

    // 获取所属类目的公共库模板标题列表
    public function getWxaapiNewtmplGetpubtemplatetitles($data)
    {
        return $this->wxa->subscribe_message->getTemplateTitles($data);
    }

    // 获取模板标题下的关键词库
    public function getWxaapiNewtmplGetpubtemplatekeywords($data)
    {
        return $this->wxa->subscribe_message->getTemplateKeywords($data);
    }

    // 获取当前帐号下的个人模板列表
    public function getWxaapiNewtmplGettemplate()
    {
        return $this->wxa->subscribe_message->getTemplates();
    }

    public function getAllWxaMsgTmpList()
    {
        $cats = $this->getWxaapiNewtmplGetcategory();
        $result = $cats->data;
        foreach ($result as &$v) {
            $v['template_titles'] = $this->getpubtemplatetitles($v['id']);
        }
        return $result;
    }

    // 获取公共模板标题
    public function getpubtemplatetitles($ids)
    {
        $limit = 30;
        $initResult = $this->getWxaapiNewtmplGetpubtemplatetitles(['ids' => $ids, 'start' => 0, 'limit' => 1]);
        $total = $initResult->count;
        $pageTotal = ceil($total / $limit);
        $return = [];
        for ($page = 0; $page < $pageTotal; $page++) {
            $data = ['ids' => $ids, 'start' => $page, 'limit' => $limit];
            $result = $this->getWxaapiNewtmplGetpubtemplatetitles($data);
            $newResult = $result->data;
            foreach ($newResult as $v) {
                $tmp = $v;
                $tmp['keywords_list'] = $this->getpubtemplatekeywords($v['tid']);
                $return[] = $tmp;
            }
        }
        return $return;
    }

    // 获取模板的关键词列表
    public function getpubtemplatekeywords($tid)
    {
        $data = ['tid' => $tid];
        $result = $this->getWxaapiNewtmplGetpubtemplatekeywords($data);
        return $result->data;
    }

    // 获取我的模板列表
    public function getTemplate()
    {
        $result = $this->getWxaapiNewtmplGettemplate();
        return $result->data;
    }

    public function onlyCommitTempCode($companyId, $authorizerAppId, $operatorId, $templateName, $wxaName, $options)
    {
        $templateData = $this->getSubmitData($companyId, $authorizerAppId, $templateName, $wxaName, $options);
        $this->commitTemplate($companyId, $templateData, $wxaName, $options);
        //保存信息到数据库
        $weappSaveData = [
            'authorizer_appid' => $this->wxaAppId,
            'operator_id' => $operatorId,
            'company_id' => $this->companyId,
            'template_id' => $templateData['template_id'],
            'template_name' => $templateName,
            'template_ver' => $templateData['version'],
            'audit_status' => 3,
        ];
        $result = app('registry')->getManager('default')->getRepository(Weapp::class)->createWeapp($this->wxaAppId, $weappSaveData);
        return $result;
    }

    public function submitReview($companyId, $authorizerAppId, $operatorId, $templateName, $wxaName, $options)
    {
        $templateData = $this->getSubmitData($companyId, $authorizerAppId, $templateName, $wxaName, $options);
        //保存信息到数据库
        $weappSaveData = [
            'authorizer_appid' => $this->wxaAppId,
            'operator_id' => $operatorId,
            'company_id' => $this->companyId,
            'template_id' => $templateData['template_id'],
            'template_name' => $templateName,
            'template_ver' => $templateData['version'],
            'audit_status' => 2,
        ];
        $category = $this->wxa->code->getCategory();
        if ($category['category_list']) {
            //可能需要遍历到最合适的服务类目，然后提交给审核
            //目前取第一个服务类目提交给审核
            $submitAuditData['item_list'] = array(
                [
                    'address' => 'pages/index',
                    'tag' => $templateData['tag'],
                    'first_class' => $category['category_list'][0]['first_class'],
                    'second_class' => $category['category_list'][0]['second_class'],
                    'first_id' => $category['category_list'][0]['first_id'],
                    'second_id' => $category['category_list'][0]['second_id'],
                    'title' => '首页',
                ],
            );
        } else {
            throw new BadRequestHttpException('请在微信小程序中设置服务类目');
        }

        // 订单中心path
        $submitAuditData['order_path'] = '/subpage/pages/trade/list';

        $result = app('registry')->getManager('default')->getRepository(Weapp::class)->createWeapp($this->wxaAppId, $weappSaveData);

        try {
            $this->wxa->code->submitAudit($submitAuditData);
        } catch (\Exception $e) {
            if ($e->getCode() == 85009) {
                return $result;
            } else {
                throw $e;
            }
        }
        return $result;
    }

    private function getSubmitData($companyId, $authorizerAppId, $templateName, $wxaName, &$options)
    {
        $templateService = new TemplateService();
        $templateService->submitAuditCheck($companyId, $authorizerAppId, $this->wxaAppId, $templateName, $wxaName);

        $wxappTemplateService = new WxappTemplateService();
        $filter['key_name'] = $templateName;
        $templateData = $wxappTemplateService->getInfo($filter);
        if (!$templateData) {
            $templateData = config('wxa.'.$templateName);
        }

        // 临时写死，后期可做为前端配置
        if (in_array($templateName, ['yykweishop'])) {
            $options['window'] = [
                'backgroundTextStyle' => 'light',
                'navigationBarBackgroundColor' => '#fff',
                'navigationBarTitleText' => $wxaName,
                'navigationBarTextStyle' => "black",
            ];
        }

        try {
            // 根据需要上架的小程序名称修改小程序服务器地址
            $this->modifyDomain($templateData['domain']);
        } catch (\Exception $e) {
            app('api.exception')->report($e);
            throw new \Exception($e->getMessage());
        }
        return $templateData;
    }

    public function getShareId($companyId, $params, $ttl = 2592000)
    {
        $shareId = uniqid();
        $redisKey = $companyId.'_'.$shareId;
        $expireAt = time() + $ttl;
        app('redis')->set($redisKey, json_encode($params));
        app('redis')->expireat($redisKey, $expireAt);
        return $shareId;
    }

    public function getByShareId($companyId, $shareId)
    {
        $redisKey = $companyId.'_'.$shareId;
        $params = app('redis')->get($redisKey);
        if ($params) {//salesperson_id
            $salesperson = json_decode($params, true);
            //{"id":"7","smid":"2","gu":"15026787264_A06"}
            // 解析gu后半为shop_code
            if ($salesperson && isset($salesperson['gu'])) {
                $workUserid = explode('_', $salesperson['gu'])[0];
                $salespersonService = new \ThirdPartyBundle\Services\MarketingCenter\SalespersonService();
                $salespersonInfo = $salespersonService->getSalespersonAndDistributorByWorkUserid($companyId, $workUserid);
                if ($salespersonInfo) {
                    app('log')->info('[getByShareId] salespersonInfo===>'. json_encode($salespersonInfo));
                    $salesperson['distributorInfo'] = $salespersonInfo['distributorInfo'] ?? [];
                    $salesperson['distributor_id'] = $salespersonInfo['distributorInfo']['distributor_id'] ?? '';
                    $salesperson['dtid'] = $salespersonInfo['distributorInfo']['distributor_id'] ?? '';
                    $salesperson['name'] = $salespersonInfo['distributorInfo']['name'] ?? '';
                    $salesperson['salespersonInfo'] = $salespersonInfo;

                }
                // $shopCode = explode('_', $salesperson['gu'])[1];
                // //通过shop_code获取门店信息
                // $distributorService = new DistributorService();
                // $filter = [
                //     'company_id' => $companyId,
                //     'shop_code' => $shopCode,
                // ];
                // $distributorInfo = $distributorService->getInfo($filter);
                // if ($distributorInfo) {
                //     $salesperson['distributor_id'] = $distributorInfo['distributor_id'] ?? '';
                //     $salesperson['name'] = $distributorInfo['name'] ?? '';
                //     $salesperson['shop_code'] = $distributorInfo['shop_code'] ?? '';
                // }
                return $salesperson;
            }else {
                return $salesperson;
            }
        }

        return [];
    }

    /**
     * 查询小程序用户隐私保护指引
     */
    public function getPrivacySetting()
    {
        $result = $this->wxa->privacy->get();
        $store_expire_timestamp = $result['owner_setting']['store_expire_timestamp'] ?? 0;
        if (intval($store_expire_timestamp) > 0) {
            $result['owner_setting']['store_expire_timestamp'] = date('Y-m-d', $store_expire_timestamp);
        }
        return $result;
    }

    /**
     * 设置小程序用户隐私保护指引
     */
    public function setPrivacySetting($data)
    {
        return $this->wxa->privacy->set($data);
    }

    /**
     * 设置小程序用户隐私保护指引
     */
    public function uploadPrivacyExtFile($path)
    {
        return $this->wxa->privacy->upload($path);
    }

    /**
     * 获取隐私接口列表
     */
    public function getSecurity()
    {
        return $this->wxa->security->get();
    }

    /**
     * 申请隐私接口
     * https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/2.0/api/apply_api/apply_privacy_interface.html
     */
    public function applySecurity($params)
    {
        return $this->wxa->security->set($params);
    }

    /**
     * 创建公众号二位码，无数量限制，只能跳转到首页
     * 需要在小程序首页中获取传入的scene，做对应的操作
     */
    public function createOffiaccountCodeForever($sceneValue=1, $isBase64 = false)
    {
        if (is_int($sceneValue) && $sceneValue > 0 && $sceneValue < 100000) { // 永久二维码只支持 1-100000
            $scene = $sceneValue; // 整型 ，1-100000
        } else {
            $scene = $sceneValue; // 字符串型
        }

        $ticketInfo = $this->wxa->qrcode->forever($scene);
        $url = $this->wxa->qrcode->url($ticketInfo['ticket']);
        $result['url'] = $url;
        if ($isBase64) {
            $codeStream = file_get_contents($url);
            $result['base64Image'] = 'data:image/jpg;base64,' . base64_encode($codeStream);
        }
        return $result;
    }

    // 申请设置订单页 path 信息
    public function applySetOrderPathInfo($appidList) {
        return app('easywechat.open_platform')->httpPostJson('/wxa/security/applysetorderpathinfo', [
            'batch_req' => [
                'path' => '/subpage/pages/trade/list',
                'appid_list' => $appidList,
            ]
        ]);
    }

    // 获取订单页 path 信息
    public function getOrderPathInfo() {
        return $this->wxa->security->httpPostJson('/wxa/security/getorderpathinfo', ['info_type' => 1]);
    }
}
