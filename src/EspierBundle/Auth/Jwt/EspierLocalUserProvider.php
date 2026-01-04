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

namespace EspierBundle\Auth\Jwt;

use AliBundle\Factory\MiniAppFactory;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use MembersBundle\Repositories\MembersInfoRepository;
use MembersBundle\Repositories\MembersRepository;
use MembersBundle\Services\MemberService;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

use CompanysBundle\Ego\GenericUser as GenericUser;
use CompanysBundle\Services\CompanysService;

use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersAssociations;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Services\UserService;
use MembersBundle\Services\WechatUserService;
use MembersBundle\Services\MemberRegSettingService;
use WechatBundle\Services\OfficialAccountService;
use WechatBundle\Services\OpenPlatform;

use Overtrue\Socialite\SocialiteManager;
use CompanysBundle\Services\Shops\ProtocolService;
use MembersBundle\Services\MembersProtocolLogService;
use EspierBundle\Services\LoginService;

class EspierLocalUserProvider implements UserProvider
{
    protected $openPlatform;

    /**
     * 会员服务
     * @var MemberService
     */
    public $memberService;

    /**
     * undocumented function
     *
     * @return void
     */
    public function __construct($app, $config)
    {
        $this->openPlatform = new OpenPlatform();
        $this->memberService = new MemberService();
    }

    /**
     * 中间件token认证。通过用户的唯一标识符检索用户
     *
     * @param  mixed  $identifier 用户唯一标识id
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $user = $this->getUserLoginInfo($identifier);

        return $this->getGenericUser($user);
    }

    /**
     * 登陆。通过给定凭据检索用户
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $this->getCompanyId($credentials);

        $authType = $credentials['auth_type'] ?? 'local';

        switch ($authType) {
            // 用户名密码登陆
            case "local":
                if (!config('common.system_is_saas')) {
                    $companyId = config('common.system_companys_id');
                } elseif (config('common.system_main_companys_id')) {
                    $companyId = config('common.system_main_companys_id');
                } else {
                    $companyId = $credentials['company_id'];
                }
                $mobile = $credentials['username'];
                $password = isset($credentials['password']) ? $credentials['password'] : '';
                $check_type = (isset($credentials['check_type']) && $credentials['check_type']) ? $credentials['check_type'] : 'password';
                $vcode = isset($credentials['vcode']) ? $credentials['vcode'] : '';

                $autoRegister = (bool)($credentials["auto_register"] ?? false);
                $silent = (bool)($credentials["silent"] ?? false);
                // 验证密码，返回会员部分信息
                $user = $this->checkUser($companyId, $mobile, $password, $check_type, $vcode, $autoRegister, $silent);
                break;
            // 小程序授权登陆方式
            case "wxapp":
                $user = $this->preLogin($credentials);
                break;
            // 小程序授权登陆方式 (微信，pc授权登录)
            case "oauth":
                $user = $this->preOauthLogin($credentials);
                break;
            // 微信内服务号网页授权登陆方式
            case "wx_offiaccount":
                $user = $this->preOffiaccountLogin($credentials);
                break;
            // pc微信扫码登录
            case "pc_wxqrcode":
                $user = $this->prewxQrcodeLogin($credentials);
                break;
            case "aliapp":
                $user = $this->preAliMiniAppLogin($credentials);
                break;
            default:
                $user = [];
        }

        if ($user && $user['user_id'] > 0) {
            // 登录成功代表用户接收隐私隐私协议
            $protocols = (new ProtocolService($user['company_id']))->get([ProtocolService::TYPE_MEMBER_REGISTER, ProtocolService::TYPE_PRIVACY]);
            $membersProtocolLogService = new MembersProtocolLogService();
            foreach ($protocols as $protocol) {
                if (!isset($protocol['digest'])) {
                    continue;
                }
                $acceptLog = [
                    'company_id' => $user['company_id'],
                    'user_id' => $user['user_id'],
                    'digest' => $protocol['digest'],
                ];
                $membersProtocolLogService->create($acceptLog);
            }
        }

        return $this->getGenericUser($user);
    }

    /**
     * 根据 origin 识别 company_id .
     *
     * @param  array  $credentials
     * @return boolean
     */
    private function getCompanyId(&$credentials)
    {
        // TS: 53686f704578
        if (isset($credentials['company_id']) && $credentials['company_id']) {
            return true;
        }

        if (!isset($credentials['origin']) or !$credentials['origin']) {
            return false;
        }

        $originDomain = str_replace(['http://', 'https://'], '', $credentials['origin']);
        $companyService = new CompanysService();
        $companyInfo = $companyService->getCompanyInfoByDomain($originDomain);
        if ($companyInfo) {
            $credentials['company_id'] = $companyInfo['company_id'] ?? '';
        }

        return true;
    }

