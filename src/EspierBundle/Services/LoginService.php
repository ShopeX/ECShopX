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

namespace EspierBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use MembersBundle\Http\FrontApi\V1\Action\Members;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use SalespersonBundle\Http\FrontApi\V1\Action\SalespersonController;
use Symfony\Component\HttpFoundation\ParameterBag;
use WechatBundle\Http\FrontApi\V1\Action\Wxapp;
use WechatBundle\Services\OpenPlatform;
use WorkWechatBundle\Entities\WorkWechatRel;
use EmployeePurchaseBundle\Services\EmployeesService;
use MembersBundle\Services\MembersWhitelistService;
use AliBundle\Factory\MiniAppFactory;
use EmployeePurchaseBundle\Services\RelativesService;
use ShuyunBundle\Jobs\MemberRegisterJob;
use ShuyunBundle\Services\MembersService as ShuyunMembersService;

class LoginService
{
    /**
     * 微信小程序的预登录
     * @param array $requestData
     * @param array $authData
     * @param int $defaultDistributorId
     * @return array
     * @throws ResourceException
     */
    public function wxappPreLogin($params): array
    {
        $errorMessage = validator_params($params, [
            'appid' => ['required', '缺少参数，登录失败！'],
            'code' => ['required', '缺少参数，登录失败！'],
            'iv' => ['required', '缺少参数，登录失败！'],
            'encryptedData' => ['required', '缺少参数，登录失败！'],
        ]);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $openPlatformService = new OpenPlatform();
        // 获取app
        $app = $openPlatformService->getAuthorizerApplication($params['appid']);
        // 获取session key，并且会返回unionid和openid
        $res = $app->auth->session($params['code']);
        $sessionKey = $res['session_key'] ?? null;
        $unionId = $res['unionid'] ?? null;
        $openId = $res['openid'] ?? null;
        empty($unionId) ? $unionId = $openId : null;

        // 验证参数
        if (empty($sessionKey)) {
            app('log')->error('sessionKey_error => ' . var_export($res, true));
            throw new ResourceException('用户登录失败！');
        }
        if (empty($openId)) {
            throw new ResourceException('小程序授权错误，请联系供应商！');
        }
        if (empty($unionId)) {
            throw new ResourceException('此小程序未关联开放平台，请联系供应商！');
        }

        // 获取手机号
        $mobileData = $app->encryptor->decryptData($sessionKey, $params['iv'], $params['encryptedData']);
        $regionMobile = $mobileData['phoneNumber'] ?? ''; // 带区号的手机号
        $mobile = $mobileData['purePhoneNumber'] ?? ''; // 不带区号的纯手机号
        $countryCode = $mobileData['countryCode'] ?? ''; // 区号
        // 获取手机号
        if (!$mobile) {
            throw new ResourceException('授权手机号失败');
        }

        $wechatUserService = new WechatUserService();

        // 迁移模式，刷新旧的unionid为新的unionid
        if (config('common.transfer_mode')) {
            $wechatUser = $wechatUserService->getSimpleUser(['open_id' => $openId, 'authorizer_appid' => $params['appid'], 'company_id' => $params['company_id']]);
            if ($wechatUser && ($wechatUser['unionid'] != $unionId)) {
                $filter = [
                    'company_id' => $params['company_id'],
                    'authorizer_appid' => $params['appid'],
                    'open_id' => $openId,
                ];
                $wechatUserService->updateUnionId($filter, $wechatUser['unionid'], $unionId);
            }
        }

        // 创建/更新微信用户
        $weChatUserData = [
            'company_id' => $params['company_id'],
            'company_id' => $params['company_id'],
            'open_id' => $openId,
            'unionid' => $unionId,
            // 记录千人千码参数
            'source_id' => $params['source_id'] ?? 0,
            'monitor_id' => $params['monitor_id'] ?? 0,
            'inviter_id' => $params['source_id'] ?? 0,
            'source_from' => $params['source_from'] ?? 'default',
        ];
        $wechatUserInfo = $wechatUserService->createWxappFans($params['appid'], $weChatUserData);

        $userType = 'wechat';

        // 查询一次是否存在用户
        $memberService = new MemberService();
        $member = $memberService->getInfoByMobile($params['company_id'], $mobile);
        $params['open_id'] = $openId;
        $params['unionid'] = $unionId;
        $params['mobile'] = $mobile;
        $params['user_type'] = $userType;
        app('log')->info('wxappPreLogin member====>'.var_export($member, true));
        if (!$member) {
            $member = $this->register($params);
        } else {
            // 数云模式
            if (config('common.oem-shuyun')) {
                // unionid和mobile保持唯一性
                // $membersAssociation = $memberService->getMembersAssociation($params['company_id'], $userType, $unionId, $member['user_id']);
                $userAssociation = $memberService->getMembersAssociationByUserid($params['company_id'], $userType, $member['user_id']);
                app('log')->info('wxappPreLogin 手机号查询到member userAssociation====>'.var_export($userAssociation, true));
                if (!$userAssociation) {
                    $member = $this->register($params);
                } elseif ($userAssociation && $userAssociation['unionid'] != $unionId) {
                    throw new ResourceException('该手机号已注册为会员，请更换手机号！');
                }
                // 去数云注册
                $data = [
                    'mobile' => $member['mobile'],
                    'unionid' => $unionId,
                    'company_id' => $member['company_id'],
                    'user_id' => $member['user_id'],
                ];
                app('log')->info('file:'.__FILE__.',line:'.__LINE__);
                app('log')->info('shuyun MemberRegisterJob data=====>'.var_export($data, true));
                $gotoJob = (new MemberRegisterJob($member['company_id'], $member['user_id'], $data))->onQueue('default');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            } else {
                $membersAssociation = $memberService->getMembersAssociation($params['company_id'], $userType, $unionId, $member['user_id']);
                if (!$membersAssociation) {
                    $member = $this->register($params);
                }

            }
        }
        
        if (isset($params['salesperson_id']) && $params['salesperson_id']) {
            // 用户和导购的关联绑定
            $this->bindWithSalesperson($params['company_id'], $params['salesperson_id'], $member['user_id']);
        }

        return [
            'user_id' => $member['user_id'],
            'open_id' => $openId,
            'unionid' => $unionId,
        ];
    }

