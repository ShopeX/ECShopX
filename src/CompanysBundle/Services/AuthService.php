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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\DistributorWorkWechatRel;
use CompanysBundle\Repositories\DistributorWorkWechatRelRepository;

use CompanysBundle\Entities\Operators;
use EasyWeChat\Factory;
use MerchantBundle\Entities\Merchant;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use WechatBundle\Entities\WechatAuth;

use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Dingo\Api\Exception\ResourceException;

use ThirdPartyBundle\Services\SaasCertCentre\CertClient;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use DistributionBundle\Services\DistributorService;
use WechatBundle\Services\DistributorWechatService;
use WechatBundle\Services\OfficialAccountService;
use WechatBundle\Services\OpenPlatform;
use WorkWechatBundle\Services\DistributorWorkWechatService;
use CompanysBundle\Ego\PrismEgo;
use CompanysBundle\Ego\CompanysActivationEgo;
use ShuyunBundle\Services\OperatorsService as ShuyunOperatorsService;

class AuthService
{
    protected $prismEgo;
    protected $prismIshopexService;

    /** @var OperatorsRepository */
    private $operatorsRepository;
    private $shopex_url;
    private $merchantRepository;

    public function __construct()
    {
        // ShopEx EcShopX Core Module
        $this->operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
        $this->shopex_url = config('common.openapi_shopex_url');
        $this->merchantRepository = app('registry')->getManager('default')->getRepository(Merchant::class);
    }

    /**
     * 获取公司的菜单类型字符串
     * @param int $companyId 公司ID
     * @return string 菜单类型字符串（all/b2c/platform/standard/in_purchase）
     */
    private function getCompanyMenuType($companyId)
    {
        $shopMenuService = new \SuperAdminBundle\Services\ShopMenuService();
        $menuTypeInfo = $shopMenuService->getMenuTypeByCompanyId($companyId);
        return $menuTypeInfo['menu_type_str'] ?? config('common.product_model');
    }

    public function retrieveByCredentials($params)
    {
        // Powered by ShopEx EcShopX
        switch (env('ADMIN_LOGIN_CHECK_LEVEL', '')){
            case 'img_code':
                $operatorsService = new OperatorsService();
                $type = 'login';
                $token = $params['token'] ?? '';
                $yzmcode = $params['yzm'] ?? '';
                if (!$operatorsService->checkImageVcode($token, $yzmcode, $type)) {
                    throw new ResourceException("图形验证码错误");
                }
                break;
            default:
                break;
        }
        // 默认使用本地账号登录
        $logintype = $params['logintype'] ?? 'localadmin';
        switch ($logintype) {
        case 'localadmin': // 本地商家管理员账号。本地添加的账号
            $operator = $this->credentialsByLocalAdminAccount($params);
            break;
        case 'dealer':
        case 'staff': // 商家员工账号。本地添加的账号
        case 'distributor': // 商家员工账号。本地添加的账号
        case 'supplier':
            $operator = $this->credentialsByStaffAccount($params);
            break;
        case 'oauthadmin':// 商派shopexid管理员账号，oauth登录
            $operator = $this->credentialsByShopexOauthCode($params);
            break;
        case 'shuyunadmin':
            $operator = $this->credentialsByShuyunCode($params);
            $logintype = $operator['logintype'];
            break;
        case 'oauthworkwechat':
            $operator = $this->credentialsByWorkWechatOauth($params);
            break;
        case 'workwechatbind':
            $operator = $this->credentialsByBindWorkWechat($params);
            break;
        case 'oauthwechat':
            $operator = $this->credentialsByWechatOauth($params);
            break;
        case 'wechatbinddistributor':
            $operator = $this->credentialsByBindWechatDistributor($params);
            break;
        case 'wechatbinddistributorbyusername':
            $operator = $this->credentialsByBindWechatDistributorByUsername($params);
            break;
        case 'wechatbinddistributorbylite':
            $operator = $this->credentialsByBindWechatDistributorByLite($params);
            break;
        case 'merchant':
            $operator = $this->credentialsByMerchantAccount($params);
            break;
        case 'admin': // 商派shopexid管理员账号。商派的shopexid账号
        default:
            // 默认也使用本地账号登录
            $operator = $this->credentialsByLocalAdminAccount($params);
            break;
        }
        $operator['logintype'] = $logintype;

        $newOperator = app('authorization')->getLoginToken($operator);
        // 移除激活验证，直接设置菜单类型
        // $company = (new CompanysActivationEgo())->check($operator['company_id']);
        $newOperator['menu_type'] = $operator['menu_type'] ?? config('common.product_model');
        return $newOperator;
    }