    /**
     * Get the generic user.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\GenericUser|null
     */
    protected function getGenericUser($user)
    {
        if (!empty($user)) {
            return new GenericUser($user);
        }
        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        return true;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
    }

    private function preLogin($inputData)
    {
        // 数云模式，使用数云的预登陆方式
        if (config('common.oem-shuyun')) {
            return $this->preLoginShuyun($inputData);
        }

        if (empty($inputData['appid'])) {
            throw new ResourceException('缺少小程序ID');
        }

        // 判断inputData里是否给了openid，如果给了，则外部已经根据微信code获取到了openid,防止两次传入code，解析失败
        if (isset($inputData['openid']) && $inputData['openid']) {
            $res = [
                'openid' => $inputData['openid'],
                'unionid' => $inputData['unionid'] ?? '',
            ];
        } else {
            //调用微信获取sessionkey接口，返回session_key,openid,unionid
            $app = $this->openPlatform->getAuthorizerApplication($inputData['appid']);
            $res = $app->auth->session($inputData['code']);
        }

        if (!isset($res['openid']) || !$res['openid']) {
            throw new ResourceException('小程序登录获取信息失败，请重试！');
        }
        // 如果小程序没有绑定过开放平台，则将unionid的值改成openid同样的值
        if (!isset($res['unionid']) || !$res['unionid']) {
            $res['unionid'] = $res['openid'];
        }

        $userService = new UserService(new WechatUserService());
        $companyId = $inputData['company_id'] ? $inputData['company_id'] : $this->openPlatform->getCompanyId($inputData['appid']);
        // $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);

        $wechatuser = $userService->getSimpleUser(['open_id' => $res['openid'], 'authorizer_appid' => $inputData['appid'], 'company_id' => $companyId]);
        if (!$wechatuser) {
            throw new ResourceException('请您检查是否已经授权！');
        }

        $user = $userService->getUserInfo(['open_id' => $res['openid'], 'unionid' => $res['unionid'], 'company_id' => $companyId]);
        if (!$user) {
            throw new ResourceException('登录出错，请联系服务商！');
        }

        if (!isset($user['user_id']) || (isset($user['user_id']) && !$user['user_id'])) {
            throw new ResourceException('请注册！');
        }

        // 迁移模式，让跳转到授权用户信息页
        if (config('common.transfer_mode') && ($wechatuser['need_transfer'] == 1)) {
            throw new ResourceException('请您重新授权用户信息！');
        }

        // $memberInfo = $this->getMemberInfo(['user_id' => $user['user_id'], 'company_id' => $companyId]);

        $result = [
            'id' => $user['user_id']."_espier_".$user['open_id']."_espier_".$user['unionid'],
            'user_id' => $user['user_id'],
            // 'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $companyId,
            // 'wxapp_appid' => $user['authorizer_appid'],
            // 'woa_appid' => $woaAppid,
            'unionid' => $user['unionid'],
            'openid' => $res['openid'],
            // 'nickname' => $user['nickname'] ?? '',
            // 'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
            // 'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
            // 'sex' => isset($memberInfo['sex']) ? $memberInfo['sex'] : ($user['sex'] ?? 0),
            // 'user_card_code' => $memberInfo['user_card_code'] ?? '',
            'operator_type' => 'user',
        ];
        return $result;
    }

