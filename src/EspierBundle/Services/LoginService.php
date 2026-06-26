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

use DistributionBundle\Entities\Distributor;
use DistributionBundle\Repositories\DistributorRepository;
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
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberBindPushService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberRegisterService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberSyncState;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;
use DistributionBundle\Services\DistributorService;

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
            $this->syncShuyunOpenPlatformWxappAfterLocalRegisterIfEnabled($params, $member, $mobile, $unionId, $openId, true);
            // register push dg - 推送会员基础信息到导购端
            // $this->pushMemberBasicInfoToSalesperson($params['company_id'], $member['user_id']);
        } else {
            // 数云模式
            if (config('common.oem-shuyun')) {
                // unionid和mobile保持唯一性
                // $membersAssociation = $memberService->getMembersAssociation($params['company_id'], $userType, $unionId, $member['user_id']);
                $userAssociation = $memberService->getMembersAssociationByUserid($params['company_id'], $userType, $member['user_id']);
                app('log')->info('wxappPreLogin 手机号查询到member userAssociation====>'.var_export($userAssociation, true));
                if (!$userAssociation) {
                    $member = $this->register($params);
                    $this->syncShuyunOpenPlatformWxappAfterLocalRegisterIfEnabled($params, $member, $mobile, $unionId, $openId, false);
                } elseif ($userAssociation && $userAssociation['unionid'] != $unionId) {
                    throw new ResourceException('该手机号已注册为会员，请更换手机号！');
                }
                if (! $this->isShuyunOpenPlatformEnabledForCompany((int) $params['company_id'])) {
                    // 去数云注册（仅 legacy LPEE）
                    $data = [
                        'mobile' => $member['mobile'],
                        'unionid' => $unionId,
                        'company_id' => $member['company_id'],
                        'user_id' => $member['user_id'],
                    ];
                    app('log')->info('file:'.__FILE__.',line:'.__LINE__);
                    app('log')->info('shuyun MemberRegisterJob data=====>'.var_export($data, true));
                    $gotoJob = (new MemberRegisterJob($member['company_id'], $member['user_id'], $data))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                }
            } else {
                $membersAssociation = $memberService->getMembersAssociation($params['company_id'], $userType, $unionId, $member['user_id']);
                if (!$membersAssociation) {
                    $member = $this->register($params);
                }

            }
        }

        // OPEN 补同步：仅依赖「公司已启用开放平台」，**不**再捆绑 legacy `oem-shuyun`；否则仅开 OPEN、未开 OEM 数云时永远不会走 member.register 补录。
        if ($this->isShuyunOpenPlatformEnabledForCompany((int) $params['company_id'])) {
            $member = $memberService->getInfoByMobile((int) $params['company_id'], $mobile);
            $this->maybeSyncShuyunOpenPlatformWxappOnlineAfterPreLoginIfNeeded(
                (int) $params['company_id'],
                $member,
                $mobile,
                (string) $unionId,
                (string) $openId
            );
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

            // 记录注册时的分销商和导购信息（直接使用传入的 distributor_id 作为门店ID）
            if (isset($params['distributor_id']) && $params['distributor_id'] !== '' && $params['distributor_id'] !== null) {
                $params['reg_distributor'] = (int)$params['distributor_id'];
            } else {
                $params['reg_distributor'] = 0;
            }

            //op_distributor start
            // op_distributor: 注册时默认与reg_distributor保持一致
            $params['op_distributor'] = $params['reg_distributor'];
            //op_distributor end
            
            // reg_salesperson: 取 gu_user_id，否则为空字符串
            $params['reg_salesperson'] = '';
            if (!empty($params['work_userid'])) {
                $params['reg_salesperson'] = (string)$params['work_userid'];
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

            // 检测导购好友关系
            $this->checkSalespersonFriendStatus($result['user_id'], $params, $params['company_id']);

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

    /**
     * 推送会员基础信息到导购端
     * @param int $companyId 企业ID
     * @param int $userId 会员ID
     * @return void
     */
    public function pushMemberBasicInfoToSalesperson($companyId, $userId)
    {
        try {
            $memberService = new MemberService();
            
            // 获取会员完整信息（包括 members 和 members_info）
            $memberInfo = $memberService->getMemberInfo([
                'user_id' => $userId,
                'company_id' => $companyId
            ], true);
            
            if (!$memberInfo || !isset($memberInfo['user_id'])) {
                app('log')->warning('推送会员基础信息到导购端：未找到会员信息', [
                    'company_id' => $companyId,
                    'user_id' => $userId
                ]);
                return;
            }
            
            // 获取 unionid
            $wechatUserService = new WechatUserService();
            $wechatUserInfo = $wechatUserService->getUserInfo([
                'user_id' => $userId,
                'company_id' => $companyId
            ]);
            $unionid = $wechatUserInfo['unionid'] ?? '';
            
            // 准备推送数据
            $pushData = [
                'user_id' => (string)$memberInfo['user_id'],
            ];
            
            // unionid
            if ($unionid) {
                $pushData['unionid'] = $unionid;
            }
            
            // birthday (格式：YYYY-MM-DD)
            if (!empty($memberInfo['birthday'])) {
                $birthday = $memberInfo['birthday'];
                // 如果是时间戳，转换为日期格式
                if (is_numeric($birthday)) {
                    $pushData['birthday'] = date('Y-m-d', $birthday);
                } else {
                    $pushData['birthday'] = $birthday;
                }
            }
            
            // reg_distributor (注册时的分销商编号 shop_code)
            if (isset($memberInfo['reg_distributor']) && $memberInfo['reg_distributor'] > 0) {
                $distributorService = new DistributorService();
                $distributorInfo = $distributorService->getInfoSimple([
                    'company_id' => $companyId,
                    'distributor_id' => $memberInfo['reg_distributor']
                ]);
                if ($distributorInfo && !empty($distributorInfo['shop_code'])) {
                    $pushData['reg_distributor'] = $distributorInfo['shop_code'];
                }
            }
            
            // reg_salesperson (注册时的导购ID)
            if (isset($memberInfo['reg_salesperson']) && !empty($memberInfo['reg_salesperson'])) {
                $pushData['reg_salesperson'] = (string)$memberInfo['reg_salesperson'];
            }
            
            // fp_salesperson (分配的导购ID)
            if (isset($memberInfo['fp_salesperson']) && $memberInfo['fp_salesperson'] > 0) {
                $pushData['fp_salesperson'] = (int)$memberInfo['fp_salesperson'];
            }
            
            // has_fp (是否有分配导购。0:否；1:是)
            if (isset($memberInfo['has_fp'])) {
                $pushData['has_fp'] = (int)$memberInfo['has_fp'];
            }
            
            // grade_id (会员等级ID)
            if (!empty($memberInfo['grade_id'])) {
                $pushData['grade_id'] = (int)$memberInfo['grade_id'];
            }
            
            // mobile (手机号)
            if (!empty($memberInfo['mobile'])) {
                $pushData['mobile'] = $memberInfo['mobile'];
            }
            
            // username (用户名)
            if (!empty($memberInfo['username'])) {
                $pushData['username'] = $memberInfo['username'];
            }
            
            // avatar (头像URL)
            if (!empty($memberInfo['avatar'])) {
                $pushData['avatar'] = $memberInfo['avatar'];
            }
            
            // 调用导购端接口推送数据
            $marketingCenterRequest = new MarketingCenterRequest();
            $result = $marketingCenterRequest->call($companyId, 'basics.member.syncBasicInfo', $pushData);
            
            app('log')->info('推送会员基础信息到导购端', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'push_data' => $pushData,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            // 推送失败不影响注册流程，只记录日志
            app('log')->error('推送会员基础信息到导购端失败', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 检测导购好友关系并更新会员信息
     * @param int $userId 会员ID
     * @param array $params 注册参数
     * @param int $companyId 公司ID
     */
    public function checkSalespersonFriendStatus($userId, $params, $companyId)
    {
        try {
            // 检查是否有unionid和门店信息
            $unionid = $params['unionid'] ?? '';
            if (empty($unionid)) {
                app('log')->info('注册时检测导购好友关系：缺少unionid，跳过检测', [
                    'user_id' => $userId,
                    'company_id' => $companyId
                ]);
                return;
            }

            // 获取门店编号（shop_code）
            $shopCode = '';
            $distributorId = $params['reg_distributor'] ?? $params['op_distributor'] ?? 0;
            
            if ($distributorId > 0) {
                $distributorService = new DistributorService();
                $distributorInfo = $distributorService->getInfoSimple([
                    'distributor_id' => $distributorId,
                    'company_id' => $companyId
                ]);
                $shopCode = $distributorInfo['shop_code'] ?? '';
            }

            if (empty($shopCode)) {
                app('log')->info('注册时检测导购好友关系：缺少门店编号，跳过检测', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId
                ]);
                return;
            }

            // 调用接口查询导购好友关系
            $marketingCenterRequest = new MarketingCenterRequest();
            $apiParams = [
                'shop_code' => $shopCode,
                'unionid' => $unionid
            ];
            
            app('log')->info('注册时检测导购好友关系：开始查询', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'shop_code' => $shopCode,
                'unionid' => $unionid
            ]);

            $result = $marketingCenterRequest->call($companyId, 'members.getFriendWorkUserid', $apiParams);
            
            app('log')->info('注册时检测导购好友关系：接口返回', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'result' => $result
            ]);

            // 检查返回结果
            if (!empty($result['data']['work_userid'])) {
                $workUserid = $result['data']['work_userid'];
                
                // 如果有work_userid（已加好友），更新会员信息
                if (!empty($workUserid)) {
                    $memberService = new MemberService();
                    $updateParams = [
                        'has_fp' => 1,
                        'is_become_friend' => 1,
                        'fp_salesperson' => $workUserid
                    ];
                    $filter = [
                        'user_id' => $userId,
                        'company_id' => $companyId
                    ];
                    
                    $memberService->updateMemberInfo($updateParams, $filter);
                    
                    app('log')->info('注册时检测导购好友关系：已更新会员信息', [
                        'user_id' => $userId,
                        'company_id' => $companyId,
                        'work_userid' => $workUserid,
                        'update_params' => $updateParams
                    ]);
                }
            }
        } catch (\Exception $e) {
            // 检测失败不影响注册流程，只记录日志
            app('log')->error('注册时检测导购好友关系失败', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 双开门禁：OPEN 已启用时，会员域不再走 legacy LPEE MemberRegisterJob。
     */
    protected function isShuyunOpenPlatformEnabledForCompany(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }
        $openConfigRepo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $openConfig = $openConfigRepo->findOneByCompanyId($companyId);

        return $openConfig !== null && (int) $openConfig->getIsEnabled() === 1;
    }

    /**
     * 按会员 {@see Members::$reg_distributor} 解析分销商行（用于数云 OPEN 线上 wxapp shopId）。
     *
     * @return array<string, mixed>
     */
    protected function resolveWxappOpenDistributorRowByRegDistributorId(int $companyId, int $regDistributorId): array
    {
        if ($companyId <= 0 || $regDistributorId <= 0) {
            return [];
        }
        $distributorRepo = app('registry')->getManager('default')->getRepository(Distributor::class);
        if (! $distributorRepo instanceof DistributorRepository) {
            return [];
        }
        $row = $distributorRepo->getInfo([
            'company_id' => $companyId,
            'distributor_id' => $regDistributorId,
        ]);

        return is_array($row) && $row !== [] ? $row : [];
    }

    /**
     * OPEN 已启用：数云 member.register + 卡号增强 + bind.push；成功后写入 {@see Members::$shuyun_open_online_wxapp_sync_at}。
     *
     * @param  array<string, mixed>  $distributorRow  distribution_distributor 行（须含 distributor_id）
     *
     * @throws ResourceException 当 $compensateWithLocalDeleteOnFailure 且数云失败时（与历史 wxapp 新注册一致）
     */
    protected function performShuyunOpenPlatformWxappOnlineSync(
        int $companyId,
        array $distributorRow,
        int $userId,
        string $mobile,
        string $unionId,
        string $openId,
        bool $compensateWithLocalDeleteOnFailure
    ): void {
        if ($companyId <= 0) {
            throw new ResourceException('公司参数错误，会员绑定失败');
        }
        if (! $this->isShuyunOpenPlatformEnabledForCompany($companyId)) {
            return;
        }
        if ($userId <= 0) {
            throw new ResourceException('会员标识异常，会员绑定失败');
        }
        $memberService = new MemberService();
        $memberRow = $memberService->getMemberInfo([
            'company_id' => $companyId,
            'user_id' => $userId,
        ]);
        if (\is_array($memberRow) && (int) ($memberRow['shuyun_open_online_wxapp_sync_at'] ?? 0) > 0) {
            return;
        }
        if (\is_array($memberRow) && ShuyunOpenPlatformMemberSyncState::needsWxappBindPushOnly($memberRow)) {
            $offlineDistId = (int) ($memberRow['offline_reg_distributor'] ?? 0);
            $offlineRow = $this->resolveWxappOpenDistributorRowByRegDistributorId($companyId, $offlineDistId);
            if ($offlineRow === []) {
                throw new ResourceException('注册门店无效，会员绑定失败');
            }
            try {
                $this->performShuyunOpenPlatformWxappBindPushOnlySync(
                    $companyId,
                    $offlineRow,
                    $userId,
                    $unionId,
                    $openId
                );
            } catch (\Throwable $e) {
                app('log')->warning('wxapp open platform bind.push failed after store offline register.', [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
                if ($compensateWithLocalDeleteOnFailure) {
                    throw new ResourceException('会员绑定失败，请稍后重试');
                }
                throw $e;
            }

            return;
        }
        $userIdStr = (string) $userId;

        $shuyunOpenMemberRegisterSucceeded = false;
        try {
            app(ShuyunOpenPlatformMemberRegisterService::class)->registerSingle(
                $companyId,
                $distributorRow,
                $userIdStr,
                $mobile,
                $unionId
            );
            $shuyunOpenMemberRegisterSucceeded = true;
            (new MemberService())->syncUserCardCodeFromShuyunEnhanceAfterRegister(
                $companyId,
                $userId,
                $distributorRow
            );
            app(ShuyunOpenPlatformMemberBindPushService::class)->pushSingle(
                $companyId,
                $distributorRow,
                $userIdStr,
                $unionId,
                $openId
            );
            (new MemberService())->updateMemberInfo(
                ['shuyun_open_online_wxapp_sync_at' => time()],
                ['user_id' => $userId, 'company_id' => $companyId]
            );
        } catch (\Throwable $e) {
            app('log')->warning('wxapp open platform member.register/bind.push failed after local register.', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'mobile' => $mobile,
                'union_id' => $unionId,
                'open_id' => $openId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'compensate_delete' => $compensateWithLocalDeleteOnFailure,
                'shuyun_open_member_register_succeeded' => $shuyunOpenMemberRegisterSucceeded,
            ]);
            if ($compensateWithLocalDeleteOnFailure) {
                try {
                    (new MemberService())->deleteMembers(
                        $companyId,
                        $userId,
                        $mobile,
                        null,
                        false,
                        ! $shuyunOpenMemberRegisterSucceeded
                    );
                } catch (\Throwable $deleteEx) {
                    app('log')->error('wxapp open platform sync failure: local deleteMembers compensation failed.', [
                        'company_id' => $companyId,
                        'user_id' => $userId,
                        'exception' => get_class($deleteEx),
                        'message' => $deleteEx->getMessage(),
                    ]);
                }
            }
            throw new ResourceException('会员绑定失败，请稍后重试');
        }
    }

    /**
     * 店务已 member.register：仅 bind.push 并标记 wxapp 同步时间（不重复 register）。
     *
     * @param  array<string, mixed>  $distributorRow
     */
    protected function performShuyunOpenPlatformWxappBindPushOnlySync(
        int $companyId,
        array $distributorRow,
        int $userId,
        string $unionId,
        string $openId
    ): void {
        app(ShuyunOpenPlatformMemberBindPushService::class)->pushSingle(
            $companyId,
            $distributorRow,
            (string) $userId,
            $unionId,
            $openId
        );
        (new MemberService())->updateMemberInfo(
            ['shuyun_open_online_wxapp_sync_at' => time()],
            ['user_id' => $userId, 'company_id' => $companyId]
        );
    }

    /**
     * 老会员首次线上 OPEN 补同步（不删会员、失败不阻断登录）。
     * 条件：`shuyun_open_online_wxapp_sync_at` 仍为空；用 **`reg_distributor`（若>0）否则 `offline_reg_distributor`** 解析门店行，能解析到有效 `distribution_distributor` 即尝试补录（**不再**要求虚拟店 `distributor_self=1`）。
     *
     * @param  array<string, mixed>  $member  {@see MemberService::getInfoByMobile} 行
     */
    protected function maybeSyncShuyunOpenPlatformWxappOnlineAfterPreLoginIfNeeded(
        int $companyId,
        array $member,
        string $mobile,
        string $unionId,
        string $openId
    ): void {
        if ($companyId <= 0 || $member === [] || $mobile === '' || $unionId === '' || $openId === '') {
            return;
        }
        if ((int) ($member['shuyun_open_online_wxapp_sync_at'] ?? 0) > 0) {
            return;
        }
        if (ShuyunOpenPlatformMemberSyncState::needsWxappBindPushOnly($member)) {
            $offlineDistId = (int) ($member['offline_reg_distributor'] ?? 0);
            $distributorRow = $this->resolveWxappOpenDistributorRowByRegDistributorId($companyId, $offlineDistId);
            if ($distributorRow === []) {
                app('log')->warning('wxapp open platform supplement skipped: offline distributor row missing.', [
                    'company_id' => $companyId,
                    'user_id' => $member['user_id'] ?? null,
                    'offline_reg_distributor' => $offlineDistId,
                ]);

                return;
            }
            $userId = (int) ($member['user_id'] ?? 0);
            if ($userId <= 0) {
                return;
            }
            try {
                $this->performShuyunOpenPlatformWxappBindPushOnlySync(
                    $companyId,
                    $distributorRow,
                    $userId,
                    $unionId,
                    $openId
                );
            } catch (\Throwable $e) {
                app('log')->warning('wxapp open platform supplement bind.push failed (login continues).', [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            }

            return;
        }
        $regDist = (int) ($member['reg_distributor'] ?? 0);
        if ($regDist <= 0) {
            $regDist = (int) ($member['offline_reg_distributor'] ?? 0);
        }
        if ($regDist <= 0) {
            return;
        }
        $distributorRow = $this->resolveWxappOpenDistributorRowByRegDistributorId($companyId, $regDist);
        if ($distributorRow === []) {
            app('log')->warning('wxapp open platform supplement skipped: distributor row missing.', [
                'company_id' => $companyId,
                'user_id' => $member['user_id'] ?? null,
                'resolved_distributor_id' => $regDist,
                'reg_distributor' => (int) ($member['reg_distributor'] ?? 0),
                'offline_reg_distributor' => (int) ($member['offline_reg_distributor'] ?? 0),
            ]);

            return;
        }
        $userId = (int) ($member['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }
        try {
            $this->performShuyunOpenPlatformWxappOnlineSync(
                $companyId,
                $distributorRow,
                $userId,
                $mobile,
                $unionId,
                $openId,
                false
            );
        } catch (\Throwable $e) {
            app('log')->warning('wxapp open platform supplement sync failed (login continues).', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * OPEN 已启用时：在本地 register 成功并取得 user_id 之后，事务外调用数云
     * member.register（id=user_id）与 bind.push（platAccount=user_id；partner 见 config shuyun_open_platform.gateway_partner，默认 nnormal）。
     * 与订单计划 A-ORD-MEMREG-01 / A-ORD-BIND-01、A-ORD-MEMREG-02（禁止 DB 长事务跨 HTTP）一致。
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $member  register 返回行，须含 user_id
     * @param  bool  $compensateWithLocalDeleteOnFailure  true：本次为「按手机号无会员行」新注册路径，失败时尝试本地 deleteMembers 补偿
     *
     * @throws ResourceException
     */
    protected function syncShuyunOpenPlatformWxappAfterLocalRegisterIfEnabled(
        array $params,
        array $member,
        string $mobile,
        string $unionId,
        string $openId,
        bool $compensateWithLocalDeleteOnFailure
    ): void {
        $companyId = (int) ($params['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new ResourceException('公司参数错误，会员绑定失败');
        }

        if (! $this->isShuyunOpenPlatformEnabledForCompany($companyId)) {
            return;
        }

        $userId = (int) ($member['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new ResourceException('会员标识异常，会员绑定失败');
        }

        $regDist = (int) ($member['reg_distributor'] ?? 0);
        $distributorRow = $this->resolveWxappOpenDistributorRowByRegDistributorId($companyId, $regDist);
        if ($distributorRow === []) {
            throw new ResourceException('注册门店无效，会员绑定失败');
        }

        $this->performShuyunOpenPlatformWxappOnlineSync(
            $companyId,
            $distributorRow,
            $userId,
            $mobile,
            $unionId,
            $openId,
            $compensateWithLocalDeleteOnFailure
        );
    }
}