    public function aliappPreLogin($params) {
        $errorMessage = validator_params($params, [
            'code' => ['required', '缺少参数，登录失败！'],
            'encryptedData' => ['required', '缺少参数，登录失败！'],
        ]);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        // 换取授权访问令牌
        $app = (new MiniAppFactory())->getApp($params['company_id']);
        $oauthData = $app->getFactory()->base()->oauth()->getToken($params['code'])->toMap();
        if (!isset($oauthData['user_id'])) {
            throw new ResourceException('小程序授权信息错误，请联系服务商！');
        }

        // 解密获取手机号
        $decryptResult = $app->getFactory()->util()->aes()->decrypt($params['encryptedData']);
        $decryptData = json_decode($decryptResult, true);
        if (empty($decryptData['mobile'])) {
            throw new ResourceException('授权手机号失败');
        }
        $mobile = $decryptData['mobile'];


        $userType = 'ali';

        // 查询一次是否存在用户
        $memberService = new MemberService();
        $member = $memberService->getInfoByMobile($params['company_id'], $mobile);
        // 创建会员, 将用户信息添加至会员主表（members）
        $params['open_id'] = $oauthData['user_id'];
        $params['unionid'] = $oauthData['user_id'];
        $params['mobile'] = $mobile;
        $params['user_type'] = $userType;
        $params['alipay_appid'] = $app->getConfig()->getAppId();
        if (!$member) {
            $member = $this->register($params);
        } else {
            $membersAssociation = $memberService->getMembersAssociation($params['company_id'], $userType, $oauthData['user_id'], $member['user_id']);
            if (!$membersAssociation) {
                $member = $this->register($params);
            }
        }

        if (isset($params['salesperson_id']) && $params['salesperson_id']) {
            // 用户和导购的关联绑定
            $this->bindWithSalesperson($params['company_id'], $params['salesperson_id'], $member['user_id']);
        }

        return [
            'user_id' => $member['user_id'],
            'alipay_user_id' => $oauthData['user_id'],
        ];
    }

