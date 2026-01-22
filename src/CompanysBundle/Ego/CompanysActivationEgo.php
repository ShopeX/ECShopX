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

namespace CompanysBundle\Ego;

use EspierBundle\Sdk\AuthCodeClient;
use CompanysBundle\Entities\Companys;
use CompanysBundle\Entities\Resources;
use Dingo\Api\Exception\ResourceException;
use GuzzleHttp\Client as Client;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use WechatBundle\Entities\WechatAuth;
use WechatBundle\Services\OpenPlatform;
use MembersBundle\Entities\Members;
use MembersBundle\Services\UserService;
use MembersBundle\Services\WechatUserService;
use CompanysBundle\Services\CompanysService;
use CompanysBundle\Services\AuthService;
use CompanysBundle\Services\RolesService;
use CompanysBundle\Ego\PrismEgo;
use CompanysBundle\Services\OperatorsService;
use SuperAdminBundle\Services\ShopMenuService;
use CompanysBundle\Ego\UpgradeEgo;
use MembersBundle\Entities\MembersAssociations;

class CompanysActivationEgo
{
    public $token = "7e23b4cecd91a0a8500c2fb65341193c";

    // 检测api的路径，如果没有买则判断是否购买了移动收银
    private static $mobile_cashier_path_check = [
        '/api/operator/cartdata/pending',
        '/api/operator/cartdataadd',
        '/api/operator/cartdatalist',
        '/api/checkout',
        '/api/order/create',
    ];