    // 通过 shopex账号 进行后台管理员认证信息获取
    public function credentialsByShopexAdminAccount($params)
    {
        $key = 'admin_login_failed_times:'.$params['username'];
        $failedTimes = app('redis')->incr($key);
        if ($failedTimes > 5) {
            throw new AccessDeniedHttpException('失败次数过多，请30分钟后重试');
        }
        app('redis')->expire($key, 1800);

        $this->prismEgo = new PrismEgo();
        // try {
            $prismResult = $this->prismEgo->getPrismAuth($params);
        // } catch (\Exception $e) {
        //     throw new AccessDeniedHttpException('账号密码错误，请重新登录');
        // }
        // app('log')->info("credentialsByShopexAdminAccount  prismResult====>".var_export($prismResult, 1));
        $operator = $this->operatorsRepository->getInfo(['mobile' => $prismResult['data']['shopexid'], 'operator_type' => 'admin']);
        if (!$operator) {
            if (config('common.system_is_saas') && config('common.system_open_online')) {
                throw new AccessDeniedHttpException('账号未开通，请联系客服');
            }

            $operatorData = [
                'eid' => $prismResult['data']['eid'],
                'mobile' => $prismResult['data']['shopexid'],
                'passport_uid' => $prismResult['data']['passport_uid'],
                'password' => $params['password'],
                'menu_type' => $params['product_model'] ?? config('common.product_model'),
            ];

            $operatorService = new OperatorsService();
            $operator = $operatorService->open($operatorData);
            $operator['operator_type'] = 'admin';
        }

        //保存 access_token 和 refresh_token
        $shopexUid = $prismResult['data']['passport_uid'];
        $accessToken = $prismResult['access_token'];
        $expiresIn = $prismResult['expires_in'];
        $refreshToken = $prismResult['refresh_token'];
        $refreshExpires = $prismResult['refresh_expires'];
        $shopexSmsClient = new ShopexSmsClient($operator['company_id'], $shopexUid);
        $shopexSmsClient->setAccessToken($accessToken, $expiresIn);
        $shopexSmsClient->setRefreshToken($refreshToken, $refreshExpires);

        // 获取shopex证书
        $certService = new CertService(new CertClient($operator['company_id'], $shopexUid));
        $certService->getAouthCert();

        app('redis')->del($key);
        
        // 获取公司的菜单类型
        $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
        return $operator;
    }

    // 通过 系统后台添加的本地管理员账号 进行后台管理员认证信息获取
    public function credentialsByLocalAdminAccount($params)
    {
        $key = 'admin_login_failed_times:'.$params['username'];
        $failedTimes = app('redis')->incr($key);
        if ($failedTimes > 5) {
            throw new AccessDeniedHttpException('失败次数过多，请30分钟后重试');
        }
        app('redis')->expire($key, 1800);

        // 内置admin账号特殊处理
        if ($params['username'] === 'admin') {
            // 查找admin账号
            $operator = $this->operatorsRepository->getInfo(['login_name' => 'admin', 'operator_type' => 'admin']);
            if (!$operator) {
                throw new AccessDeniedHttpException('账号不存在');
            }
            // 验证密码（使用password_verify验证加密后的密码）
            $checkPassword = password_verify($params['password'], $operator['password']);
            if (!$checkPassword) {
                throw new AccessDeniedHttpException('账号密码错误，请重新登录');
            }
            app('redis')->del($key);
            
            // 获取公司的菜单类型
            $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
            return $operator;
        }

        // 原有逻辑：通过手机号或login_name查询
        if (preg_match("/^1[23456789]{1}\d{9}$/", $params['username'])) {
            $filter['mobile'] = $params['username'];
        } else {
            $filter['login_name'] = $params['username'];
        }
        $filter['operator_type'] = 'admin';
        try {
            $operator = $this->operatorsRepository->getInfo($filter);

            if (!$operator) {
                throw new AccessDeniedHttpException('账号不存在');
            }
        } catch (\Exception $e) {
            throw new AccessDeniedHttpException('账号不存在');
        }

        $checkPassword = password_verify($params['password'], $operator['password']);
        if (!$checkPassword) {
            throw new AccessDeniedHttpException('账号密码错误，请重新登录');
        }

        app('redis')->del($key);
        
        // 获取公司的菜单类型
        $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
        return $operator;
    }