    /**
     * 注册用户
     * @return array
     * @throws \Exception
     */
    protected function register($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $employeesService = new EmployeesService();
            $employeeAuthParams = $params['employee_auth'] ?? [];
            $inviteCode = $params['invite_code'] ?? '';
            $employeeAuth = false;
            $relativeBind = false;
            if ($employeeAuthParams) {
                if (!($employeeAuthParams['enterprise_id'] ?? 0)) {
                    throw new ResourceException('企业ID必填');
                }
                $employeeAuth = true;
            } elseif ($inviteCode) {
                // 如果有分享码且分享码有效，先给分享码加锁
                $employeesService->lockInviteCode($params['company_id'], $inviteCode);
                $relativeBind = true;
            } else {
                // 检查白名单;
                $inWhitelist = (new MembersWhitelistService())->checkWhitelistValid($params['company_id'], $params['mobile'], $tips);
                if (!$inWhitelist) {
                    throw new ResourceException($tips);
                }
            }

            $memberService = new MemberService();
            $params['inviter_id'] = $params['inviter_id'] ?? 0;
            if (isset($params['uid']) && $params['uid']) {
                $memberInfo = $memberService->getMemberInfo([
                    'user_id' => $params['uid'],
                    'company_id' => $params['company_id']
                ]);
                if ($memberInfo) {
                    $params['inviter_id'] = $params['uid'];
                }
            } elseif(isset($params['puid']) && $params['puid']){
                // puid为推广二维码带的，推广员的user_id
                $memberInfo = $memberService->getMemberInfo([
                    'user_id' => $params['puid'],
                    'company_id' => $params['company_id']
                ]);
                if ($memberInfo) {
                    $params['inviter_id'] = $params['puid'];
                }
            } elseif (!$params['inviter_id'] && $params['user_type'] == 'wechat') {
                $wechatUser = (new WechatUserService())->getSimpleUserInfo($params['company_id'], $params['unionid']);
                $params['inviter_id'] = $wechatUser['inviter_id'] ?? 0;
                $params['source_from'] = $wechatUser['source_from'] ?? 'default';
            }

            // 创建用户
            $result = $memberService->createMember($params);

            // 员工认证
            if ($employeeAuth) {
                $employeeAuthParams['company_id'] = $params['company_id'];
                $employeeAuthParams['user_id'] = $result['user_id'];
                $employeeAuthParams['member_mobile'] = $result['mobile'];
                $employeeAuthParams['mobile'] = $result['mobile'];
                $employeesService->authentication($employeeAuthParams);
            }

            // 绑定家属
            if ($relativeBind) {
                $relativeBindParams = [
                    'company_id' => $params['company_id'],
                    'user_id' => $result['user_id'],
                    'member_mobile' => $result['mobile'],
                    'invite_code' => $inviteCode,
                ];
                $relativesService = new RelativesService();
                $relativesService->bindRelative($relativeBindParams);
                $employeesService->delInviteCode($params['company_id'], $inviteCode);
            }
            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            //解锁邀请码
            if ($relativeBind) {
                $employeesService->unlockInviteCode($params['company_id'], $inviteCode);
            }

            $conn->rollback();
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ];
            app('log')->info('会员注册失败:'.var_export($error, true));
            throw new ResourceException($e->getMessage());
        }
    }
    
    /**
     * 数云的中心小程序跳转到商城小程序后，去数云查询手机号，在商城直接注册成为会员
     * @param  array $inputData 
     * @param  array $params    
     */
    public function shuyunMemberSilent($inputData, $params)
    {
        app('log')->info('shuyunMemberSilent inputData====>'.var_export($inputData, true));
        app('log')->info('shuyunMemberSilent params====>'.var_export($params, true));
        if (!isset($inputData['shuyunappid']) || $inputData['shuyunappid'] == "") {
            return false;
        }
        // 去数云查询手机号
        $shuyunMembersService = new ShuyunMembersService($params['company_id']);
        $searchParams = [
            'shuyunappid' => $inputData['shuyunappid'],
            'unionid' => $params['unionid'],
        ];
        $mobile = $shuyunMembersService->memberSilentSearch($searchParams);
        app('log')->info('shuyunMemberSilent mobile:'.var_export($mobile, true));
        if (!$mobile) {
            return false;
        }
        // 注册商城会员
        // 创建/更新微信用户
        $weChatUserData = [
            'company_id' => $params['company_id'],
            'open_id' => $params['open_id'],
            'unionid' => $params['unionid'],
            // 记录千人千码参数
            'source_id' => $inputData['source_id'] ?? 0,
            'monitor_id' => $inputData['monitor_id'] ?? 0,
            'inviter_id' => $inputData['source_id'] ?? 0,
            'source_from' => $inputData['source_from'] ?? 'default',
        ];
        $wechatUserService = new WechatUserService();
        $wechatUserInfo = $wechatUserService->createWxappFans($inputData['appid'], $weChatUserData);

        $userType = 'wechat';

        // 查询一次是否存在用户
        $memberService = new MemberService();
        $member = $memberService->getInfoByMobile($params['company_id'], $mobile);
        $registorParams = $inputData;
        $registorParams['open_id'] = $params['open_id'];
        $registorParams['unionid'] = $params['unionid'];
        $registorParams['mobile'] = $mobile;
        $registorParams['user_type'] = $userType;
        $registorParams['api_from'] = 'wechat';
        app('log')->info('shuyunMemberSilent member====>'.var_export($member, true));
        if (!$member) {
            $member = $this->register($registorParams);
        } else {
            // unionid和mobile保持唯一性
            $userAssociation = $memberService->getMembersAssociationByUserid($params['company_id'], $userType, $member['user_id']);
            app('log')->info('shuyunMemberSilent mobile search member userAssociation====>'.var_export($userAssociation, true));
            if (!$userAssociation) {
                $member = $this->register($params);
            } elseif ($userAssociation && $userAssociation['unionid'] != $params['unionid']) {
                app('log')->info('shuyunMemberSilent 该手机号已注册为会员，请更换手机号！');
                return false;
                // throw new ResourceException('该手机号已注册为会员，请更换手机号！');
            }
        }
        if (isset($inputData['salesperson_id']) && $inputData['salesperson_id']) {
            // 用户和导购的关联绑定
            $this->bindWithSalesperson($params['company_id'], $inputData['salesperson_id'], $member['user_id']);
        }

        return [
            'user_id' => $member['user_id'],
            'open_id' => $params['open_id'],
            'unionid' => $params['unionid'],
        ];
    }

    /**
     * 让用户和导购做一个关联绑定
     * @return array|bool[]
     * @throws \Exception
     */
    public function bindWithSalesperson($companyId, $salespersonId, $userId)
    {
        $workWechatRepositories = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);

        //查找用户已绑定的导购员
        $bound = $workWechatRepositories->getInfo([
            'user_id' => $userId,
            'is_bind' => 1,
            'company_id' => $companyId,
        ]);
        if ($bound) {
            if ($bound['salesperson_id'] == $salespersonId) {
                return true;
            }
            return false;
        }

        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'salesperson_id' => $salespersonId,
        ];
        $data = $workWechatRepositories->getInfo($filter);
        if ($data) {
            $result = $workWechatRepositories->updateOneBy($filter, ['is_bind' => 1]); //修改
        } else {
            $data = [
                'user_id' => $userId,
                'salesperson_id' => $salespersonId,
                'company_id' => $companyId,
                'is_bind' => 1
            ];
            $result = $workWechatRepositories->create($data);
        }

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