    // 小程序预登陆，如果登陆成功则成功，否则是需要注册
    private function preLoginShuyun($inputData)
    {
        app('log')->info('file:'.__FILE__.',line:'.__LINE__.',inputData====>'.var_export($inputData, true));
        if (empty($inputData['appid'])) {
            throw new ResourceException('缺少小程序ID');
        }

        // 判断inputData里是否给了openid，如果给了，则外部已经根据微信code获取到了openid,防止两次传入code，解析失败
        if (isset($inputData['openid']) && $inputData['openid']) {
            $res = [
                'openid' => $inputData['openid'],
                'unionid' => $inputData['unionid'] ?? '',
            ];

        } else {
            //调用微信获取sessionkey接口，返回session_key,openid,unionid
            $app = $this->openPlatform->getAuthorizerApplication($inputData['appid']);
            $res = $app->auth->session($inputData['code']);
        }
        app('log')->info('file:'.__FILE__.',line:'.__LINE__.',res====>'.var_export($res, true));
        if (!isset($res['openid']) || !$res['openid']) {
            throw new ResourceException('小程序登陆获取信息失败，请重试！');
        }
        // 如果小程序没有绑定过开放平台，则将unionid的值改成openid同样的值
        if (!isset($res['unionid']) || !$res['unionid']) {
            $res['unionid'] = $res['openid'];
        }

        $userService = new UserService(new WechatUserService());
        $companyId = $inputData['company_id'] ? $inputData['company_id'] : $this->openPlatform->getCompanyId($inputData['appid']);
        // $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);

        $wechatuser = $userService->getSimpleUser(['open_id' => $res['openid'], 'authorizer_appid' => $inputData['appid'], 'company_id' => $companyId]);
        app('log')->info('file:'.__FILE__.',line:'.__LINE__.',wechatuser====>'.var_export($wechatuser, true));
        if (!$wechatuser) {
            app('log')->info('file:'.__FILE__.',line:'.__LINE__.',请您检查是否已经授权！');
            $loginService = new LoginService();
            $params = [
                'unionid' => $res['unionid'],
                'open_id' => $res['openid'],
                'company_id' => $companyId,
            ];
            $user = $loginService->shuyunMemberSilent($inputData, $params);
            if (!$user) {
                app('log')->info('file:'.__FILE__.',line:'.__LINE__.',静默授权失败，请您检查是否已经授权！');
                throw new ResourceException('请您检查是否已经授权！');
            }
            
        }

        $user = $userService->getUserInfo(['open_id' => $res['openid'], 'unionid' => $res['unionid'], 'company_id' => $companyId]);
        if (!$user && $wechatuser) {
            $filter = [
                'company_id' => $wechatuser['company_id'],
                'authorizer_appid' => $wechatuser['authorizer_appid'],
                'open_id' => $wechatuser['open_id'],
            ];
            $userService->updateUnionId($filter, $wechatuser['unionid'], $res['unionid']);
            $user = $userService->getUserInfo(['open_id' => $res['openid'], 'unionid' => $res['unionid'], 'company_id' => $companyId]);
        }

        app('log')->info('file:'.__FILE__.',line:'.__LINE__.',user====>'.var_export($user, true));
        if (!$user) {
            app('log')->info('file:'.__FILE__.',line:'.__LINE__.',登录出错，请联系服务商！');
            $loginService = new LoginService();
            $params = [
                'unionid' => $res['unionid'],
                'open_id' => $res['openid'],
                'company_id' => $companyId,
            ];
            $user = $loginService->shuyunMemberSilent($inputData, $params);
            if (!$user) {
                app('log')->info('file:'.__FILE__.',line:'.__LINE__.',静默授权失败，登录出错，请联系服务商！');
                throw new ResourceException('登录出错，请联系服务商！');
            }
        }
        
        if (!isset($user['user_id']) || (isset($user['user_id']) && !$user['user_id'])) {
            app('log')->info('file:'.__FILE__.',line:'.__LINE__.',请注册！');
            $loginService = new LoginService();
            $params = [
                'unionid' => $res['unionid'],
                'open_id' => $res['openid'],
                'company_id' => $companyId,
            ];
            $user = $loginService->shuyunMemberSilent($inputData, $params);
            if (!$user) {
                app('log')->info('file:'.__FILE__.',line:'.__LINE__.',静默授权失败，请注册！');
                throw new ResourceException('请注册！');
            }
            
        }

        // 迁移模式，让跳转到授权用户信息页
        if (config('common.transfer_mode') && ($wechatuser['need_transfer'] == 1)) {
            throw new ResourceException('请您重新授权用户信息！');
        }

        // $memberInfo = $this->getMemberInfo(['user_id' => $user['user_id'], 'company_id' => $companyId]);

        $result = [
            'id' => $user['user_id']."_espier_".$user['open_id']."_espier_".$user['unionid'],
            'user_id' => $user['user_id'],
            // 'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $companyId,
            // 'wxapp_appid' => $user['authorizer_appid'],
            // 'woa_appid' => $woaAppid,
            'unionid' => $user['unionid'],
            'openid' => $res['openid'],
            // 'nickname' => $user['nickname'] ?? '',
            // 'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
            // 'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
            // 'sex' => isset($memberInfo['sex']) ? $memberInfo['sex'] : ($user['sex'] ?? 0),
            // 'user_card_code' => $memberInfo['user_card_code'] ?? '',
            'operator_type' => 'user',
        ];
        return $result;
    }