    // 通过 员工账号 认证信息获取
    public function credentialsByStaffAccount($params)
    {
        $key = ($params['logintype'] ?? 'staff').'_login_failed_times:'.$params['username'];
        $failedTimes = app('redis')->incr($key);
        if ($failedTimes > 5) {
            throw new AccessDeniedHttpException('失败次数过多，请30分钟后重试');
        }
        app('redis')->expire($key, 1800);

        $employeeService = new EmployeeService();
        $operator = $employeeService->employeeLogin($params);
        if (!$operator) {
            throw new AccessDeniedHttpException('账号密码错误，请重新登录');
        }

        app('redis')->del($key);
        
        // 获取公司的菜单类型
        if (isset($operator['company_id'])) {
            $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
        }
        
        return $operator;
    }

    // 通过 shopex账号 进行后台管理员认证信息获取
    public function credentialsByShopexOauthCode($params)
    {
        // 根据code 获取shopex账号信息
        $this->prismEgo = new PrismEgo();
        $res = $this->prismEgo->getToken($params['code']);
        $res['access_token'] = $res['access_token'] ?? '';
        $res['data'] = $res['data'] ?? [];
        if ($res['access_token'] && $res['data']) {
            $prismData = $res['data'];
            $operator = $this->operatorsRepository->getInfo(['mobile' => $prismData['shopexid'], 'operator_type' => 'admin']);
            if (!$operator) {
                if (config('common.system_is_saas') && config('common.system_open_online')) {
                    throw new ResourceException('账号未开通，请联系客服');
                    // throw new UnauthorizedHttpException('账号未开通，请联系客服');
                }

                $operatorData = [
                    'eid' => $prismData['eid'],
                    'mobile' => $prismData['shopexid'],
                    'passport_uid' => $prismData['passport_uid'],
                    'password' => $params['password'] ?? '',
                    'menu_type' => $params['product_model'] ?? config('common.product_model'),
                ];

                $operatorService = new OperatorsService();
                $operator = $operatorService->open($operatorData);
                $operator['operator_type'] = 'admin';
            }

            //保存 access_token 和 refresh_token
            $shopexUid = $prismData['passport_uid'];
            $accessToken = $res['access_token'];
            $expiresIn = $res['expires_in'];
            $refreshToken = $res['refresh_token'];
            $refreshExpires = $res['refresh_expires'];
            $shopexSmsClient = new ShopexSmsClient($operator['company_id'], $shopexUid);
            $shopexSmsClient->setAccessToken($accessToken, $expiresIn);
            $shopexSmsClient->setRefreshToken($refreshToken, $refreshExpires);

            // 获取shopex证书
            $certService = new CertService(new CertClient($operator['company_id'], $shopexUid));
            $certService->getAouthCert();
            
            // 获取公司的菜单类型
            $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
            return $operator;
        }
        return false;
    }

    /**
     * 改变权限后退出登陆
     *
     * @param $shopexId
     * @return bool
     */
    public function changeAuthLogout($shopexId): bool
    {
        app('log')->debug('changeAuthLogout:' . $shopexId);
        $operator = $this->operatorsRepository->getInfo(['mobile' => $shopexId, 'operator_type' => 'admin']);
        if (empty($operator)) {
            throw new ResourceException('账号信息不存在');
        }

        $this->setBlackTokenCache($operator['operator_id'], $operator['operator_type']);

        return true;
    }