    //延长本地开发环境的有效期
    public function extendCompanyDemoLicense($companyId)
    {
        $upgradeEgo = new UpgradeEgo();
        $license = $upgradeEgo->getSwooleLicense();
        if (!isset($license['license_utype']) || $license['license_utype'] != 'developer') {
            throw new ResourceException('只能延长开发版的有效期');
        }

        if (config('app.env') != 'local') {
            throw new ResourceException('只能延长本地开发环境的有效期');
        }

        $resourcesRepository = app('registry')->getManager('default')->getRepository(Resources::class);
        $data = app('redis')->connection('companys')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, 1);
            $data['resource_id'] = $data['resouce_id'];
            if ($data['source'] != 'demo') {
                throw new ResourceException('只能延长测试激活码的有效期');
            }
        } else {
            $data = $resourcesRepository->getInfo(['company_id' => $companyId, 'source' => 'demo']);
        }
        if (!$data) {
            throw new ResourceException('没有有效企业的信息');
        }

        if ($data['expired_at'] > time()) {
            throw new ResourceException('只能过期之后再延长有效期');
        }

        $data['expired_at'] = time() + 15*24*60*60;
        $result = $resourcesRepository->update($data['resource_id'], $data);
        if ($result) {
            $this->saveLicenseRedis($result, $result['company_id']);
        }

        $companysService = new CompanysService();
        $resources = $companysService->getCompanyLastResource($companyId);
        $res = [
            'company_id' => $companyId,
            'expired_at' => $resources['expiredAt'],
            'resouce_id' => $resources['resourceId'],
            'source' => $resources['source'],
        ];
        app('redis')->connection('companys')->set($this->genReidsId($companyId), json_encode($res));

        return true;
    }

    public function createDemoCompanyLicense($params)
    {
        if (!isset($params['company_id'],$params['expired_at'])) {
            app('log')->debug('初始化商城授权信息有误 company_id、expired_at');
            throw new ResourceException('初始化商城授权信息有误');
        }
        $companysRepository  = app('registry')->getManager('default')->getRepository(Companys::class);
        $company = $companysRepository->get(['company_id' => $params['company_id']]);
        if(!$company) {
            app('log')->debug('没有有效的企业信息');
            throw new ResourceException("无相关企业信息");
        }
        $activeCode = "demo123";
        $params['available_days'] =  15;
        $params['shop_num'] = 1;
        $params['source'] = 'demo';
        $params['resource_name'] = '1店版';
        $params['left_shop_num'] = 1;
        $params['active_status'] = 'active';
        $params['active_at'] = time();
        $params['code'] = $activeCode;
        $params['active_code'] = self::encrypt($activeCode);
        $companyService = new CompanysService();
        $result = $companyService->active($params);
        if ($result && isset($result['resource_id']) && $result['resource_id']) {
            $this->saveLicenseRedis($params, $params['company_id']);
        }
        return $result;
    }

    public function createCompanyLicense($postdata)
    {
        if (!isset($postdata['user'])) {
            app('log')->debug('登录用户auth信息获取有误');
            throw new ResourceException('授权信息有误');
        }
        $userAuth = $postdata['user'];
        $companyId = $userAuth->get('company_id');
        if (!isset($postdata['active_code']) || !$postdata['active_code']) {
            app('log')->debug('没有有效的激活码');
            throw new ResourceException("激活信息有误");
        }
        if (!$companyId) {
            app('log')->debug('company_id为空');
            throw new ResourceException("企业信息有误");
        }
        $companysRepository  = app('registry')->getManager('default')->getRepository(Companys::class);
        $company = $companysRepository->get(['company_id' => $companyId]);
        if(!$company) {
            app('log')->debug('没有有效的企业信息');
            throw new ResourceException("无相关企业信息");
        }
        $shopexId = $company->getPassportUid();
        if (!$company->getPassportUid() || !$shopexId) {
            app('log')->debug('没有有效的shopex_id');
            throw new ResourceException("登录信息有误");
        }
        $activeCode = $postdata['active_code'];
        $params = $this->companysActivation($shopexId, $activeCode, $companyId);
        $params['company_id'] = $companyId;
        $params['eid'] = $company->getEid();
        $params['passport_uid'] = $shopexId;
        $companyService = new CompanysService();
        $result = $companyService->active($params);
        if ($result && isset($result['resource_id']) && $result['resource_id']) {
            $this->saveLicenseRedis($params, $companyId);
        }
        return $result;
    }

    public function createOnlineCompanyLicense($params)
    {
        if (!isset($params['eid'],$params['passport_uid'],$params['company_id'],$params['available_days'],$params['issue_id'],$params['goods_code'],$params['product_code'])) {
            app('log')->debug('初始化商城授权信息有误 eid 、passport_uid、company_id、available_days、issue_id、goods_code、product_code');
            throw new ResourceException('初始化商城授权信息有误');
        }
        $companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
        $company = $companysRepository->get(['company_id' => $params['company_id']]);
        if(!$company) {
            app('log')->debug('没有有效的企业信息');
            throw new ResourceException("无相关企业信息");
        }

        $activeAt = time();
        $expiredAt = $activeAt + $params['available_days'] * 86400;
        $activeCode = $params['issue_id'];
        $params['shop_num'] = 1;
        // $params['source'] = 'purchased';
        $params['resource_name'] = '1店版';
        $params['left_shop_num'] = 1;
        $params['active_status'] = 'active';
        $params['active_at'] = $activeAt;
        $params['expired_at'] = $expiredAt;
        $params['active_code'] = self::encrypt($activeCode);
        $companyService = new CompanysService();
        $result = $companyService->active($params);
        if ($result && isset($result['resource_id']) && $result['resource_id']) {
            $this->saveLicenseRedis($params, $params['company_id']);
        }
        return $result;
    }

    public function updateOnlineCompanyLicense($params)
    {
        if (!isset($params['expired_at'],$params['issue_id'])) {
            app('log')->debug('更新商城授权信息有误 expired_at、issue_id');
            throw new ResourceException('更新商城授权信息有误');
        }

        $companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
        $resourcesRepository = app('registry')->getManager('default')->getRepository(Resources::class);
        $data = $resourcesRepository->getInfo(['issue_id' => $params['issue_id']]);
        if (!$data) {
            throw new ResourceException('没有有效的企业信息');
        }

        $data['expired_at'] = $params['expired_at'];
        $result = $resourcesRepository->update($data['resource_id'], $data);
        if ($result) {
            $this->saveLicenseRedis($data, $data['company_id']);

            //更新企业有效期
            $resources = $resourcesRepository->getList(['company_id' => $data['company_id']], ['expired_at' => 'DESC'],0,1);
            $companysRepository->update(['company_id' => $data['company_id']], ['expiredAt' => $resources['list'][0]['expiredAt']]);
            $key = 'companyActivateInfo:'.sha1($data['company_id']);
            $res = [
                'company_id' => $data['company_id'],
                'expired_at'  => $resources['list'][0]['expiredAt'],
                'resouce_id'  => $resources['list'][0]['resourceId'],
            ];
            app('redis')->connection('companys')->set($key, json_encode($res));
        }
        return $result;
    }

    //获取登录用户token
    public function getLoginToken($operator)
    {
        if (!$operator || !isset($operator['company_id'], $operator['mobile'])) {
            app('log')->debug('登录用户信息异常');
            throw new ResourceException("登录账号异常");
        }

        if (!config('common.system_is_saas') && $operator['company_id'] != config('common.system_companys_id')) {
            // 独立部署客户
            throw new ResourceException("请使用授权的账号登录");
        }

        $authorizerAppid = app('registry')->getManager('default')
            ->getRepository(WechatAuth::class)
            ->getAuthorizerAppid($operator['company_id']);
        
        // 移除激活验证
        // $activateInfo = $this->checkValid($operator['company_id']);

        $sid = 'select_distributor'.$operator['operator_id'].'-'.$operator['company_id'];
        app('redis')->connection('companys')->set($sid, null);

        $id = self::encrypt(['id' => $operator['operator_id'], 'token' => $this->token, 'time' => time()]);
        $newOperator = [
            'id'            => $id,
            'company_id'    => $operator['company_id'],
            'mobile'        => $operator['mobile'],
            'operator_type' => $operator['operator_type'],
            'is_authorizer' => $authorizerAppid ? true : false,
            'logintype' => $operator['logintype'],
        ];

        // 移除license_authorize设置
        // if (isset($activateInfo['valid']) && $activateInfo['valid'] == 'true') {
        //     $licenseAuthorize = base64_encode(json_encode($activateInfo));
        //     $newOperator['license_authorize'] = $licenseAuthorize;
        // }

        $operatorsService = new OperatorsService();
        $operatorsService->updateOperator($operator['operator_id'], ['last_login_time' => time()]);

        (new AuthService())->delBlackTokenCache($operator['operator_id'], $operator['operator_type']);

        return $newOperator;
    }

    public function getUserData($identifier)
    {
        $authService = new AuthService();
        if (is_object($identifier) && ($identifier->source ?? '') == 'salesperson') {
            $operatorsService = new OperatorsService();
            try {
                //加锁防止创建多个帐号
                $key = 'admin_'.$identifier->shopexid;
                $succ = app('redis')->setnx($key, 1);
                if ($succ) app('redis')->expire($key, 10);
                while (!$succ) {
                    usleep(rand(1000, 1000000));
                    $succ = app('redis')->setnx($key, 1);
                    if ($succ) app('redis')->expire($key, 10);
                }

                $filter = [
                    'mobile' => $identifier->shopexid,
                    'operator_type' => 'admin',
                ];
                $admin = $operatorsService->getInfo($filter);
                if (!$admin) {
                    $admin = $authService->searchAndCreateAdminAccount([
                        'mobile' => $identifier->shopexid,
                        'password' => uniqid(),
                    ]);
                    if (!$admin) {
                        throw new ResourceException("登录验证错误");
                    }
                }
                app('redis')->expire($key, 0);
            } catch (\Exception $e) {
                app('redis')->expire($key, 0);
                throw new ResourceException($e->getMessage());
            }

            if ($identifier->login_type == 'admin') {
                $param['id'] = $admin['operator_id'];
            }

            if ($identifier->login_type == 'staff') {
                try {
                    if (!$identifier->mobile) {
                        if (!preg_match('/^1[3456789]{1}[0-9]{9}$/', $identifier->work_userid)) {
                            throw new ResourceException('请将企业微信员工账号设置为手机号');
                        }
                        $identifier->mobile = $identifier->work_userid;
                    }

                    $key = 'staff_'.$identifier->mobile;
                    $succ = app('redis')->setnx($key, 1);
                    if ($succ) app('redis')->expire($key, 10);
                    while (!$succ) {
                        usleep(rand(1000, 1000000));
                        $succ = app('redis')->setnx($key, 1);
                        if ($succ) app('redis')->expire($key, 10);
                    }

                    $filter = [
                        'mobile' => $identifier->mobile,
                        'operator_type' => 'staff',
                        // 'company_id' => $admin['company_id'],
                    ];
                    $staff = $operatorsService->getInfo($filter);
                    if (!$staff) {
                        $staffData['operator_type'] = 'staff';
                        $staffData['login_name'] = $identifier->login_name;
                        $staffData['mobile'] = $identifier->mobile;
                        $staffData['company_id'] = $admin['company_id'];
                        $staffData['username'] = $identifier->user_name;
                        $staffData['regionauth_id'] = 0;
                        $staffData['shop_ids'] = [];
                        $staffData['distributor_ids'] = [];
                        $staffData['password'] = password_hash(uniqid(), PASSWORD_DEFAULT);
                        $staff = $operatorsService->create($staffData);
                        if (!$staff) {
                            throw new ResourceException("登录验证错误");
                        }
                    } elseif ($staff['company_id'] != $admin['company_id']) {
                        throw new ResourceException("当前账号手机号已被使用");
                    }

                    app('redis')->expire($key, 0);
                } catch (\Exception $e) {
                    app('redis')->expire($key, 0);
                    throw new ResourceException($e->getMessage());
                }

                $param['id'] = $staff['operator_id'];
            }

            if (isset($param['id']) && $param['id']) {
                $user = $authService->getBasicUserById($param);
                $user['source'] = 'salesperson';
                $user['id'] = self::encrypt(['id' => $user['operator_id'], 'token' => $this->token, 'time' => time()]);
                return $user;
            }

            throw new ResourceException("登录验证错误");
        }

        if (is_object($identifier) && ($identifier->source ?? '') == 'salesperson_workwechat') {
            return $this->salespersonWorkwechatCheck($identifier);
        }

        // 店务端使用会员登录token
        if (strpos($identifier, '_espier_')) {
            $user = $this->userCheck($identifier);
            return $user;
        }
        $data = self::decrypt($identifier);
        if ($this->token == $data['token']) {
            // 判断是否需要重新登陆
            $param['id'] = $data['id'];
            $user = $authService->getBasicUserById($param);

            if (isset($user['is_disable']) && $user['is_disable']) {
                throw new UnauthorizedHttpException('','账号已被禁用');
            }

            //if (isset($data['time']) && $user['last_login_time'] > $data['time']) {
            //    throw new UnauthorizedHttpException('','账号已在其他设备登录');
            //}

            $tokenLoginTime = $data['time'] ?? 0;
            $isNeedRefresh = $authService->getBlackTokenCache($user['operator_id'], $user['operator_type'], $tokenLoginTime);
            if ($isNeedRefresh) {
                throw new UnauthorizedHttpException('','账号发生改动需重新登陆');
            }

            $user['id'] = $identifier;
            return $user;
        } else {
            app('log')->debug('登录用户信息异常');
            throw new ResourceException("登录验证错误");
        }
    }

    /**
     * 店务端使用会员登录的token
     * @param  string $identifier
     */
    private function userCheck($identifier)
    {
        list($userId, $openid, $unionid) = explode('_espier_', $identifier);
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $userEntity = $membersRepository->findOneBy(['user_id' => $userId]);
        if (empty($userEntity)) {
            throw new ResourceException("登录验证错误");
        }
        $companyId = 0;
        if ($openid && $unionid && $openid != 'companyid') {
            if ($openid == 'alipay') {
                $membersAssoRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
                $alipayuser = $membersAssoRepository->get(['user_id' => $userId, 'user_type' => 'ali', 'unionid' => $unionid]);
                if ($alipayuser) {
                    $companyId = $alipayuser['company_id'];
                }
            } else {
                $userService = new UserService(new WechatUserService());
                $user = $userService->getUserInfo(['unionid' => $unionid, 'open_id' => $openid]);
                $openPlatform = new OpenPlatform();
                if (isset($user['authorizer_appid']) && $user['authorizer_appid']) {
                    $companyId = $openPlatform->getCompanyId($user['authorizer_appid']);
                }
            }
        } else {
            $companyId = $unionid;
        }
        if (!$companyId) {
            throw new UnauthorizedHttpException('', '获取用户信息出错');
        }
        $operatorsService = new OperatorsService();
        $company = $this->check($companyId);
        $filter = [
            'company_id' => $companyId,
            'mobile' => $userEntity->getMobile(),
            'is_disable' => false,
        ];
        if ($company['product_model'] != 'platform') {
            // 云店读取平台管理员
            $filter['operator_type'] = 'staff';
        } else {
            // ecx 读取店铺管理员
            $filter['operator_type'] = 'distributor';
        }
        $operator = $operatorsService->getInfo($filter);
        app('log')->info('user filter===>'.var_export($filter,1));
        app('log')->info('user operator===>'.var_export($operator,1));
        if (empty($operator) || $operator['operator_type'] == 'admin') {
            app('log')->debug('账号信息异常：'.json_encode($identifier));
            throw new ResourceException("登录验证错误");
        }
        // 判断是否需要重新登陆
        $param['id'] = $operator['operator_id'];
        $authService = new AuthService();
        $user = $authService->getBasicUserById($param);

        if (isset($user['is_disable']) && $user['is_disable']) {
            throw new UnauthorizedHttpException('','账号已被禁用');
        }

        if (isset($data['time']) && isset($user['last_login_time']) && $user['last_login_time'] > $data['time']) {
            throw new UnauthorizedHttpException('','账号已在其他设备登录');
        }

        $tokenLoginTime = $data['time'] ?? 0;
        $isNeedRefresh = $authService->getBlackTokenCache($user['operator_id'], $user['operator_type'], $tokenLoginTime);
        if ($isNeedRefresh) {
            throw new UnauthorizedHttpException('','账号发生改动需重新登陆');
        }
        $user['source'] = 'user';
        $user['logintype'] = 'user';
        $user['id'] = $identifier;
        return $user;
    }

    private function salespersonWorkwechatCheck($identifier)
    {
        //验证系统账号是否存在，根据商派id
        $operatorsService = new OperatorsService();
        $filter = [
            'mobile' => $identifier->shopexid,
            'operator_type' => 'admin',
        ];
        $admin = $operatorsService->getInfo($filter);
        if (empty($admin)) {
            app('log')->debug('账号信息异常：'.json_encode($identifier));
            throw new ResourceException("登录验证错误");
        }

        //获取导购信息
        $salespersonService = new \ThirdPartyBundle\Services\MarketingCenter\SalespersonService();
        $userinfo = $salespersonService->getSalespersonInfoByWorkUserid($admin['company_id'], $identifier->work_userid);
        if (empty($userinfo['work_userid'] ?? null)) {
            app('log')->debug('导购信息异常：'.json_encode($identifier));
            throw new ResourceException("登录验证错误");
        }

        $user['company_id'] = $userinfo['company_id'];
        $user['distributor_ids'] = $userinfo['distributor_ids'];
        $user['work_userid'] = $userinfo['work_userid'];
        $user['source'] = 'salesperson_workwechat';
        $user['logintype'] = 'salesperson_workwechat';
        $user['operator_type'] = 'staff';
        $user['id'] = self::encrypt(['id' => $userinfo['work_userid'], 'token' => $this->token, 'time' => time()]);
        return $user;
    }


    public function checkUserAuth($userData)
    {
        if (in_array($userData['operator_type'], ['shopadmin', 'user'])) {
            return true;
        }

        if ($userData['source'] ?? '' && in_array($userData['source'], ['user'])) {
            return true;
        }

        if (!isset($userData['id'])) {
            app('log')->debug('用户信息错误，checkUserAuth');
            throw new ResourceException("用户信息错误，checkUserAuth");
        }
        $data = self::decrypt($userData['id']);
        if ($this->token != $data['token']) {
            app('log')->debug('用户token有误');
            throw new ResourceException("登录验证错误");
        }

        return true;
    }

    public function getMenu($userAuth, $menu_version)
    {
        $filter['company_id'] = $userAuth->get('company_id');
        if (!$filter['company_id']) {
            throw new ResourceException("登录验证错误");
        }

        $companysRepository  = app('registry')->getManager('default')->getRepository(Companys::class);
        $company = $companysRepository->get(['company_id' => $filter['company_id']]);
        if(!$company || $company->getExpiredAt() < time()) {
            return [
                ['url' => '/login'],
            ];
        }

        // 开源版本：移除激活码检查，直接返回菜单
        $operatorType = $userAuth->get('operator_type');
        if ($operatorType == 'distributor') {
            $filter['version'] = 3;
        } else if($operatorType == 'dealer') {
            $filter['version'] = 5;
        } else if($operatorType == 'merchant') {
            $filter['version'] = 6;
        } else if($operatorType == 'supplier') {
            $filter['version'] = 7;
        } else {
            $filter['version'] = 1;
        }
        // 如果指定了菜单版本，则覆盖上面的过滤条件，例如平台添加店铺角色，只获取店铺的菜单权限
        if ($menu_version) {
            $filter['version'] = 3;
        }
        $filter['operator_type'] = $operatorType;
        $filter['id'] = $userAuth->get('operator_id');

        $disabledMenus = [];
        // saas 或者非管理员不可使用菜单管理
        // if (config('common.system_is_saas') or $userAuth->get('operator_type') != 'admin') {
        //     $disabledMenus[] = 'menumanage';
        // }

        if ($disabledMenus) {
            $filter['disabled_menus'] = $disabledMenus;
        }

        $rolesService = new RolesService();
        return $rolesService->getPermissionTree($filter);
    }

    private function saveLicenseRedis($params, $companyId)
    {
        $redisKey = "AuthorizeActivation:". sha1($companyId);
        $AuthorizeActivation = self::encrypt($params);
        return app('redis')->connection('companys')->hset($redisKey, $params['active_code'], $AuthorizeActivation);
    }

    //激活企业并返回资源数据
    private function companysActivation($shopexId, $activeCode, $companyId)
    {
        // 请求激活码管理系统那边的验证激活码接口
        if (config('common.system_is_saas')) {
            $result = $this->checkActiveCode($shopexId, $activeCode, $msg);
        } else {
            $result = $this->independentCheckActiveCode($shopexId, $activeCode, $msg, $companyId);
        }
        if ($result && is_array($result) && (isset($result['days'], $result['store']) || isset($result['failure_time']))) {
            $params['available_days'] = $result['days'] ?? 5;
            $params['shop_num'] = $result['store'] ?? 1;
            if(!$params['shop_num']) {
                throw new ResourceException("激活门店数有误");
            }
            if(!$params['available_days']) {
                throw new ResourceException("激活天数有误！");
            }
            $params['source'] = 'purchased';
            $params['resource_name'] = $params['shop_num'].'店版';
            $params['left_shop_num'] = $params['shop_num'];
            $params['active_status'] = (isset($params['active_status']) && $params['active_status']) ? $params['active_status'] : 'active';
            $params['active_at'] = time();
            $params['code'] = $activeCode;
            if (isset($result['failure_time'])) {
                $params['available_days'] = (int)floor(($result['failure_time']-$params['active_at'])/86400);
            }
            $params['expired_at'] = $params['active_at'] + $params['available_days']*86400;
            $params['active_code'] = self::encrypt($activeCode);
            return $params;
        } else {
            app('log')->debug('激活码：'.$activeCode.', 错误信息：'.$msg);
            throw new ResourceException($msg);
        }
    }

    public function independentCheckActiveCode($shopexId, $activeCode, &$msg, $companyId)
    {
        $certService = new CertService(false, $companyId, $shopexId);;
        $certSetting = $certService->getCertSetting();

        $request_url = config('licensegateway.independent_license_url');
        $ProductType = array_column(swoole_get_license(), 'Product_type');
        $postData = [
            'node_id' => $certSetting['node_id'],
            'certificate' => $certSetting['cert_id'],
            'shop_url' => 'http://'.$_SERVER['HTTP_HOST'],
            'version' => config('licensegateway.version'),
            'product_name' => $ProductType[0],
        ];
        $data = [
            'shopex_id' => $shopexId,
            'active_code' => $activeCode
        ];
        $authcode = new AuthCodeClient('11958e9cfa0a44d7be353637220ee4ac');
        //换取激活code
        $code = $authcode->encode($data);
        $postData['product'] = config('licensegateway.product_name');
        $postData['code'] = $code;
        $client = new Client();
        $res = $client->post($request_url, ['verify'=>false, 'form_params' => $postData])->getBody();
        $result = json_decode($res->getContents(), 1);

        if ( ($result['code'] === 0 || $result['code'] === '0') && isset($result['data']) ) {
            $result['data'] = $authcode->decode($result['data']);
            $result['data']['failure_time'] = strtotime($result['data']['failure_time']);
            return $result['data'];
        }

        if($result['code'] != '0' || $result['code'] !== 0 ) {
            $msg = $result['message_zh'];
            return false;
        }
    }
    //验证资源包的有效性
    private function checkValid($companyId)
    {
        $data['expired_at'] = strtotime('2037-01-01');
        $data['desc'] = '开源版本';
        $data['valid'] = 'true';
        return $data;
        $redisKey = "AuthorizeActivation:". sha1($companyId);
        $datalist = app('redis')->connection('companys')->Hgetall($redisKey);

        if ($datalist) {
            foreach ($datalist as $data) {
                $resourceList[] = self::decrypt($data);
            }
        } else {
            $resourcesRepository   = app('registry')->getManager('default')->getRepository(Resources::class);
            $filter['company_id'] = $companyId;
            $datalist = $resourcesRepository->lists($filter);
            $resourceList = $datalist['list'];
        }

        $valid = 'false';
        $expiredAt = 0;
        $type = 'demo';
        foreach ($resourceList as $value) {
            if ($value['expired_at'] - time() <= 0) {
                // app('log')->debug('激活码或授权已过期, data:'.var_export($value, 1));
                continue;
            }
            if ($value['source'] == 'demo') {
                $valid = 'true';
                $expiredAt = $value['expired_at'] > $expiredAt ? $value['expired_at'] : $expiredAt;
                continue;
            }

            if (isset($value['issue_id']) && $value['issue_id']) {
                if (!$this->checkGoodsExpired($value['eid'], $value['goods_code'])) {
                    app('log')->debug('授权已过期, data:'.var_export($value, 1));
                    continue;
                }
            } else {
                if (!isset($value['active_code']) || !$value['active_code']) {
                    app('log')->debug('激活码不存在, data:'.var_export($value, 1));
                    continue;
                }
                $activeCode = self::decrypt($value['active_code']);
                if (config('common.system_is_saas')) {
                    $result = $this->checkActiveCode($value['passport_uid'], $activeCode, $msg);
                } else {
                    $result = $this->independentCheckActiveCode($value['passport_uid'], $activeCode, $msg, $companyId);
                }
                if (!$result) {
                    app('log')->debug('激活码验证失败, data:'.var_export($value, 1). ", code:".$activeCode.", msg:".$msg);
                    continue;
                }
            }

            $valid = 'true';
            $type = 'purchased';
            $expiredAt = $value['expired_at'] > $expiredAt ? $value['expired_at'] : $expiredAt;
        }
        $data = [
            'valid' =>  $valid,
            'expiredAt' => $expiredAt,
            'desc' => $type == 'purchased' ? '已购买' : '试用'
        ];
        // 免费版，返回验证成功
        $upgradeEgo = new UpgradeEgo();
        $license = $upgradeEgo->getSwooleLicense();
        if (isset($license['Product_type']) && ($license['Product_type'] == 'ECSHOPX2_FREE')) {
            $data['expired_at'] = strtotime('2037-01-01');
            $data['desc'] = '已购买';
            $data['valid'] = 'true';
        }
        return $data;
    }

    private function checkGoodsExpired($eid, $goodsCode) {
        $redisKey = "onlineGoodsInfo:". sha1($eid.'_'.$goodsCode);
        $data = app('redis')->connection('companys')->get($redisKey);
        if ($data) {
            $data = self::decrypt($data);
        } else {
            $prismEgo = new PrismEgo();
            $result = $prismEgo->getSnInfo($eid, $goodsCode);
            if (isset($result['status']) && $result['status'] == 'success' && isset($result['data'])) {
                $data = $result['data'];
                if (isset($data['service_end_time']) && strtotime($data['service_end_time']) > time()) {
                    app('redis')->connection('companys')->set($redisKey, self::encrypt($data));
                    app('redis')->connection('companys')->expire($redisKey, 3*24*3600);
                }
            }
        }

        if ($data && isset($data['service_end_time']) && strtotime($data['service_end_time']) > time()) {
            return true;
        }

        return false;
    }

    private function checkActiveCode($shopexId, $activeCode, &$msg)
    {
        $data = [
            'shopex_id' => $shopexId,
            'active_code' => $activeCode
        ];
        $authcode = new AuthCodeClient('11958e9cfa0a44d7be353637220ee4ac');
        //换取激活code
        $code = $authcode->encode($data);

        $postData = [
            'product' => config('licensegateway.product_name'),
            'code' => $code ,
        ];
        $request_url = config('licensegateway.license_url');
        $client = new Client();
        $res = $client->post($request_url, ['verify'=>false, 'form_params' => $postData])->getBody();
        $content = $res->getContents();
        $result = json_decode($content, 1);

        if ( ($result['code'] === 0 || $result['code'] === '0') && isset($result['data']) ) {
            $resdata['days'] = $result['data']['days'];
            $resdata['store'] = $result['data']['store'];
            return $resdata;
        }

        if ($result['code'] === 'E111') {
            $msg = $result['message_zh'];
            return true;
        }

        if($result['code'] != '0' || $result['code'] !== 0 ) {
            $msg = $result['message_zh'];
            return false;
        }
    }

    /**
     * 获取redis存储的ID
     */
    public function genReidsId($companyId)
    {
        return 'companyActivateInfo:'. sha1($companyId);
    }

    // 获取uri
    protected function getPathInfo($uri)
    {
        if (null === ($requestUri = $uri)) {
            return '/';
        }

        // Remove the query string from REQUEST_URI
        if (false !== $pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        if ('' !== $requestUri && '/' !== $requestUri[0]) {
            $requestUri = '/'.$requestUri;
        }

        return $requestUri;
    }

    // 检查激活、有效期等
    public function check($companyId)
    {
        // 开源版本：直接返回成功，不进行任何验证
        $companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
        $company = $companysRepository->getInfo(['company_id' => $companyId]);
        
        // 获取当前商户的产品类型，没有就用.env配置的
        $productModel = config('common.product_model', 'platform');
        if ($company && isset($company['menu_type'])) {
            $productModel = ShopMenuService::MENU_TYPE[$company['menu_type']] ?? $productModel;
        }
        
        return [
            'company_id' => $companyId,
            'product_model' => $productModel,
            'valid' => 'true',
            'is_valid' => true,
            'expired_at' => strtotime('2037-01-01'), // 设置一个很远的过期时间
            'desc' => 'ecshopX开源商业版本',
            'resouce_id' => 0,
            'source' => 'opensource',
        ];
    }

    public static function encrypt($encodeArray){
        $arrayString = serialize($encodeArray);
        srand();//生成随机数
        $encrypt_key = md5(rand(0,10000));//从0到10000取一个随机数
        $ctr = 0;
        $encodeString = '';
        for($i = 0;$i < strlen($arrayString);$i++){
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $encodeString .= $encrypt_key[$ctr].($arrayString[$i] ^ $encrypt_key[$ctr++]);
        }
        return base64_encode(self::key($encodeString));
    }

    //解密函数(参数:字符串，返回值:数组)
    public static function decrypt($encodeString){
        $encodeString = self::key(base64_decode($encodeString));
        $resultString = '';
        for($i = 0;$i < strlen($encodeString); $i++) {
            $md5 = $encodeString[$i];
            $resultString .= $encodeString[++$i] ^ $md5;
        }
        $stringArray = unserialize($resultString);
        return $stringArray;
    }

    public static function key($arrayString)
    {
        $codetoken='YW53dWx1eWFudGFuZw';
        $encrypt_key = md5($codetoken);
        $ctr = 0;
        $resultString = '';
        for($i = 0; $i < strlen($arrayString); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $resultString .= $arrayString[$i] ^ $encrypt_key[$ctr++];
        }
        return $resultString;
    }

    public function getApplications()
    {
        $applications = [
            'mobile_cashier' => true,
            'adapay' => true,
            'pointsmall' => true,
            'marketing_center' => true,
            'employee_purchase' => true,
            'seckill' => true,
        ];

        return $applications;
    }
}