    // 微信，pc授权登录
    private function preOauthLogin($inputData)
    {
        if (empty($inputData['appid'])) {
            throw new ResourceException('缺少参数，获取用户信息失败！');
        }
        if (!isset($inputData['openid'])) {
            throw new ResourceException('小程序信息错误，请联系服务商！');
        }
        $userService = new UserService(new WechatUserService());
        $wechatuser = $userService->getSimpleUser(['open_id' => $inputData['openid'], 'authorizer_appid' => $inputData['appid']]);
        $companyId = $this->openPlatform->getCompanyId($inputData['appid']);
        $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);

        if (isset($wechatuser['unionid']) && $wechatuser['unionid']) {
            $user = $userService->getUserInfo(['open_id' => $inputData['openid'], 'unionid' => $wechatuser['unionid']]);
        } else {
            throw new ResourceException('请授权！');
        }

        $redis = app('redis')->connection('members');
        $key = 'member:oauth:login:' . $inputData['token'];
        $info = json_decode($redis->get($key), true);
        if (!$info) {
            throw new ResourceException('登录失败');
        }
        if ($info['union_id'] != $wechatuser['unionid']) {
            throw new ResourceException('用户错误');
        }

        // $companyId = $this->openPlatform->getCompanyId($user['authorizer_appid']);
        // $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);

        $memberInfo = [];
        if (!isset($user['user_id']) || (isset($user['user_id']) && !$user['user_id'])) {
            throw new ResourceException('请注册！');
        }
        $memberInfo = $this->getMemberInfo(['user_id' => $user['user_id'], 'company_id' => $companyId]);