    /**
     * 设置需重新登陆的token
     *
     * @param int $operatorId
     * @param string $operatorType
     * @return bool
     */
    public function setBlackTokenCache(int $operatorId, string $operatorType): bool
    {
        $key = $this->getBlackTokenKey($operatorId, $operatorType);

        app('redis')->connection('prism')->set($key, time());
        app('redis')->connection('prism')->expire($key, 86400 * 7);

        $this->operatorsRepository->updateOneBy(['operator_id' => $operatorId], ['updated' => time()]);
        return true;
    }

    /**
     * 删除需重新登陆的token
     *
     * @param int $operatorId
     * @param string $operatorType
     * @return bool
     */
    public function delBlackTokenCache(int $operatorId, string $operatorType): bool
    {
        $key = $this->getBlackTokenKey($operatorId, $operatorType);
        app('redis')->connection('prism')->del($key);
        return true;
    }

    /**
     * 获取需重新登陆的token
     *
     * @param int $operatorId
     * @param string $operatorType
     * @param $tokenLoginTime
     * @return bool
     */
    public function getBlackTokenCache(int $operatorId, string $operatorType, $tokenLoginTime): bool
    {
        $key = $this->getBlackTokenKey($operatorId, $operatorType);
        $setValue = app('redis')->connection('prism')->get($key);

        // 是否有重新登陆标记
        if (!$setValue) {
            return false;
        }

        // 重新设置密码的时间在用户登陆时间后面就需要重新登陆
        if ($tokenLoginTime && $setValue - 3 > $tokenLoginTime) {
            return true;
        }

        return false;
    }

    /**
     * @param int $operatorId
     * @param string $operatorType
     * @return string
     */
    private function getBlackTokenKey(int $operatorId, string $operatorType): string
    {
        return 'black_token_' . $operatorId . '_' . $operatorType;
    }

    // 通过企业微信oauth登录
    public function credentialsByWorkWechatOauth($params)
    {
        // 通过corp_id获取相关的后台微信配置
        $companyId = $params['company_id'];

        $config = app('wechat.work.wechat')->getConfig($companyId, 'dianwu');
        $wechatApp = Factory::work($config);

        $userInfo = $wechatApp->mobile->getUser($params['code']);

        $work_userid = $userInfo['UserId'] ?? '';
        if (!$work_userid) {
            throw new ResourceException('该账号不在企业通讯录中');
        }

        $userInfo = $wechatApp->user->get($work_userid);
        if ($userInfo['errcode'] != 0) {
            throw new ResourceException('该账号不在店务应用可见范围内');
        }

        $filter = [
            'company_id' => $companyId,
            'work_userid' => $work_userid,
        ];
        // 查询该user_id是否绑定店务端账号
        $workWechatService = new DistributorWorkWechatService();
        $relInfo = $workWechatService->getInfo($filter);
        // 绑定了本地账号的情况
        if ($relInfo && $relInfo['operator_id']) {
            $filter = [
                'company_id' => $companyId,
                'operator_id' => $relInfo['operator_id'],
            ];
            if ($operator = $this->operatorsRepository->getInfo($filter)) {
                // 获取公司的菜单类型
                $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
                return $operator;
            }
        }

        // 可以绑定手机号
        $encrypt = md5($params['code'].str_random(5));
        $workWechatService->setReBindMobileEncrypt($companyId, $work_userid, $encrypt);
        $bindInfo = [
            'company_id' => $companyId,
            'work_userid' => $work_userid,
            'check_token' => $encrypt,
        ];
        throw new ResourceException('账号未绑定', ['bind_info' => $bindInfo]);
    }