        return [
            'id' => $user['user_id']."_espier_".$user['open_id']."_espier_".$user['unionid'],
            'user_id' => $user['user_id'],
            'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $companyId,
            'wxapp_appid' => $user['authorizer_appid'],
            'woa_appid' => $woaAppid,
            'unionid' => $user['unionid'],
            'openid' => $inputData['openid'],
            'nickname' => $user['nickname'] ?? '',
            'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
            'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
            'sex' => isset($memberInfo['sex']) ? $memberInfo['sex'] : $user['sex'],
            'user_card_code' => $memberInfo['user_card_code'] ?? '',
            'offline_card_code' => $memberInfo['offline_card_code'] ?? '',
            'operator_type' => 'user',
        ];
    }
    public function prewxQrcodeLogin($inputData)
    {
        if (empty($inputData['company_id'])) {
            throw new ResourceException('缺少company_id参数，获取用户信息失败！');
        }
        if (empty($inputData['appid'])) {
            throw new ResourceException('缺少appid参数，获取用户信息失败！');
        }
        $config = [
            'wechat' => [
                'client_id' => $inputData['appid'],
                'client_secret' => $inputData['secret'],
                'redirect' => $inputData['url']
            ],
        ];

        $socialite = new SocialiteManager($config);
        // $oauth = $socialite->driver('wechat')->getAccessToken($inputData['code']);
        $oauth = $socialite->driver('wechat')->user();
        $res = $oauth->getOriginal();
        if (!isset($res['openid'])) {
            throw new ResourceException('公众号授权信息错误，请联系服务商！');
        }
        // 记录千人千码参数
        $res['source_id'] = isset($inputData['source_id']) ? trim($inputData['source_id']) : 0;
        $res['monitor_id'] = isset($inputData['monitor_id']) ? trim($inputData['monitor_id']) : 0;
        $res['inviter_id'] = isset($inputData['inviter_id']) ? trim($inputData['inviter_id']) : 0;
        $res['source_from'] = isset($inputData['source_from']) ? trim($inputData['source_from']) : 'default';

        $userService = new UserService(new WechatUserService());
        // $companyId = $this->openPlatform->getCompanyId($inputData['appid']);
        // $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);
        $companyId = $inputData['company_id'];
        $woaAppid = $inputData['appid'];

        if (!$userInfo = $this->createFans($companyId, $inputData['appid'], $res)) {
            throw new ResourceException('Invalid store wxapp user.');
        }
        $wechatuser = $userService->getSimpleUser(['open_id' => $res['openid'], 'authorizer_appid' => $inputData['appid'], 'company_id' => $companyId]);
        if (!$wechatuser) {
            throw new ResourceException('请您检查是否已经授权！');
        }

        $user = $userService->getUserInfo(['open_id' => $res['openid'], 'unionid' => $wechatuser['unionid'], 'company_id' => $companyId]);
        if (!$user) {
            throw new ResourceException('登录出错，请联系服务商！');
        }

        if (isset($user['user_id']) && $user['user_id']) {
            $memberInfo = $this->getMemberInfo(['user_id' => $user['user_id'], 'company_id' => $companyId]);
        } else {
            $user['user_id'] = 0;
        }

        $result = [
            'id' => $user['user_id']."_espier_".$user['open_id']."_espier_".$user['unionid'],
            'user_id' => $user['user_id'] ?? 0,
            'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $companyId,
            'wxapp_appid' => $user['authorizer_appid'],
            'woa_appid' => $woaAppid,
            'unionid' => $user['unionid'],
            'openid' => $res['openid'],
            'nickname' => $user['nickname'] ?? '',
            'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
            'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
            'sex' => isset($memberInfo['sex']) ? $memberInfo['sex'] : ($user['sex'] ?? 0),
            'user_card_code' => $memberInfo['user_card_code'] ?? '',
            'offline_card_code' => $memberInfo['offline_card_code'] ?? '',
            'operator_type' => 'user',
        ];
        return $result;
    }

    // 微信内服务号网页授权登录
    private function preOffiaccountLogin($inputData)
    {
        /** 根据code去获取用户信息 **/

        if (empty($inputData["company_id"])) {
            throw new ResourceException("缺少参数！");
        }
        if (empty($inputData["code"])) {
            throw new ResourceException("code error");
        }

        // 获取微信公众号的对象
        $app = (new OpenPlatform())->getWoaApp([
            "company_id" => $inputData["company_id"],
            "trustlogin_tag" => $inputData["trustlogin_tag"] ?? "weixin", // weixin
            "version_tag" => $inputData["version_tag"] ?? "touch" // touch
        ]);

        // 获取公众号的appid
        $woaAppid = $app->oauth->getClientId();

        // 根据code获取微信里的用户信息
        $res = (new OfficialAccountService($app))->getUserInfoByCode($inputData["code"]);

        if (!isset($res['openid']) || !$res['openid']) {
            throw new ResourceException('小程序登陆获取信息失败，请重试！');
        }
        // 如果小程序没有绑定过开放平台，则将unionid的值改成openid同样的值
        if (!isset($res['unionid']) || !$res['unionid']) {
            $res['unionid'] = $res['openid'];
        }

        /** 将微信的用户信息存入表中 **/

        // 头像
        $res["headimgurl"] = $res["avatar"] ?? "";
        // 记录千人千码参数
        $res['source_id'] = isset($inputData['source_id']) ? trim($inputData['source_id']) : 0;
        $res['monitor_id'] = isset($inputData['monitor_id']) ? trim($inputData['monitor_id']) : 0;
        $res['inviter_id'] = isset($inputData['inviter_id']) ? trim($inputData['inviter_id']) : 0;
        $res['source_from'] = isset($inputData['source_from']) ? trim($inputData['source_from']) : 'default';
        // 创建微信用户信息
        $info = $this->createFans($inputData["company_id"], $woaAppid, $res);
        if (!$info) {
            throw new ResourceException('Invalid store wxapp user.');
        }

        // 微信用户的基本信息
        $wechatUser = $info["wechatuser"] ?? [];

        // 整理token内的数据
        $result = [
            'id' => 0 . "_espier_" . $wechatUser['open_id'] . "_espier_" . $wechatUser['unionid'],
            'user_id' => 0,
            'disabled' => 0,
            'company_id' => $inputData["company_id"],
            'wxapp_appid' => $wechatUser['authorizer_appid'],
            'woa_appid' => $woaAppid,
            'unionid' => $wechatUser['unionid'],
            'openid' => $wechatUser['open_id'],
            'nickname' => $res['nickname'] ?? '',
            'mobile' => '',
            'username' => $res['username'] ?? '',
            'sex' => $res['sex'] ?? 0,
            'user_card_code' => '',
            'offline_card_code' => '',
            'operator_type' => 'user',
            "is_new" => (int)($info["is_new"] ?? 1),
        ];
        // 如果是老用户，则获取用户信息
        if (!$result["is_new"]) {
            $user = $info["memberInfo"] ?? [];
            $result = array_merge($result, [
                'id' => $user['user_id'] . "_espier_" . $wechatUser['open_id'] . "_espier_" . $wechatUser['unionid'],
                'user_id' => $user['user_id'] ?? 0,
                'disabled' => $user['disabled'] ?? 0,
                'mobile' => $user['mobile'] ?? '',
                'user_card_code' => $user['user_card_code'] ?? '',
                'offline_card_code' => $user['offline_card_code'] ?? '',
            ]);
        }

        return $result;
    }

    private function preAliMiniAppLogin($inputData)
    {
        if (empty($inputData['company_id'])) {
            throw new ResourceException('缺少参数！');
        }

        if (isset($inputData['alipay_user_id']) && $inputData['alipay_user_id']) {
            $alipayUserId = $inputData['alipay_user_id'];
        } else {
            if (empty($inputData['code'])) {
                throw new ResourceException('缺少参数！');
            }
            $app = (new MiniAppFactory())->getApp($inputData['company_id']);
            $oauthData = $app->getFactory()->base()->oauth()->getToken($inputData['code'])->toMap();
            if (!isset($oauthData['user_id'])) {
                throw new ResourceException('小程序授权信息错误，请联系服务商！');
            }
            $alipayUserId = $oauthData['user_id'];
        }
        $companyId = $inputData['company_id'];

        $membersAssoRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $alipayuser = $membersAssoRepository->get(['company_id' => $companyId, 'user_type' => 'ali', 'unionid' => $alipayUserId]);
        if (!$alipayuser) {
            throw new ResourceException('请您检查是否已经授权！');
        }

        $memberRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $memberInfo = $memberRepository->get(['user_id' => $alipayuser['user_id'], 'company_id' => $companyId]);
        if (empty($memberInfo['user_id'])) {
            throw new ResourceException('请注册！');
        }

        $result = [
            'id' => $memberInfo['user_id'] . "_espier_alipay" . "_espier_" . $alipayuser['unionid'],
            'user_id' => $memberInfo['user_id'],
            'company_id' => $companyId,
            'alipay_appid' => $memberInfo['alipay_appid'],
            'alipay_user_id' => $alipayuser['unionid'],
            'operator_type' => 'user',
        ];
        return $result;
    }

    // 创建粉丝，已有则直接返回粉丝信息
    private function createFans($companyId, $appId, $userInfo)
    {
        $params = ['open_id' => $userInfo['openid'], 'unionid' => $userInfo['unionid']];
        if (!$params['open_id'] || !$params['unionid']) {
            throw new ResourceException('用户登录失败！');
        }

        $params['company_id'] = $companyId;
        $params['headimgurl'] = $userInfo['headimgurl'];
        $params['country'] = $userInfo['country'];
        $params['province'] = $userInfo['province'];
        $params['city'] = $userInfo['city'];
        $params['sex'] = $userInfo['sex'];
        $params['language'] = $userInfo['language'];
        $params['nickname'] = $userInfo['nickname'];

        $params['inviter_id'] = $userInfo['inviter_id'];
        $params['source_from'] = $userInfo['source_from'];
        $params['source_id'] = $userInfo['source_id'];
        $params['monitor_id'] = $userInfo['monitor_id'];

        $userService = new UserService(new WechatUserService());
        return $userService->createWxappFans($appId, $params);
    }

    /**
     * 认证获取用户
     * @$identifier  user_id."_espier_".open_id."_espier_".unionid
     */
    private function getUserLoginInfo($identifier)
    {
        if (!strpos($identifier, '_espier_')) {
            throw new UnauthorizedHttpException('', '获取用户信息出错');
        }
        list($userId, $openid, $unionid) = explode('_espier_', $identifier);

        $companyId = 0;
        if ($openid && $unionid && $openid != 'companyid') {
            if ($openid == 'alipay') {
                $membersAssoRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
                $alipayuser = $membersAssoRepository->get(['user_id' => $userId, 'user_type' => 'ali', 'unionid' => $unionid]);
                if ($alipayuser) {
                    $companyId = $alipayuser['company_id'];
                    $alipayUserId = $alipayuser['unionid'];
                }
            } else {
                $userService = new UserService(new WechatUserService());
                $user = $userService->getUserInfo(['unionid' => $unionid, 'open_id' => $openid]);
                $openPlatform = new OpenPlatform();
                if (isset($user['authorizer_appid']) && $user['authorizer_appid']) {
                    $companyId = $openPlatform->getCompanyId($user['authorizer_appid']);
                    $woaAppid = $openPlatform->getWoaAppidByCompanyId($companyId);
                }
            }
        } else {
            $companyId = $unionid;
        }

        if (!$companyId) {
            throw new UnauthorizedHttpException('', '获取用户信息出错');
        }

        $memberInfo = $this->getMemberInfo(['user_id' => $userId, 'company_id' => $companyId]);
        if (!$memberInfo) {
            throw new UnauthorizedHttpException('', '获取用户信息出错');
        }

        $result = [
            'id' => $memberInfo['user_id'],
            'user_id' => $memberInfo['user_id'],
            'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $memberInfo['company_id'],
            'wxapp_appid' => $user['authorizer_appid'] ?? '',
            'woa_appid' => $woaAppid ?? '',
            'open_id' => $user['open_id'] ?? '',
            'unionid' => $user['unionid'] ?? '',
            'nickname' => $user['nickname'] ?? '',
            'headimgurl' => $user['headimgurl'] ?? '',
            'grade_id' => $memberInfo['grade_id'],
            'mobile' => $memberInfo['mobile'],
            'username' => $memberInfo['username'] ?? '',
            'user_card_code' => $memberInfo['user_card_code'],
            'offline_card_code' => $memberInfo['offline_card_code'],
            'operator_type' => 'user',
            'inviter_id' => $memberInfo['inviter_id'],
            'source_id' => $memberInfo['source_id'],
            'monitor_id' => $memberInfo['monitor_id'],
            'latest_source_id' => $memberInfo['latest_source_id'],
            'latest_monitor_id' => $memberInfo['latest_monitor_id'],
            'chief_id' => 0,
            'alipay_appid' => $memberInfo['alipay_appid'] ?? '',
            'alipay_user_id' => $alipayUserId ?? '',
        ];
        if ((config('common.product_model') != 'in_purchase') && !empty($result['user_id'])) {
            $chief = (new \CommunityBundle\Services\CommunityChiefService())->getChiefInfoByUserID($result['user_id']);
            $result['chief_id'] = $chief['chief_id'] ?? 0;
        }
        return $result;
    }

    /**
     * 获取会员信息
     * @param  array $filter 查询条件
     * @return array         会员数据
     */
    private function getMemberInfo($filter)
    {
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $member = $membersRepository->get($filter);
        $result = $member;
        if ($member && isset($member['user_id']) && $member['user_id']) {
            $memberFilter = [
                'company_id' => $member['company_id'],
                'user_id' => $member['user_id']
            ];
            $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
            $info = $membersInfoRepository->getInfo($memberFilter);
            // 如果存在用户相信信息
            if (!empty($info)) {
                $info["other_params"] = (array)jsonDecode($info["other_params"] ?? null);
                // 前端透传的参数
                $info["isGetWxInfo"] = (bool)($info["other_params"]["isGetWxInfo"] ?? false);
            }
            $result = array_merge($member, $info);
        }
        return $result;
    }

    /**
     * 验证用户名密码
     * @param int $company_id 公司的企业id
     * @param string $mobile 手机号
     * @param string $password 密码
     * @param string $check_type 短信验证码的验证码类型
     * @param string $vcode 短信验证码
     * @param bool $autoRegister 是否自动注册【true 自动注册】【false 不自动注册并抛出异常】
     * @return array
     * @throws \Exception
     */
    private function checkUser($company_id, $mobile, $password, $check_type = 'password', $vcode = '', bool $autoRegister = false, bool $silent = false)
    {
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $userEntity = $membersRepository->findOneBy(['company_id' => $company_id, 'mobile' => fixedencrypt($mobile)]);

        // 表单验证最优先
        switch ($check_type) {
            case "mobile":
                if (!(new MemberRegSettingService())->checkSmsVcode($mobile, $company_id, $vcode, 'login')) {
                    throw new ResourceException('短信验证码错误');
                }
                break;
            case "password":
                if ($userEntity && !$this->checkPassword($password, $userEntity->getPassword())) {
                    throw new ResourceException('用户名或密码错误');
                }
                break;
            default:
                throw new ResourceException("验证类型有误！");
        }

        // 判断手机是否存在
        if (empty($userEntity)) {
            // 如果是自动创建，则直接创建用户
            if ($autoRegister) {
                $userInfo = (new MemberService())->createMember([
                    "mobile" => $mobile,
                    "region_mobile" => $mobile,
                    "mobile_country_code" => "86",
                    "company_id" => $company_id,
                    "wxa_appid" => "", // 小程序appid
                    "authorizer_appid" => "", // 公众号id
                    "sex" => 0,
                    "username" => randValue(8),
                    "avatar" => "",
                    "email" => "",
                    "password" => $password,
                    "api_from" => "h5app",
                    "auth_type" => "local",
                    "user_type" => "local",
                    "unionid" => "",
                    "open_id" => "",
                    "force_password" => 1
                ]);
            } else {
                if (!$silent) {
                    throw new ResourceException('手机号码未注册，请注册后登陆');
                }
                $userInfo = [
                    "user_id" => null,
                    "company_id" => $company_id,
                    "grade_id" => null,
                    "mobile" => null,
                    "user_card_code" => null,
                    "offline_card_code" => null,
                    "disabled" => null,
                    "inviter_id" => null,
                    "source_id" => null,
                    "monitor_id" => null,
                    "latest_source_id" => null,
                    "latest_monitor_id" => null,
                ];
            }
            $userInfo["is_new"] = 1;
        } else {
            $userInfo = $this->memberService->membersRepository->getDataByEntity($userEntity);
            $userInfo["is_new"] = 0;
        }

        // 生成token中的数据
        return $this->memberService->getTokenData($userInfo);
    }

    private function checkPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