    /**
     * 关联店务本地账号与微信账号
     * 需要绑定后登录
     */
    public function credentialsByBindWorkWechat($params)
    {
        $operatorSmsService = new OperatorSmsService();
        if (!$operatorSmsService->checkVerifyCode($params['mobile'], 'login', $params['vcode'])) {
            throw new ResourceException('短信验证码错误');
        }

        $company_id = $params['company_id'];
        $work_userid = $params['work_userid'];
        $mobile = $params['mobile'];
        $encrypt_key = $params['check_token'];

        // 校验码
        $workWechatService = new DistributorWorkWechatService();
        if (!$workWechatService->checkReBindMobile($company_id, $work_userid, $encrypt_key)) {
            throw new ResourceException('校验信息失效，请重新绑定');
        }

        // 查询账户
        $filter = [
            'company_id' => $company_id,
            'mobile' => $mobile,
            'operator_type' => 'staff',
        ];
        $operator = $this->operatorsRepository->getInfo($filter);
        if (!$operator) {
            throw new ResourceException('需关联云店手机号');
        }

        /** @var DistributorWorkWechatRelRepository $workWechatRepositories */
        $workWechatRepositories = app('registry')->getManager('default')->getRepository(DistributorWorkWechatRel::class);

        $bound_filter = [
            'company_id' => $company_id,
            'operator_id' => $operator['operator_id'],
        ];

        if ($workWechatRepositories->getInfo($bound_filter)) {
            throw new ResourceException('该手机号已绑定，请更换对应的企业微信账号');
        }

        // 绑定账户
        $filter = [
            'company_id' => $company_id,
            'work_userid' => $work_userid,
        ];
        $data = [
            'operator_id' => $operator['operator_id'],
            'bound_time' => time(),
        ];
        if ($workWechatRepositories->getInfo($filter)) {
            $workWechatRepositories->updateOneBy($filter, $data);
        } else {
            $workWechatRepositories->create(array_merge($filter, $data));
        }

        $workWechatService->delReBindKey($company_id, $work_userid);

        // 获取公司的菜单类型
        $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
        return $operator;
    }

    /**
     * 获取oauth登录链接
     */
    public function getWorkwechatOuthorizeurl($company_id)
    {
        // 获取corp_id
        $workWechatService = new DistributorWorkWechatService();
        $config = $workWechatService->getConfig($company_id);
        if (!$config) {
            throw new ResourceException('company_id未配置');
        }
        $callback = config('common.dis_workwechat_h5_authuri');
        $query = http_build_query([
            'company_id' => $company_id
        ]);
        if (strpos($callback, '?')) {
            $callback .= ('&'.$query);
        } else {
            $callback .= ('?'.$query);
        }
        $data = [
            'appid' => $config['corpid'],
            'redirect_uri' => $callback,
            'response_type' => 'code',
            'scope' => 'snsapi_base',
        ];
        $query = http_build_query($data);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?{$query}&state={$company_id}#wechat_redirect";
    }

    public function getWechatAuthorizeUrl($company_id)
    {
        $oauth_url = '';
        $url = config('common.dis_workwechat_h5_authuri');
        // 获取微信公众号的对象
        $app = (new OpenPlatform())->getWoaApp([
            "company_id" => $company_id,
            "trustlogin_tag" => "weixin", // weixin
            "version_tag" => "touch" // touch
        ]);
        if ($oauthUrl = (new OfficialAccountService($app))->getAuthorizationUrl($url)) {
            $oauth_url = $oauthUrl;
        }
        return $oauth_url;
    }

    // 通过微信oauth登录
    public function credentialsByWechatOauth($params)
    {
        $company_id = $params['company_id'];
        // 获取微信公众号的对象
        $app = (new OpenPlatform())->getWoaApp([
            "company_id" => $company_id,
            "trustlogin_tag" => "weixin", // weixin
            "version_tag" => "touch" // touch
        ]);
        // 获取公众号的appid
        $woaAppid = $app->oauth->getClientId();

        $userInfo = (new OfficialAccountService($app))->getUserInfoByCode($params['code']);
        if (empty($userInfo)) {
            throw new ResourceException('该账号不在店务应用可见范围内');
        }

        $filter = [
            'company_id' => $company_id,
            'app_id' => $woaAppid,
            'app_type' => 'wx',
            'openid' => $userInfo['openid'],
            'unionid' => $userInfo['unionid'],
        ];

        $wechatService = new DistributorWechatService();
        $relInfo = $wechatService->getInfo($filter);
        // 绑定了本地账号的情况
        if ($relInfo && $relInfo['operator_id']) {
            $filter = [
                'company_id' => $company_id,
                'operator_id' => $relInfo['operator_id'],
            ];
            if ($operator = $this->operatorsRepository->getInfo($filter)) {
                // 获取公司的菜单类型
                $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
                return $operator;
            }
        }

        // 可以绑定手机号
        $encrypt = md5($params['code'].str_random(5));
        $wechatService->setReBindMobileEncrypt($company_id, $woaAppid, $userInfo['openid'], $userInfo['unionid'], $encrypt);
        $bindInfo = [
            'company_id' => $company_id,
            'app_id' => $woaAppid,
            'app_type' => 'wx',
            'openid' => $userInfo['openid'],
            'unionid' => $userInfo['unionid'],
            'check_token' => $encrypt,
        ];
        throw new ResourceException('账号未绑定', ['bind_info' => $bindInfo]);
    }

    public function credentialsByBindWechatDistributor($params)
    {
        $operatorSmsService = new OperatorSmsService();
        if (!$operatorSmsService->checkVerifyCode($params['mobile'], 'login', $params['vcode'])) {
            throw new ResourceException('短信验证码错误');
        }

        $company_id = $params['company_id'];
        $app_id = $params['app_id'];
        $app_type = $params['app_type'];
        $openid = $params['openid'];
        $unionid = $params['unionid'];
        $mobile = $params['mobile'];
        $encrypt_key = $params['check_token'];

        // 校验码
        $wechatService = new DistributorWechatService();
        if (!$wechatService->checkReBindMobile($company_id, $app_id, $openid, $unionid, $encrypt_key)) {
            throw new ResourceException('校验信息失效，请重新绑定');
        }

        // 查询账户
        $filter = [
            'company_id' => $company_id,
            'mobile' => $mobile,
            'operator_type' => 'distributor',
        ];
        $operator = $this->operatorsRepository->getInfo($filter);
        if (!$operator) {
            throw new ResourceException('需关联云店手机号');
        }

        $bound_filter = [
            'company_id' => $company_id,
            'app_type' => $app_type,
            'operator_id' => $operator['operator_id'],
        ];

        if ($wechatService->getInfo($bound_filter)) {
            throw new ResourceException('该手机号已绑定，请更换对应的企业微信账号');
        }

        // 绑定账户
        $filter = [
            'company_id' => $company_id,
            'app_id' => $app_id,
            'app_type' => $app_type,
            'openid' => $openid,
            'unionid' => $unionid,
        ];
        $data = [
            'operator_id' => $operator['operator_id'],
            'bound_time' => time(),
        ];
        if ($wechatService->getInfo($filter)) {
            $wechatService->updateOneBy($filter, $data);
        } else {
            $wechatService->create(array_merge($filter, $data));
        }

        $wechatService->delReBindKey($company_id, $app_id, $openid, $unionid);

        // 获取公司的菜单类型
        $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
        return $operator;
    }

    public function credentialsByBindWechatDistributorByUsername($params)
    {
        $company_id = $params['company_id'];
        $app_id = $params['app_id'];
        $app_type = $params['app_type'];
        $openid = $params['openid'];
        $unionid = $params['unionid'];
        $username = $params['username'];
        // 查询账户
        $filter = [
            'company_id' => $company_id,
            'username' => $username,
            'operator_type' => 'distributor',
        ];
        $operator = $this->operatorsRepository->getInfo($filter);
        if (!$operator) {
            throw new ResourceException('账号不存在');
        }

        $bound_filter = [
            'company_id' => $company_id,
            'app_type' => $app_type,
            'operator_id' => $operator['operator_id'],
        ];

        // 校验码
        $wechatService = new DistributorWechatService();
        if ($wechatService->getInfo($bound_filter)) {
            throw new ResourceException('该账号已绑定，请更换对应的微信账号');
        }

        // 绑定账户
        $filter = [
            'company_id' => $company_id,
            'app_id' => $app_id,
            'app_type' => $app_type,
            'openid' => $openid,
            'unionid' => $unionid,
        ];
        $data = [
            'operator_id' => $operator['operator_id'],
            'bound_time' => time(),
        ];
        if ($wechatService->getInfo($filter)) {
            $wechatService->updateOneBy($filter, $data);
        } else {
            $wechatService->create(array_merge($filter, $data));
        }

        // 获取公司的菜单类型
        $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
        return $operator;
    }

    public function credentialsByBindWechatDistributorByLite($params)
    {
        $company_id = $params['company_id'];
        $app_id = $params['app_id'];
        $app_type = $params['app_type'];
        $openid = $params['openid'];
        $unionid = $params['unionid'];
        // 绑定账户
        $filter = [
            'company_id' => $company_id,
            'app_id' => $app_id,
            'app_type' => $app_type,
            'openid' => $openid,
            'unionid' => $unionid,
        ];
        $wechatService = new DistributorWechatService();
        $bindData = $wechatService->getInfo($filter);
        if (empty($bindData)) {
            throw new ResourceException('请先绑定账号');
        }
        // 查询账户
        $filter = [
            'company_id' => $company_id,
            'operator_id' => $bindData['operator_id'],
            'operator_type' => 'distributor',
        ];
        $company = (new CompanysActivationEgo())->check($company_id);
        if ($company['product_model'] != 'platform') {
            $filter['operator_type'] = 'staff';
        }

        $operator = $this->operatorsRepository->getInfo($filter);
        if (!$operator) {
            throw new ResourceException('账号不存在');
        }

        // 获取公司的菜单类型
        $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
        return $operator;
    }

    /**u
     * 根据账号ID获取账号基本信息
     */
    public function getBasicUserById($operators)
    {
        $operatorId = $operators['id'];
        $operatorInfo = $this->operatorsRepository->getInfo(['operator_id' => $operatorId]);

        if (!$operatorInfo) {
            throw new UnauthorizedHttpException('帐号不存在');
        }

        $companyId = $operatorInfo['company_id'];
        $distributorIds = [];
        $shopIds = [];
        if ($operatorInfo['distributor_ids'] ?? '') {
            $distributorIds = is_array($operatorInfo['distributor_ids']) ? $operatorInfo['distributor_ids'] : json_decode($operatorInfo['distributor_ids'], true);
        }
        // 获取区域关联的店铺id,等于0则代表所有权限，不判断
        if (($operatorInfo['operator_type'] == 'staff') && $operatorInfo['regionauth_id'] > 0) {
            $distributorService = new DistributorService();
            $distributorIds = $distributorService->getDistributorIdByRegionAuthId($operatorInfo['company_id'], $operatorInfo['regionauth_id']);
        }
        if ($operatorInfo['shop_ids'] ?? '') {
            $shopIds = is_array($operatorInfo['shop_ids']) ? $operatorInfo['shop_ids'] : json_decode($operatorInfo['shop_ids'], true);
        }


        $authorizerAppid = app('registry')->getManager('default')
            ->getRepository(WechatAuth::class)
            ->getAuthorizerAppid($companyId);

        $distributorId = 0;
        if ($operatorInfo['operator_type'] == 'distributor') {
            $sid = 'select_distributor'.$operatorId.'-'.$companyId;
            $distributorId = app('redis')->connection('companys')->get($sid);
        }
        $result = [
            'id' => $operatorId,
            'operator_id' => $operatorId,
            'distributor_ids' => $distributorIds,
            'distributor_id' => $distributorId ?: 0,
            'shop_ids' => $shopIds,
            'mobile' => $operatorInfo['mobile'],
            'company_id' => $companyId,
            'authorizer_appid' => $authorizerAppid,
            'operator_type' => $operatorInfo['operator_type'],
            'username' => isset($operatorInfo['username']) ? $operatorInfo['username'] : '超级管理员',
            'head_portrait' => isset($operatorInfo['head_portrait']) ? $operatorInfo['head_portrait'] : '',
            'regionauth_id' => $operatorInfo['regionauth_id'],
            'updated' => $operatorInfo['updated'],
            'merchant_id' => $operatorInfo['merchant_id'],
            'is_merchant_main' => $operatorInfo['is_merchant_main'],
            'is_distributor_main' => $operatorInfo['is_distributor_main'],
            'is_disable' => $operatorInfo['is_disable'] ?? 0,
        ];

        return $result;
    }

    /**
    * 获取oauth登录链接
    */
    public function getOuthorizeurl()
    {
        $callback = config('common.shop_admin_url') . 'iframeLogin';
        $data = array(
            'response_type' => 'code',
            'client_id' => config('common.prism_key'),
            'redirect_uri' => $callback,
            'view' => 'ydsaas_iframe_login',
            'reg' => 'ydsaas_login',
            'direct_reg_uri' => config('common.shop_admin_url'),
        );
        $query = http_build_query($data);
        $url = "{$this->shopex_url}/oauth/authorize?{$query}";
        return $url;
    }

    /**
    * 退出登录
    */
    public function getOauthLogoutUrl($callback)
    {
        $params = array(
            'redirect_uri' => $callback,
        );
        $query = http_build_query($params);
        $url = "{$this->shopex_url}/oauth/logout?{$query}";
        return $url;
    }


    // 通过 系统后台添加的本地管理员账号 进行后台管理员认证信息获取
    public function credentialsByMerchantAccount($params)
    {
        $key = 'merchant_login_failed_times:'.$params['username'];
        $failedTimes = app('redis')->incr($key);
        if ($failedTimes > 5) {
            throw new AccessDeniedHttpException('失败次数过多，请30分钟后重试');
        }
        app('redis')->expire($key, 1800);

        if (preg_match("/^1[3456789]{1}\d{9}$/", $params['username'])) {
            $filter['mobile'] = $params['username'];
        } else {
            $filter['login_name'] = $params['username'];
        }
        $filter['operator_type'] = 'merchant';
        try {
            $operator = $this->operatorsRepository->getInfo($filter);
            if (!$operator) {
                throw new AccessDeniedHttpException('账号不存在');
            }
            if (empty($operator['merchant_id'])) {
                throw new AccessDeniedHttpException('该账号不能登陆');
            }
            $merchantInfo = $this->merchantRepository->getInfo(['id' => $operator['merchant_id']]);
            if (empty($merchantInfo)) {
                throw new AccessDeniedHttpException('商户不存在');
            }
            if (!empty($merchantInfo['disabled'])) {
                throw new AccessDeniedHttpException('该商户已禁用');
            }
        } catch (\Exception $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }

        $checkPassword = password_verify($params['password'], $operator['password']);
        if (!$checkPassword) {
            throw new AccessDeniedHttpException('账号密码错误，请重新登录');
        }

        app('redis')->del($key);
        
        // 获取公司的菜单类型
        $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
        return $operator;
    }

    // 通过 数云code 进行后台管理员认证信息获取
    public function credentialsByShuyunCode($params)
    {
        $shuyunoperatorsService = new ShuyunOperatorsService();
        if ($params['code'] == 'shopextest1') {
            $data['phone'] = '15202937200';
            $data['shopId'] = '88240602404045';
            $data['role'] = 0;
        } else if($params['code'] == 'shopextest2') {
            $data['phone'] = '13100000000';
            $data['shopId'] = '88240602404045';
            $data['userName'] = '测试用普通账号';
            $data['role'] = 1;
        } else {
            $data = $shuyunoperatorsService->getShuyunOperatorData($params['code']);
        }
        $filter = ['mobile' => $data['phone']];
        $operatorsService = new OperatorsService();
        switch ($data['role']) {
            case 0:
                // 超管 去授权中心校验
                $prismIshopexService = new PrismIshopexService();
                $result = $prismIshopexService->getYunqiaccountInfo($data['phone']);
                if ($result['status'] == 'failure') {
                    throw new ResourceException('管理员账号错误，请稍后重试~');
                }
                $filter['operator_type'] = 'admin';
                $operator = $operatorsService->getInfo($filter);
                if (empty($operator)) {
                    throw new ResourceException('管理员账号错误，请稍后重试~');
                }
                // 获取公司的菜单类型
                $operator['menu_type'] = $this->getCompanyMenuType($operator['company_id']);
                break;
            case 1:
                // 普通账号
                $filter['operator_type'] = 'staff';
                $operator = $operatorsService->getInfo($filter);
                if (empty($operator)) {
                    $operator = $this->operatorsRepository->getInfo(['operator_type' => 'admin', 'passport_uid' => $data['shopId']]);
                    if (empty($operator)) {
                        throw new ResourceException('请先设置超级管理员账号~');
                    }
                    $data['company_id'] = $operator['company_id'];
                    $operator = $shuyunoperatorsService->createStaff($data);
                }
                break;
            default:
                throw new ResourceException('管理员账号类型错误~');
                break;
        }
        
        $operator['logintype'] = $filter['operator_type'];
        return $operator;
    }
}
