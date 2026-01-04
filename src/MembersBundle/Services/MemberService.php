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

namespace MembersBundle\Services;

use CompanysBundle\Services\Shops\ProtocolService;
use EspierBundle\Services\Config\ConfigRequestFieldsService;
use KaquanBundle\Services\PackageSetService;
use MembersBundle\Entities\MembersAddress;
use MembersBundle\Entities\MembersDeleteRecord;
use MembersBundle\Entities\WechatUsers;
// use MembersBundle\Repositories\MembersAssociationsRepository;
// use MembersBundle\Repositories\MembersRepository;
// use MembersBundle\Repositories\WechatUsersRepository;
use MembersBundle\Repositories\MembersGatUserRepository;
use OrdersBundle\Entities\NormalOrders;
use PopularizeBundle\Entities\Promoter;
use PromotionsBundle\Services\RegisterPromotionsService;
use PromotionsBundle\Services\EmployeePurchaseActivityService;
use SalespersonBundle\Entities\ShopSalesperson;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use SalespersonBundle\Services\SalespersonTaskRecordService;
use SalespersonBundle\Services\SalespersonService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorUserService;
use Exception;
use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\VipGradeOrderService;
use KaquanBundle\Services\VipGradeService;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersAssociations;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Events\CreateMemberSuccessEvent;
use MembersBundle\Traits\GetCodeTrait;
use PointBundle\Services\PointMemberRuleService;
use PopularizeBundle\Services\PromoterService;
use PromotionsBundle\Jobs\FirePromotionsActivity;
use WechatBundle\Services\OpenPlatform;
use WorkWechatBundle\Entities\WorkWechatRel;
use DistributionBundle\Services\DistributorService;
use CompanysBundle\Ego\GenericUser as GenericUser;
use WorkWechatBundle\Services\WorkWechatRelService;
use MembersBundle\Jobs\BindSalseperson;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use MembersBundle\Services\MembersProtocolLogService;
use KaquanBundle\Entities\VipGradeRelUser;
use MembersBundle\Services\ShopRelMemberService;
use ShuyunBundle\Services\MembersService as ShuyunMembersService;
use ThirdPartyBundle\Services\DmCrm\MemberService as DmMemberService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;
use ThirdPartyBundle\Services\DmCrm\DmService;

class MemberService
{
    use GetCodeTrait;

    /**
     * @var \MembersBundle\Repositories\MembersRepository
     */
    public $membersRepository;

    /** @var \MembersBundle\Repositories\MembersInfoRepository */
    public $membersInfoRepository;

    /**
     * MemberService 构造函数.
     */
    public function __construct()
    {
        $this->membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $this->membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
    }

    /**
     * 根据手机号来获取用户信息
     * @param int $companyId
     * @param string $mobile
     * @return array
     */
    public function getInfoByMobile(int $companyId, string $mobile): array
    {
        return $this->membersRepository->get([
            'company_id' => $companyId,
            'mobile' => $mobile,
        ]);
    }

    /**
     * 根据用户iD
     *
     * @param int $companyId
     * @param array $userIds
     * @return mixed
     */
    public function getUsernameByUserIds(int $companyId, array $userIds)
    {
        if (empty($userIds)) {
            return [];
        }

        $filter = [
            'company_id' => $companyId,
            'user_id' => $userIds
        ];
        $fields = 'username,user_id';
        return $this->membersInfoRepository->getListNotPagination($filter, $fields);
    }

    /**
     * 新增会员
     */
    public function createMember($params, $isUpdatePassword = false)
    {
        $dmService = new DmService($params['company_id']);
        if ($dmService->isOpen) {
            return $this->dmCreateMember($params, $isUpdatePassword);
        }
        $memberInfo = [
            'mobile' => $params['mobile'],
            'region_mobile' => $params['region_mobile'] ?? '',
            'mobile_country_code' => $params['mobile_country_code'] ?? '',
            'company_id' => $params['company_id'],
            'wxa_appid' => $params['wxa_appid'] ?? '',
            'authorizer_appid' => $params['authorizer_appid'] ?? '',
            'alipay_appid' => $params['alipay_appid'] ?? '',
            'sex' => $params['sex'] ?? 0,
            'username' => $params['username'] ?? randValue(8),
            'avatar' => $params['avatar'] ?? '',
            'habbit' => isset($params['habbit']) ? $params['habbit'] : [],
            'email' => isset($params['email']) ? $params['email'] : null,
            'income' => isset($params['income']) ? $params['income'] : null,
            'address' => isset($params['address']) ? $params['address'] : null,
            'industry' => isset($params['industry']) ? $params['industry'] : null,
            'birthday' => isset($params['birthday']) ? $params['birthday'] : null,
            'edu_background' => isset($params['edu_background']) ? $params['edu_background'] : null,
        ];

        if ($isUpdatePassword) {
            $memberInfo['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
        }

        $isNew = false;
        $isUploadMember = false;
        $filter = [
            'company_id' => $params['company_id'],
            'mobile' => $params['mobile'],
        ];
        $workWechatRelRepository = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $memberCardService = new MemberCardService();
            $defaultGradeInfo = $memberCardService->getDefaultGradeByCompanyId($params['company_id']);
            if (!$defaultGradeInfo) {
                throw new ResourceException(trans('MembersBundle/Members.missing_default_level'));
            }
            $member = $this->membersRepository->get($filter);
            if ($member) {
                if (!$member['user_card_code']) {
                    $memberInfo['user_card_code'] = $this->getCode();
                }
                if (!$member['grade_id'] || $member['grade_id'] == -1) {
                    $memberInfo['grade_id'] = $defaultGradeInfo['grade_id'];
                }
                $result = $this->membersRepository->update($memberInfo, $filter);
                $updateFilter = [
                    'user_id' => $member['user_id'],
                    'company_id' => $member['company_id'],
                ];
                $memberInfo['user_id'] = $member['user_id'];
                $infoData = $this->membersInfoRepository->updateOneBy($updateFilter, $memberInfo);
                $otherParams = $infoData['other_params'];
                if (isset($otherParams['is_upload_member']) && $otherParams['is_upload_member']) {
                    $isNew = true;
                    $isUploadMember = true;
                }
            } else {
                $isNew = true;
                $memberInfo['grade_id'] = $defaultGradeInfo['grade_id'];
                $memberInfo['user_card_code'] = $this->getCode();

                $memberInfo['inviter_id'] = $params['inviter_id'] ?? 0;

                // 微信来源的用户，如果force_password不为1，会默认生成随机密码
                // H5微信授权登录后，新用户需要手动输入密码才能创建用户
                $forcePassword = (int)($params["force_password"] ?? 0);
                if (($params['api_from'] == 'wechat' || $params['auth_type'] == 'wxapp' || $params['auth_type'] == 'aliapp') && $forcePassword === 0) {
                    $params['password'] = substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10); // 生成随机密码
                }
                $memberInfo['password'] = password_hash($params['password'], PASSWORD_DEFAULT);

                $memberInfo['source_from'] = $params['source_from'] ?? "default";

                // 记录千人千码来源
                $memberInfo['source_id'] = $params['source_id'] ?? 0;
                $memberInfo['monitor_id'] = $params['monitor_id'] ?? 0;
                $memberInfo['latest_source_id'] = $params['latest_source_id'] ?? 0;
                $memberInfo['latest_monitor_id'] = $params['latest_monitor_id'] ?? 0;
                app('log')->debug('推荐关系跟踪 memberInfo'.var_export($memberInfo, 1));
                $result = $this->membersRepository->create($memberInfo);
                $memberInfo['user_id'] = $result['user_id'];
                $memberInfo['other_params'] = json_encode([]);
                $this->membersInfoRepository->create($memberInfo);
            }


            // 注销会员是否享受新客营销
            $ifRegisterPromotion = true;
            $member_logout_config = ProtocolService::TYPE_MEMBER_LOGOUT_CONFIG;
            $privacyData = (new ProtocolService($params['company_id']))->get([$member_logout_config]);
            if (empty($privacyData[$member_logout_config]['new_rights'])) {
                $membersDeleteRecordRepository = app('registry')->getManager('default')->getRepository(MembersDeleteRecord::class);
                $membersDeleteRecord = $membersDeleteRecordRepository->getInfo(['company_id' => $params['company_id'],'mobile' => $params['mobile']]);
                if (!empty($membersDeleteRecord)) {
                    $ifRegisterPromotion = false;
                }
            }

            if ($isNew && $result['user_id']) {
                // 根据 work_userid 从 导购员表 获取导购员信息
                if (isset($params['work_userid']) && $params['work_userid']) {
                    $salespersonService = new SalespersonService();
                    $salesperson = $salespersonService->getInfo(['company_id' => $params['company_id'], 'work_userid' => $params['work_userid']]);
                    if ($salesperson) {
                        $params['salesperson_id'] = $salesperson['salesperson_id'];
                        // 根据导购员id 从 导购员店铺关联表 获取店铺信息
                        $shopRelSalespersonRepository = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
                        $shopRelSalesperson = $shopRelSalespersonRepository->getInfo(['salesperson_id' => $salesperson['salesperson_id']]);
                        if ($shopRelSalesperson) {
                            $params['distributor_id'] = $shopRelSalesperson['shop_id'];
                            app('log')->info('导购员信息 shopRelSalesperson===>'.json_encode($shopRelSalesperson) .':salesperson_id===>'.$salesperson['salesperson_id'] );
                        }
                    }   
                    app('log')->info('导购员信息 salesperson===>'.json_encode($salesperson) .':work_userid===>'.$params['work_userid'] );   
                }
                // 创建分销员数据
                $promoterService = new PromoterService();
                // 需要创建B级推广员
                if (isset($params['puid']) && intval($params['puid']) > 0) {
                    $memberInfo['puid'] = $params['puid'];
                }
                app('log')->info('推广员发展下级 推荐关系跟踪 memberInfo===>'.var_export($memberInfo, true));
                $promoterService->create($memberInfo);

                //记录新会员和店铺或导购的关系
                $dataParams = [
                    'distributor_id' => $params['distributor_id'] ?? 0,
                    'user_id' => $result['user_id'],
                    'company_id' => $params['company_id'],
                    'unionid' => $params['unionid'],
                    'inviter_id' => $memberInfo['inviter_id'] ?? 0,
                    'salesperson_id' => $params['salesperson_id'] ?? 0,
                ];
                $distributorUserService = new DistributorUserService();
                $distributorUserService->createData($dataParams);

                if ($params['distributor_id'] ?? 0) {
                    $dataParams = [
                        'user_id' => $result['user_id'],
                        'company_id' => $params['company_id'],
                        'shop_id' => $params['distributor_id'],
                        'shop_type' => 'distributor',
                    ];
                    $shopRelMemberService = new ShopRelMemberService();
                    $shopRelMemberService->create($dataParams);
                }

                if (($params['salesperson_id'] ?? 0) > 0) {
                    $data = [
                        'company_id' => $params['company_id'],
                        'salesperson_id' => intval($params['salesperson_id']),
                        'unionid' => $params['unionid'],
                        'user_id' => $result['user_id'],
                        'is_friend' => 0,
                        'is_bind' => 1,
                        'bound_time' => time(),
                        'add_friend_time' => 0
                    ];
                    $workWechatRelRepository->create($data);

                    //记录导购变更日志
                    $logData = $data;
                    $logData['is_first_bind'] = true;
                    $workWechatRelService = new WorkWechatRelService();
                    $workWechatRelService->saveWorkWechatRelLogs($logData);

                    // 存在导购id才会计算完成导购拉新任务
                    $SalespersonTaskRecordService = new SalespersonTaskRecordService();
                    $salespersonTaskParams = [
                        'company_id' => $params['company_id'],
                        'salesperson_id' => $params['salesperson_id'],
                        'user_id' => $result['user_id'],
                    ];
                    $SalespersonTaskRecordService->completeNewUser($salespersonTaskParams);
                }

                if (!$isUploadMember) {
                    //记录每天新增会员数
                    $redisKey = "Member:" . $params['company_id'] . ":" . date('Ymd');
                    app('redis')->sadd($redisKey, $result['user_id']);
                }
            }

            //关联表
            if ($params['api_from'] == 'wechat' || $params['auth_type'] == 'wxapp' || $params['auth_type'] == 'wx_offiaccount' || $params['auth_type'] == 'aliapp') { // 本地注册会员则不用创建关联信息
                $this->createMemberAssociations((int)$params['company_id'], (int)$result['user_id'], (string)$params['unionid'], $params['user_type'] ?? 'wechat');
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        //params
        app('log')->info('创建会员 params===>'.json_encode($params));

        if ($isNew) {
            $eventData = [
                'user_id' => $result['user_id'],
                'company_id' => $params['company_id'],
                'mobile' => $params['mobile'],
                'openid' => $params['open_id'],
                'wxa_appid' => $params['wxa_appid'] ?? ''
            ];
            $eventData['inviter_id'] = $params['inviter_id'] ?? 0;
            $eventData['distributor_id'] = 0;
            if (isset($params['distributor_id']) && $params['distributor_id']) {
                $eventData['distributor_id'] = $params['distributor_id'];
            }
            // 千人千码统计参数
            $eventData['source_id'] = $params['source_id'] ?? 0;
            $eventData['monitor_id'] = $params['monitor_id'] ?? 0;
            $eventData['salesperson_id'] = $params['salesperson_id'] ?? 0;
            $eventData['if_register_promotion'] = $ifRegisterPromotion;
            event(new CreateMemberSuccessEvent($eventData));

            if (($params['work_userid'] ?? '') && ($params['channel'] ?? 0) == 1) {
                app('log')->info('memberservice创建会员 绑定导购:2 params===>'.__FUNCTION__.':'.__FILE__.':'.__LINE__.json_encode($params));
                $queue = (new BindSalseperson($params['company_id'], $params['unionid'], $params['work_userid'], 1, $params['mobile'], $result['user_id']))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
            }

            // 删除导入初始化
            if ($isUploadMember) {
                unset($otherParams['is_upload_member']);

                $this->membersInfoRepository->updateOneBy(['user_id' => $result['user_id']], ['other_params' => json_encode($otherParams)]);
            }
        }

        return $result;
    }

    public function dmCreateMember($params, $isUpdatePassword = false)
    {
        // 查询达摩CRM会员
        $dmMemberService = new DmMemberService($params['company_id']);
        $dmMemberInfo = $dmMemberService->getMemberInfo(['mobile' => $params['mobile']]);
        if (!$dmMemberInfo) {
            $params['username'] = randValue(8);
            // 根据 work_userid 从 导购员表 获取导购员信息
            if (isset($params['work_userid']) && $params['work_userid']) {
                app('log')->info('dmCreateMember::导购员信息::params===>'.json_encode($params) .':work_userid===>'.$params['work_userid'] );
                $salespersonService = new SalespersonService();
                $salesperson = $salespersonService->getInfo(['company_id' => $params['company_id'], 'work_userid' => $params['work_userid']]);
                if ($salesperson) {
                    app('log')->info('dmCreateMember::导购员信息::salesperson===>'.json_encode($salesperson) .':work_userid===>'.$params['work_userid'] );
                    $params['salesperson_id'] = $salesperson['salesperson_id'];
                    $params['openClerkCode'] = $params['mainClerkCode'] = $salesperson['work_userid'];
                }
            }
            if (isset($params['distributor_id']) && $params['distributor_id']) {
                $distributorService = new DistributorService();
                $distributorInfo = $distributorService->getInfoSimple(['company_id' => $params['company_id'], 'distributor_id' => $params['distributor_id']]);
                app('log')->info('dmCreateMember::门店信息::distributorInfo===>'.json_encode($distributorInfo) .':distributor_id===>'.$params['distributor_id'] );
                $params['openStoreCode'] = $params['mainStoreCode'] = $distributorInfo['shop_code'] ?? '';
            }
            // 创建达摩CRM会员
            $dmMemberInfo = $dmMemberService->memberRegister($params);
        }
        if (!$dmMemberInfo) {
            throw new ResourceException('操作失败，请联系客服');
        }
        
        $memberInfo = [
            'mobile' => $params['mobile'],
            'region_mobile' => $params['region_mobile'] ?? '',
            'mobile_country_code' => $params['mobile_country_code'] ?? '',
            'company_id' => $params['company_id'],
            'wxa_appid' => $params['wxa_appid'] ?? '',
            'authorizer_appid' => $params['authorizer_appid'] ?? '',
            'alipay_appid' => $params['alipay_appid'] ?? '',
            'sex' => $dmMemberInfo['sex'] ?? 0,
            'username' => $dmMemberInfo['name'] ?? $params['username'],
            'avatar' => $params['avatar'] ?? '',
            'habbit' => isset($params['habbit']) ? $params['habbit'] : [],
            'email' => isset($params['email']) ? $params['email'] : null,
            'income' => isset($params['income']) ? $params['income'] : null,
            'address' => isset($params['address']) ? $params['address'] : null,
            'industry' => isset($params['industry']) ? $params['industry'] : null,
            'birthday' => isset($dmMemberInfo['birthday']) ? date('Y-m-d', $dmMemberInfo['birthday']/1000) : null,
            'edu_background' => isset($params['edu_background']) ? $params['edu_background'] : null,
            'dm_member_id' => $dmMemberInfo['memberId'] ?? '',
            'user_card_code' => $dmMemberInfo['cardNo'] ?? '',
            'dm_card_no' => $dmMemberInfo['cardNo'] ?? '',
        ];

        if ($isUpdatePassword) {
            $memberInfo['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
        }

        $isNew = false;
        $isUploadMember = false;
        $filter = [
            'company_id' => $params['company_id'],
            'mobile' => $params['mobile'],
        ];
        $workWechatRelRepository = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $memberCardService = new MemberCardService();
            if ($dmMemberInfo['gradeCode'] ?? 0) {
                $defaultGradeInfo = $memberCardService->getGradeInfo(['company_id' => $params['company_id'], 'dm_grade_code' => $dmMemberInfo['gradeCode']]);
            } else {
                $defaultGradeInfo = $memberCardService->getDefaultGradeByCompanyId($params['company_id']);
            }
            if (!$defaultGradeInfo) {
                throw new ResourceException('缺少默认等级');
            }
            $member = $this->membersRepository->get($filter);
            if ($member) {
                if (!$member['user_card_code']) {
                    $memberInfo['user_card_code'] = $this->getCode();
                }
                if (!$member['grade_id'] || $member['grade_id'] == -1) {
                    $memberInfo['grade_id'] = $defaultGradeInfo['grade_id'];
                }
                $result = $this->membersRepository->update($memberInfo, $filter);
                $updateFilter = [
                    'user_id' => $member['user_id'],
                    'company_id' => $member['company_id'],
                ];
                $memberInfo['user_id'] = $member['user_id'];
                $infoData = $this->membersInfoRepository->updateOneBy($updateFilter, $memberInfo);
                $otherParams = $infoData['other_params'];
                if (isset($otherParams['is_upload_member']) && $otherParams['is_upload_member']) {
                    $isNew = true;
                    $isUploadMember = true;
                }
            } else {
                $isNew = true;
                $memberInfo['grade_id'] = $defaultGradeInfo['grade_id'];

                $memberInfo['inviter_id'] = $params['inviter_id'] ?? 0;

                // 微信来源的用户，如果force_password不为1，会默认生成随机密码
                // H5微信授权登录后，新用户需要手动输入密码才能创建用户
                $forcePassword = (int)($params["force_password"] ?? 0);
                if (($params['api_from'] == 'wechat' || $params['auth_type'] == 'wxapp' || $params['auth_type'] == 'aliapp') && $forcePassword === 0) {
                    $params['password'] = substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10); // 生成随机密码
                }
                $memberInfo['password'] = password_hash($params['password'], PASSWORD_DEFAULT);

                $memberInfo['source_from'] = $params['source_from'] ?? "default";

                // 记录千人千码来源
                $memberInfo['source_id'] = $params['source_id'] ?? 0;
                $memberInfo['monitor_id'] = $params['monitor_id'] ?? 0;
                $memberInfo['latest_source_id'] = $params['latest_source_id'] ?? 0;
                $memberInfo['latest_monitor_id'] = $params['latest_monitor_id'] ?? 0;
                app('log')->debug('推荐关系跟踪 memberInfo'.var_export($memberInfo, 1));
                $result = $this->membersRepository->create($memberInfo);
                $memberInfo['user_id'] = $result['user_id'];
                $memberInfo['other_params'] = json_encode([]);
                $this->membersInfoRepository->create($memberInfo);
            }

            // 注销会员是否享受新客营销
            $ifRegisterPromotion = true;
            $member_logout_config = ProtocolService::TYPE_MEMBER_LOGOUT_CONFIG;
            $privacyData = (new ProtocolService($params['company_id']))->get([$member_logout_config]);
            if (empty($privacyData[$member_logout_config]['new_rights'])) {
                $membersDeleteRecordRepository = app('registry')->getManager('default')->getRepository(MembersDeleteRecord::class);
                $membersDeleteRecord = $membersDeleteRecordRepository->getInfo(['company_id' => $params['company_id'],'mobile' => $params['mobile']]);
                if (!empty($membersDeleteRecord)) {
                    $ifRegisterPromotion = false;
                }
            }

            if ($isNew && $result['user_id']) {
                // 创建分销员数据
                $promoterService = new PromoterService();
                // 需要创建B级推广员
                if (isset($params['puid']) && intval($params['puid']) > 0) {
                    $memberInfo['puid'] = $params['puid'];
                }
                app('log')->info('推广员发展下级 推荐关系跟踪 memberInfo===>'.var_export($memberInfo, true));
                $promoterService->create($memberInfo);

                //记录新会员和店铺或导购的关系
                $dataParams = [
                    'distributor_id' => $params['distributor_id'] ?? 0,
                    'user_id' => $result['user_id'],
                    'company_id' => $params['company_id'],
                    'unionid' => $params['unionid'],
                    'inviter_id' => $memberInfo['inviter_id'] ?? 0,
                    'salesperson_id' => $params['salesperson_id'] ?? 0,
                ];
                $distributorUserService = new DistributorUserService();
                $distributorUserService->createData($dataParams);

                if ($params['distributor_id'] ?? 0) {
                    $dataParams = [
                        'user_id' => $result['user_id'],
                        'company_id' => $params['company_id'],
                        'shop_id' => $params['distributor_id'],
                        'shop_type' => 'distributor',
                    ];
                    $shopRelMemberService = new ShopRelMemberService();
                    $shopRelMemberService->create($dataParams);
                }

                if (($params['salesperson_id'] ?? 0) > 0) {
                    $data = [
                        'company_id' => $params['company_id'],
                        'salesperson_id' => intval($params['salesperson_id']),
                        'unionid' => $params['unionid'],
                        'user_id' => $result['user_id'],
                        'is_friend' => 0,
                        'is_bind' => 1,
                        'bound_time' => time(),
                        'add_friend_time' => 0
                    ];
                    $workWechatRelRepository->create($data);

                    //记录导购变更日志
                    $logData = $data;
                    $logData['is_first_bind'] = true;
                    $workWechatRelService = new WorkWechatRelService();
                    $workWechatRelService->saveWorkWechatRelLogs($logData);

                    // 存在导购id才会计算完成导购拉新任务
                    $SalespersonTaskRecordService = new SalespersonTaskRecordService();
                    $salespersonTaskParams = [
                        'company_id' => $params['company_id'],
                        'salesperson_id' => $params['salesperson_id'],
                        'user_id' => $result['user_id'],
                    ];
                    $SalespersonTaskRecordService->completeNewUser($salespersonTaskParams);
                }

                if (!$isUploadMember) {
                    //记录每天新增会员数
                    $redisKey = "Member:" . $params['company_id'] . ":" . date('Ymd');
                    app('redis')->sadd($redisKey, $result['user_id']);
                }
            }

            //关联表
            if ($params['api_from'] == 'wechat' || $params['auth_type'] == 'wxapp' || $params['auth_type'] == 'wx_offiaccount' || $params['auth_type'] == 'aliapp') { // 本地注册会员则不用创建关联信息
                $this->createMemberAssociations((int)$params['company_id'], (int)$result['user_id'], (string)$params['unionid'], $params['user_type'] ?? 'wechat');
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        if ($isNew) {
            $eventData = [
                'user_id' => $result['user_id'],
                'company_id' => $params['company_id'],
                'mobile' => $params['mobile'],
                'openid' => $params['open_id'],
                'wxa_appid' => $params['wxa_appid'] ?? ''
            ];
            $eventData['inviter_id'] = $params['inviter_id'] ?? 0;
            $eventData['distributor_id'] = 0;
            if (isset($params['distributor_id']) && $params['distributor_id']) {
                $eventData['distributor_id'] = $params['distributor_id'];
            }
            // 千人千码统计参数
            $eventData['source_id'] = $params['source_id'] ?? 0;
            $eventData['monitor_id'] = $params['monitor_id'] ?? 0;
            $eventData['salesperson_id'] = $params['salesperson_id'] ?? 0;
            $eventData['if_register_promotion'] = $ifRegisterPromotion;
            event(new CreateMemberSuccessEvent($eventData));

            if (($params['work_userid'] ?? '') && ($params['channel'] ?? 0) == 1) {
                app('log')->info('memberservice创建会员 绑定导购:1 params===>'.__FUNCTION__.':'.__FILE__.':'.__LINE__.json_encode($params));
                $queue = (new BindSalseperson($params['company_id'], $params['unionid'], $params['work_userid'], 1, $params['mobile']))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
            }

            // 删除导入初始化
            if ($isUploadMember) {
                unset($otherParams['is_upload_member']);

                $this->membersInfoRepository->updateOneBy(['user_id' => $result['user_id']], ['other_params' => json_encode($otherParams)]);
            }
        }
        // 绑定达摩CRM会员
        $dmMemberService->memberBind(['mobile' => $params['mobile']]);
        return $result;
        
    }

    /**
     * 获取用户的关联表信息
     * @param int $companyId 企业id
     * @param string $userType 用户类型
     * @param string $unionId unionid
     * @return array
     */
    public function getMembersAssociation(int $companyId, string $userType, string $unionId, int $userId): array
    {
        $membersAssoc = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        return $membersAssoc->get([
            'user_type' => $userType,
            'company_id' => $companyId,
            'unionid' => $unionId,
            'user_id' => $userId,
        ]);
    }

    /**
     * 获取用户的关联表信息
     * @param int $companyId 企业id
     * @param string $userType 用户类型
     * @param string $userId 会员ID
     * @return array
     */
    public function getMembersAssociationByUserid(int $companyId, string $userType, int $userId): array
    {
        $membersAssoc = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        return $membersAssoc->get([
            'user_type' => $userType,
            'company_id' => $companyId,
            // 'unionid' => $unionId,
            'user_id' => $userId,
        ]);
    }

    /**
     * 会员注册,根据传入的导购员Id，查询会员注册的店铺Id
     * @param $company_id :企业ID
     * @param $salesperson_id :导购员Id
     * @return 店铺Id 没有返回空值
     */
    public function getDistributorId($company_id, $salesperson_id)
    {
        app('log')->info('没有店铺Id,查询店铺Id company_id:' . $company_id . ',salesperson_id:' . $salesperson_id);
        if ($salesperson_id) {
            // 有导购员id,查询导购员所在店铺Id
            $filter = [
                'salesperson_id' => $salesperson_id,
                'company_id' => $company_id,
                'store_type' => 'distributor'
            ];
            $shopRelSalespersonRepository = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
            $relSalespersonInfo = $shopRelSalespersonRepository->getInfo($filter);
            return $relSalespersonInfo['shop_id'] ?? '';
        } else {
            // 没有导购员id,查询企业的默认门店
            $distributorService = new DistributorService();
            $distributor_id = $distributorService->getDefaultDistributorId($company_id);
            return $distributor_id ?? '';
        }
    }

    public function getMemberInfoList($filter, $page = 1, $pageSize = 100, $orderBy = ['user_id' => 'DESC'])
    {
        $result = $this->membersInfoRepository->lists($filter, $orderBy, $pageSize, $page);
        return $result;
    }

    public function getMemberInfoData($userId, $companyId)
    {
        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId,
        ];
        $info = $this->membersInfoRepository->getInfo($filter);

        $regSettinService = new MemberRegSettingService();
        $regSetting = $regSettinService->getRegItem($companyId);
        $regSetting = $regSetting['setting'] ?? [];
        if (!$regSetting) {
            $regSetting['username'] = [
                'name' => "姓名",
                'is_open' => true,
                'element_type' => "input",
                'is_required' => true,
            ];
        }

        foreach ($info as $key => $val) {
            if (!($regSetting[$key] ?? null) || !$val) {
                continue;
            }
            $val = ($val && is_array($val)) ? $val : json_decode($val, true);
            if ($key == 'habbit' && is_array($val)) {
                $val = is_array($val) ? $val : json_decode($val, true);
                foreach ($val as $v) {
                    if (isset($v['ischecked']) && $v['ischecked'] == 'true') {
                        $habbit[] = $v['name'];
                    } elseif (!isset($v['name']) && !isset($v['ischecked']) && is_string($v)) {
                        $habbit[] = $v;
                    }
                }
                $info[$key] = $habbit ?? [];
                continue;
            }
        }
        $result['info'] = $info;
        $result['registerSetting'] = $regSetting;
        return $result;
    }

    /**
     * 更新微信会员总消费额
     */
    public function updateMemberConsumption($userId, $companyId, $pay_fee)
    {
        // 数云模式，不执行
        if (config('common.oem-shuyun')) {
            // 会员的等级变更使用数云，这里不做处理
            return true;
        }
        
        // 达摩crm, 等级不处理
        $ns = new DmCrmSettingService();
        if ($ns->getDmCrmSetting($companyId)['is_open'] ?? '') {

            return true;
        }

        $filter = ['user_id' => $userId, 'company_id' => $companyId];
        $memberInfo = $this->getMemberInfo($filter);
        if (!$memberInfo) {
            return false;
        }
        $totalConsumption = $this->getTotalConsumption($userId);
        if ($totalConsumption + $pay_fee > 0) {
            app('redis')->connection('members')->incrby($this->genReidsId($userId), $pay_fee);
        } else {
            app('redis')->connection('members')->set($this->genReidsId($userId), 0);
        }
        $totalConsumption = $this->getTotalConsumption($userId);

        //判断是否要升级
        $memberCardService = new MemberCardService();
        $gradeList = $memberCardService->getGradeListByCompanyId($companyId);
        //按照消费额度从大到小
        krsort($gradeList);
        foreach ($gradeList as $key => $value) {
            $condition = $value['promotion_condition']['total_consumption'] ?? 0;
            $condition = bcmul($condition, 100);
            if ($totalConsumption >= $condition && $memberInfo['grade_id'] < $value['grade_id']) {
                $nextGradeId = $value['grade_id'];
                $updateInfo = ['grade_id' => $nextGradeId];
                $this->updateMemberInfo($updateInfo, $filter);

                // 会员等级提升，触发优惠活动
                $activityMemberInfo['grade_id'] = $nextGradeId;
                $activityMemberInfo['user_id'] = $userId;
                $activityMemberInfo['mobile'] = $memberInfo['mobile'];
                $activityMemberInfo['grade_name'] = $value['grade_name'];
                $job = (new FirePromotionsActivity($companyId, $activityMemberInfo, 'member_upgrade'));
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
                (new PackageSetService())->triggerPackage((int)$companyId, (int)$userId, (int)$nextGradeId, 'grade', true);
                break;
            }
        }

        return true;
    }

    public function getTotalConsumption($userId)
    {
        $result = app('redis')->connection('members')->get($this->genReidsId($userId));
        return $result ? $result : 0;
    }

    /**
     * 获取redis存储的ID
     */
    public function genReidsId($userId)
    {
        return 'totalConsumption:' . sha1($userId);
    }

    public function getMemberInfo($filter, $getInfo = false)
    {
        $member = $this->membersRepository->get($filter);
        $result = $member;
        $requestFields = [];
        // 需要脱敏的字段
        $datapassRequestFields = [];
        if ($member && isset($member['user_id']) && $member['user_id']) {
            $memberFilter = [
                'company_id' => $member['company_id'],
                'user_id' => $member['user_id']
            ];
            $info = $this->membersInfoRepository->getInfo($memberFilter);
            // 如果存在用户相信信息
            if (!empty($info)) {
                $info["other_params"] = (array)jsonDecode($info["other_params"] ?? null);
                // 前端透传的参数
                $info["isGetWxInfo"] = (bool)($info["other_params"]["isGetWxInfo"] ?? false);
                if ($getInfo) {
                    // 获取验证字段
                    $requestValidateList = (new ConfigRequestFieldsService())->getListAndHandleSettingFormat((int)$info['company_id'], ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO);
                    foreach ($requestValidateList as $keyName => $item) {
                        // 根据数据库中定义的字段名去member和info里获取实际的值，如果都拿不到，则去info的other_params.custom_data里去取
                        if (isset($member[$keyName])) {
                            $requestFields[$keyName] = $member[$keyName];
                        } elseif (isset($info[$keyName])) {
                            $requestFields[$keyName] = $info[$keyName];
                        } else {
                            $requestFields[$keyName] = $info["other_params"]["custom_data"][$keyName] ?? null;
                        }

                        $fieldType = $item["field_type"] ?? null;
                        // 如果字段是checkbox则只把选中的值拼接成字符串
                        if ($fieldType == ConfigRequestFieldsService::FIELD_TYPE_CHECKBOX && !empty($requestFields[$keyName])) {
                            // 获取已经选中的选项
                            $checkedItemList = [];
                            $requestFields[$keyName] = (array)jsonDecode($requestFields[$keyName]);
                            foreach ($requestFields[$keyName] as &$checkboxItem) {
                                if (!empty($checkboxItem["ischecked"]) && ($checkboxItem["ischecked"] === "true" || $checkboxItem["ischecked"] === true)) {
                                    $checkboxItem["ischecked"] = true;
                                    $checkedItemList[] = $checkboxItem["name"] ?? "";
                                } else {
                                    $checkboxItem["ischecked"] = false;
                                }
                            }

                            // 转成字符串，并去掉前后的 逗号
                            $checkedItemString = trim((string)implode(",", $checkedItemList), ",");
                            if (isset($member[$keyName])) {
                                $member[$keyName] = $checkedItemString;
                            } elseif (isset($info[$keyName])) {
                                $info[$keyName] = $checkedItemString;
                            } else {
                                $info["other_params"]["custom_data"][$keyName] = $checkedItemString;
                            }
                        }
                        if ($item['field_type'] == ConfigRequestFieldsService::FIELD_TYPE_MOBILE) {
                            $datapassRequestFields['mobile'][] = $keyName;
                        }
                    }

                    (new ConfigRequestFieldsService())->transformGetDescByValue((int)$member['company_id'], ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO, $requestFields);
                }
            }
            $result = array_merge($member, $info);
            $result["requestFields"] = $requestFields;
            $result['datapassRequestFields'] = $datapassRequestFields;

            if ($getInfo) {
//                $regSettinService = new MemberRegSettingService();
//                $regSetting = $regSettinService->getRegItem($member['company_id'])['setting'];
//                foreach ($info as $key => $val) {
//                    if (!($regSetting[$key] ?? null) || (!$val && $val !== '0')) {
//                        continue;
//                    }
//                    $data = $regSetting[$key];
//                    $val = ($val && is_array($val)) ? $val : json_decode($val, true);
//                    if ($key == 'habbit' && is_array($val)) {
//                        $val = is_array($val) ? $val : json_decode($val, true);
//                        foreach ($val as $v) {
//                            if (isset($v['ischecked']) && $v['ischecked'] == 'true') {
//                                $habbit[] = $v['name'];
//                            } else if (!isset($v['name']) && !isset($v['ischecked']) && is_string($v)) {
//                                $habbit[] = $v;
//                            }
//                        }
//                        if ($habbit ?? []) {
//                            $info[$key] = implode(',', $habbit);
//                        }
//                        continue;
//                    }
//
//                    if (isset($data['items']) && is_array($data['items']) && is_numeric($val)) {
//                        $info[$key] = $data['items'][$val] ?? '';
//                    }
//                }
            }
//            $result = array_merge($member, $info);
//            $result["requestFields"] = $requestFields;
        }
        return $result;
    }

    // 获取用户信息通过dm_card_no
    public function getMemberInfoByDmCardNo($dm_card_no, $company_id)
    {
        $filterInfo = [
            'company_id' => $company_id,
            'dm_card_no' => $dm_card_no,
        ];
        $memberInfo = $this->membersInfoRepository->getInfo($filterInfo);
        $filter = [
            'company_id' => $company_id,
            'user_id' => $memberInfo['user_id'] ?? 0
        ];
        $result = $this->getMemberInfo($filter);

        return $result;
    }

    /**
     * 更新微信会员
     */
    public function updateMemberInfo($params, $filter)
    {
        $member = $this->getMemberInfo($filter);
        if (!$member) {
            throw new Exception(trans('MembersBundle/Members.user_not_exists'));
        }
        if (isset($params['disabled'])) {
            $params['disabled'] = $params['disabled'] ? 1 : 0;
        }
        $result = $this->membersRepository->update($params, $filter);
        // 更新info数据
        if (isset($params['name'])) {
            $paramsInfo = ['name' => $params['name']];
            $filterInfo['user_id'] = $member['user_id'];
            $this->updateMemberinfoInfo($paramsInfo, $filterInfo);
        }
        return $result;
    }

    public function updateMemberinfoInfo($params, $filter)
    {
        $result = $this->membersInfoRepository->updateOneBy($filter, $params);

        return $result;
    }

    public function getList($page = 1, $limit = 100, $filter = [])
    {
        $offset = ($page - 1) * $limit;
        $result = $this->membersRepository->getList($filter, $offset, $limit);

        return $result;
    }

    public function getUserIdByMobile($mobile, $companyId)
    {
        $filter = [
            'mobile' => $mobile,
            'company_id' => $companyId,
        ];
        $data = $this->membersRepository->get($filter);
        return $data ? $data['user_id'] : null;
    }

    public function getUserIdByMobile2($mobile)
    {
        $filter = [
            'mobile' => $mobile,
        ];
        $member = $this->membersRepository->lists($filter);
        if (!$member) {
            return [];
        }
        return array_column($member['list'], 'user_id');
    }

    /**
     * 根据多个mobile获取user_id
     * @param  array $mobiles    会员手机号
     * @param  string $companyId 企业ID
     * @return [type]            [description]
     */
    public function getUserIdsByMobiles($mobiles, $companyId)
    {
        $filter = [
            'company_id' => $companyId,
            'mobile' => $mobiles,
        ];
        $member = $this->membersRepository->lists($filter);
        if (!$member) {
            return [];
        }
        return array_column($member['list'], 'user_id');
    }

    public function getUserIdByUsername($username, $companyId)
    {
        $filter = [
            'company_id' => $companyId,
            'username' => $username,
        ];
        $member = $this->membersInfoRepository->lists($filter, ["user_id" => "DESC"], -1);
        if (!$member) {
            return [];
        }
        return array_column($member['list'], 'user_id');
    }

    public function getMobileByUserIds($companyId, $userIds)
    {
        return $this->membersRepository->getMobileByUserIds($companyId, $userIds);
    }

    public function getMobileByUserId($userId, $companyId)
    {
        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId,
        ];
        $data = $this->membersRepository->get($filter);
        return $data ? $data['mobile'] : null;
    }

    public function getinviterByUserId($userId, $companyId)
    {
        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId,
        ];
        $data = $this->membersRepository->get($filter);
        return $data ? $data['inviter_id'] : null;
    }

    public function generateBarCode($content)
    {
        $content = 'MC_' . $content;
        $dns1d = app('DNS1D')->getBarcodePNG($content, "C93", 1, 70);
        $dns2d = app('DNS2D')->getBarcodePNG($content, "QRCODE", 120, 120);

        $result = [
            'barcode_url' => 'data:image/jpg;base64,' . $dns1d,
            'qrcode_url' => 'data:image/jpg;base64,' . $dns2d
        ];

        return $result;
    }

    public function memberInfoUpdate($params, $filter)
    {
        $info = $this->membersInfoRepository->getInfo($filter);
        if (!$info) {
            throw new Exception(trans('MembersBundle/Members.user_not_exists'));
        }

        // other_params是追加更新，如果是覆盖更新，会出现参数丢失的问题
        if (isset($params["other_params"])) {
            if (!is_array($params["other_params"])) {
                $params["other_params"] = (array)jsonDecode($params["other_params"]);
            }
            $otherParams = (array)jsonDecode($info["other_params"] ?? null);
            // 如果自定义的验证参数存在旧数据, 则追加自定义验证数据
            if (isset($otherParams["custom_data"]) && isset($params["other_params"]["custom_data"])) {
                $params["other_params"]["custom_data"] = array_merge((array)$otherParams["custom_data"], $params["other_params"]["custom_data"]);
                unset($otherParams["custom_data"]);
            }
            $params["other_params"] = array_merge($otherParams, $params["other_params"]);
        } else {
            $params["other_params"] = [];
        }
        $params["other_params"] = json_encode($params["other_params"], JSON_UNESCAPED_UNICODE);

        $result = $this->membersInfoRepository->updateOneBy($filter, $params);
        return $result;
    }

    /**
     * 同步数云，会员修改
     * @param  string $companyId 企业ID
     * @param  string $userId    会员ID
     * @param  array $params     提交数据
     */
    public function shuyunModify($companyId, $userId, $params)
    {
        $shuyunModifyData = [];
        if (isset($params['username'])) {
            $shuyunModifyData['nick'] = $params['username'];
        }
        if (isset($params['birthday'])) {
            $shuyunModifyData['birthday'] = $params['birthday'];
        }
        if (isset($params['sex']) && in_array($params['sex'], ['1','2'])) {
            $shuyunModifyData['gender'] = $params['sex'] == '1' ? 'M' : 'F';
        }
        if (empty($shuyunModifyData)) {
            return false;
        }
        $assocInfo = $this->getMembersAssociationByUserid($companyId, 'wechat', $userId);
        if (empty($assocInfo)) {
            return false;
        }
        $shuyunModifyData['unionid'] = $assocInfo['unionid'];
        $shuyunMembersService = new ShuyunMembersService($companyId, $userId);
        return $shuyunMembersService->memberUpdate($shuyunModifyData);
    }

    /**
     * 会员更新
     * @param $params
     * @param $filter
     * @return mixed
     * @throws Exception
     */
    public function memberUpdate($params, $filter)
    {
        $info = $this->membersRepository->get($filter);
        if (!$info) {
            throw new Exception(trans('MembersBundle/Members.user_not_exists'));
        }

        $result = $this->membersRepository->update($params, $filter);
        return $result;
    }

    //保存会员操作日志
    public function saveMemberOperateLog($inputdata, $companyId)
    {
        if (app('auth')->user()->get('operator_type') == 'staff') {
            $sender = '员工-' . app('auth')->user()->get('username') . '-' . app('auth')->user()->get('mobile');
        } else {
            $sender = app('auth')->user()->get('username');
        }
        $operateLog = new MemberOperateLogService();
        $operateParams = [
            'user_id' => $inputdata['user_id'],
            'company_id' => $companyId,
            'operate_type' => 'grade_id',
            'old_data' => $inputdata['old_grade_id'],
            'new_data' => $inputdata['grade_id'],
            'operater' => $sender,
            'remarks' => $inputdata['remarks'],
        ];
        $operateLog->create($operateParams);
    }

    /**
     * 更新会员的手机号
     * @param array $params 更新的内容
     * @param array $filter 筛选条件
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateMemberMobile($params, $filter)
    {
        $member = $this->membersRepository->get($filter);
        if (!$member) {
            throw new ResourceException(trans('MembersBundle/Members.user_not_exists'));
        }
        $newMobileMember = $this->membersRepository->get(['mobile' => $params['mobile'], 'company_id' => $filter['company_id']]);
        if ($newMobileMember) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_already_exists_service'));
        }
        return $this->membersRepository->update($params, $filter);
    }

    public function getMemberList($filter, $page = 1, $pageSize = 100, $orderBy = ['created' => 'DESC'])
    {
        $filter['user_id'] = $filter['user_id'] ?? [];
        if (!is_array($filter['user_id'])) {
            $filter['user_id'] = [$filter['user_id']];
        }
        $indexNickInfo = [];
        if (isset($filter['wechat_nickname']) && $filter['wechat_nickname']) {
            $nickInfo = $this->getNickName($filter['company_id'], $filter['wechat_nickname']);
            $indexNickInfo = array_column($nickInfo, 'nickname', 'user_id');
            if (empty($filter['user_id'])) {
                $filter['user_id'] = empty($nickInfo) ? '-1' : array_column($nickInfo, 'user_id');
            } else {
                $filter['user_id'] = array_intersect($filter['user_id'], array_column($nickInfo, 'user_id'));
            }
            unset($filter['wechat_nickname']);
        }

        if (empty($filter['user_id'])) {
            unset($filter['user_id']);
        }

        $conn = app('registry')->getConnection('default');

        $mFields = "DISTINCT m.user_id,m.company_id,m.grade_id,m.mobile,m.user_card_code,m.authorizer_appid,m.wxa_appid,m.source_id,m.monitor_id,m.latest_source_id,m.latest_monitor_id,m.created,m.updated,m.created_year,m.created_month,m.created_day,m.offline_card_code,m.inviter_id,m.source_from,m.password,m.disabled,m.use_point,m.remarks,m.third_data,m.region_mobile,m.mobile_country_code,";
        $row = $mFields . 'info.username,info.name,info.sex,info.birthday,info.address,info.email,info.industry,info.income,info.edu_background,info.habbit,info.avatar';

        $criteria = $conn->createQueryBuilder();
        $criteria->from('members', 'm')
            ->leftJoin('m', 'members_info', 'info', 'info.user_id = m.user_id');

        if (isset($filter['shop_id']) && $filter['shop_id']) {
            $criteria->leftJoin('m', 'members_rel_shop', 'shop', 'shop.user_id = m.user_id')
                ->leftJoin('shop', 'wxshops', 's', 'shop.shop_id = s.wx_shop_id');
            $row .= ',shop.shop_id,s.store_name';
        } elseif ((isset($filter['distributor_id']) && $filter['distributor_id']) || (isset($filter['salesman_id']) &&$filter['salesman_id'])) {
            $criteria->leftJoin('m', 'members_rel_shop', 'shop', 'shop.user_id = m.user_id')
                ->leftJoin('shop', 'distribution_distributor', 'd', 'shop.shop_id = d.distributor_id');
            $row .= ',d.name as shop_name';
        }

        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $criteria->leftJoin('m', 'members_rel_tags', 'reltags', 'reltags.user_id = m.user_id');
        }

        $this->_filter($filter, $criteria);

        $commonKey = ['company_id', 'user_id', 'created', 'updated', 'created_month', 'created_day', 'created_year'];
        foreach ($orderBy as $key => $value) {
            if (in_array($key, $commonKey)) {
                if ($key == 'created') {
                    $key = 'user_id';//created 没有索引，和 user_id 排序等效
                }
                $criteria->addOrderBy('m.' . $key, $value);
            } else {
                $criteria->addOrderBy($key, $value);
            }
        }
        $criteria->select($row);
        if ($pageSize > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
        }

        $result = $criteria->execute()->fetchAll();

        $userIdList = array_column($result, 'user_id');
        $idList = $this->getOpenIdByUserIdList($filter, $userIdList);
        $idIndex = array_column($idList, null, 'user_id');

        if ($result) {
            foreach ($result as $key => $value) {
                $result[$key]['unionid'] = isset($idIndex[$value['user_id']]) ? $idIndex[$value['user_id']]['unionid'] : '';
                $result[$key]['open_id'] = isset($idIndex[$value['user_id']]) ? $idIndex[$value['user_id']]['open_id'] : '';

                $result[$key]['nickname'] = $indexNickInfo && isset($indexNickInfo[$value['user_id']]) ? $indexNickInfo[$value['user_id']] : '';
                // 脱敏
                isset($value['mobile']) and $result[$key]['mobile'] = fixeddecrypt($value['mobile']);
                isset($value['username']) and $result[$key]['username'] = fixeddecrypt($value['username']);
                isset($value['nickname']) and $result[$key]['nickname'] = fixeddecrypt($value['nickname']);
            }
        }
        return $result;
    }


    private function getNickName(int $companyId, string $nickName): array
    {
        $wechatUserInfo = app('registry')->getManager('default')->getRepository(WechatUsers::class);

        $filter = [
            'company_id' => $companyId,
            'nickname' => $nickName,
        ];
        $nickNameList = $wechatUserInfo->getAllLists($filter, 'unionid,nickname');
        if (empty($nickNameList)) {
            return [];
        }
        $unionIdList = array_column($nickNameList, 'unionid');

        $membersAssoc = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $filter = [
            'company_id' => $companyId,
            'unionid' => $unionIdList
        ];
        $list = $membersAssoc->lists($filter, 'unionid,user_id');
        $indexUserId = array_column($list, 'user_id', 'unionid');

        foreach ($nickNameList as $key => $item) {
            if (isset($indexUserId[$item['unionid']])) {
                $nickNameList[$key]['user_id'] = $indexUserId[$item['unionid']];
            }
        }
        return $nickNameList;
    }

    /**
     * 通过userId列表获取微信ID信息
     *
     * @param array $companyId
     * @param array $userIdList
     * @return array
     */
    private function getOpenIdByUserIdList(array $filter, array $userIdList): array
    {
        if (empty($userIdList)) {
            return [];
        }

        $companyId = isset($filter['company_id']) && $filter['company_id'] ? $filter['company_id'] : 0;

        // 获取 unionid
        $membersAssoc = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $filter = [
            'user_id' => $userIdList
        ];
        $companyId && $filter['company_id'] = $companyId;

        $list = $membersAssoc->lists($filter, 'unionid,user_id');
        if (empty($list)) {
            return [];
        }

        $unionIdList = array_column($list, 'unionid');
        $filter = [
            'unionid' => $unionIdList
        ];
        $companyId && $filter['company_id'] = $companyId;

        $wechatUsers = app('registry')->getManager('default')->getRepository(WechatUsers::class);
        $openIdList = $wechatUsers->getAllLists($filter, 'unionid,open_id');
        $openIdIndex = array_column($openIdList, 'open_id', 'unionid');

        foreach ($list as $key => $item) {
            $list[$key]['open_id'] = $openIdIndex[$item['unionid']] ?? '';
        }

        return $list;
    }


    public function getDistributorMemberList($filter, $page = 1, $pageSize = 100, $orderBy = ['created' => 'DESC'])
    {
        $conn = app('registry')->getConnection('default');

        $row = 'm.*, info.username, info.sex, info.birthday, info.address, info.email, info.industry, info.income, info.edu_background, info.habbit';

        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('members', 'm');

        //判断是否存在 members_info 的搜索条件
        if (isset($filter['have_consume']) or isset($filter['username'])) {
            $criteria->leftJoin('m', 'members_info', 'info', 'info.user_id = m.user_id');
        }

        if (isset($filter['shop_id']) && $filter['shop_id']) {
            $criteria->leftJoin('m', 'members_rel_shop', 'shop', 'shop.user_id = m.user_id')
                ->leftJoin('shop', 'wxshops', 's', 'shop.shop_id = s.wx_shop_id');
            $row .= ',shop.shop_id,s.store_name';
        }

        if ((isset($filter['distributor_id']) && $filter['distributor_id'])) {
            $criteria->leftJoin('m', 'distribution_distributor_user', 'store', 'store.user_id = m.user_id');
            $row .= ',store.distributor_id';
            if (isset($filter['distributor_id']) && $filter['distributor_id']) {
                $criteria->leftJoin('store', 'distribution_distributor', 'd', 'store.distributor_id = d.distributor_id');
                $row .= ', d.name as shop_name';
            }
        }

        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $criteria->leftJoin('m', 'members_rel_tags', 'reltags', 'reltags.user_id = m.user_id');
        }

        $this->_filter($filter, $criteria);

        if ($pageSize > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
        }

        $commonKey = ['company_id', 'user_id', 'created', 'updated', 'created_month', 'created_day', 'created_year'];
        foreach ($orderBy as $key => $value) {
            if (in_array($key, $commonKey)) {
                $criteria->addOrderBy('m.' . $key, $value);
            } else {
                $criteria->addOrderBy($key, $value);
            }
        }

        $lists = $criteria->select($row)->execute()->fetchAll();
        if ($lists) {
            /**
             * //处理会员绑定的导购员
             * $memberIds = [];
             * foreach ($lists as $list) {
             * array_push($memberIds, $list['user_id']);
             * }
             * $filter = [
             * 'user_id' => $memberIds,
             * 'is_bind' => 1
             * ];
             * $relSalesperson = $this->workWechatRelRepository->getLists($filter, 'user_id, salesperson_id');
             * $salespersonIds = [];
             * foreach ($relSalesperson as $value) {
             * foreach ($lists as &$list) {
             * if ($value['user_id'] == $list['user_id']) {
             * $list['salesperson_id'] = $value['salesperson_id'];
             * }
             * }
             * array_push($salespersonIds, $value['salesperson_id']);
             * }
             * unset($list);
             * if ($salespersonIds) {
             * $filter = [
             * 'salesperson_id' => $salespersonIds,
             * 'salesperson_type' => 'shopping_guide'
             * ];
             * $salespersons = $this->shopSalespersonRepository->getLists($filter, 'name, salesperson_id');
             * foreach ($salespersons as $salesperson) {
             * foreach ($lists as &$list) {
             * if (isset($list['salesperson_id']) && $salesperson['salesperson_id'] == $list['salesperson_id']) {
             * $list['salesperson_name'] = $salesperson['name'];
             * }
             * }
             * }
             * unset($list);
             * }
             **/
        }

        return $lists;
    }


    private function _filter($filter, &$criteria)
    {
        $fixedencryptCol = ['mobile', 'wechat_nickname'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt($filter[$col]);
            }
        }
        if (isset($filter['have_consume'])) {
            $criteria->andWhere($criteria->expr()->eq('info.have_consume', $criteria->expr()->literal($filter['have_consume'])));
            unset($filter['have_consume']);
        }
        if (isset($filter['username'])) {
            if ($filter['username']) {
                $criteria->andWhere($criteria->expr()->eq('info.username', $criteria->expr()->literal(fixedencrypt($filter['username']))));
            }
            unset($filter['username']);
        }
        if (isset($filter['name'])) {
            if ($filter['name']) {
                $criteria->andWhere($criteria->expr()->eq('info.name', $criteria->expr()->literal($filter['name'])));
            }
            unset($filter['name']);
        }

//        if (isset($filter['wechat_nickname'])) {
//            if ($filter['wechat_nickname']) {
//                $criteria->andWhere($criteria->expr()->like('nickname', $criteria->expr()->literal('%' . $filter['wechat_nickname'] . '%')));
//            }
//            unset($filter['wechat_nickname']);
//        }

        if (isset($filter['distributor_id']) && $filter['distributor_id']) {
            $distributorIds = is_array($filter['distributor_id']) ? $filter['distributor_id'] : array($filter['distributor_id']);
//            $criteria->orWhere($criteria->expr()->in('store.distributor_id', $distributorIds));
            $criteria = $criteria->andWhere($criteria->expr()->andX(
                $criteria->expr()->orX(
                    $criteria->expr()->eq('shop.shop_type', $criteria->expr()->literal('distributor'))
                ),
                $criteria->expr()->andX(
                    $criteria->expr()->in('shop.shop_id', $distributorIds)
                )
            ));
        } elseif (isset($filter['shop_id']) && $filter['shop_id']) {
            $shopIds = is_array($filter['shop_id']) ? $filter['shop_id'] : array($filter['shop_id']);
//            $criteria->orWhere($criteria->expr()->eq('store.shop_type', $criteria->expr()->literal('shop')));
//            $criteria->andWhere($criteria->expr()->in('shop.shop_id', $shopIds));

            $criteria = $criteria->andWhere($criteria->expr()->andX(
                $criteria->expr()->orX(
                    $criteria->expr()->eq('shop.shop_type', $criteria->expr()->literal('shop'))
                ),
                $criteria->expr()->andX(
                    $criteria->expr()->in('shop.shop_id', $shopIds)
                )
            ));
        }

        /**
         * if (isset($filter['salesman_id']) && $filter['salesman_id']) {
         * $criteria->andWhere($criteria->expr()->eq('store.salesman_id', $filter['salesman_id']));
         * unset($filter['salesman_id']);
         * }
         **/

        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $criteria->andWhere($criteria->expr()->eq('reltags.tag_id', $criteria->expr()->literal($filter['tag_id'])));
        }
        unset($filter['distributor_id']);
        unset($filter['shop_id']);
        unset($filter['tag_id']);

        //好像没什么用？
        //$criteria->andWhere($criteria->expr()->isNotNull('m.mobile'));
        //$criteria->andWhere($criteria->expr()->isNotNull('m.user_card_code'));

        if ($filter) {
            $commonKey = ['company_id', 'user_id', 'created', 'updated', 'created_month', 'created_day', 'created_year', 'remarks', 'mobile'];
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $v = in_array($v, $commonKey) ? 'm.' . $v : $v;
                    if (in_array($k, ['like', 'notlike'])) {
                        $criteria->andWhere($criteria->expr()->$k($v, $criteria->expr()->literal('%' . $value . '%')));
                    } elseif (in_array($k, ['in', 'notIn'])) {
                        $criteria->andWhere($criteria->expr()->$k($v, $value));
                    } else {
                        $criteria->andWhere($criteria->expr()->$k($v, $criteria->expr()->literal($value)));
                    }
                    continue;
                } elseif (in_array($field, $commonKey)) {
                    if (is_array($value)) {
                        $criteria->andWhere($criteria->expr()->in('m.' . $field, $value));
                    } else {
                        $criteria->andWhere($criteria->expr()->eq('m.' . $field, $criteria->expr()->literal($value)));
                    }
                } else {
                    $criteria->andWhere($criteria->expr()->eq('m.' . $field, $criteria->expr()->literal($value)));
                }
            }
        }
    }

    public function getMemberCount($filter)
    {
        $conn = app('registry')->getConnection('default');

        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('members', 'm')
            ->leftJoin('m', 'members_info', 'info', 'info.user_id = m.user_id');

        if (isset($filter['shop_id']) && $filter['shop_id']) {
            $criteria->leftJoin('m', 'members_rel_shop', 'shop', 'shop.user_id = m.user_id')
                ->leftJoin('shop', 'wxshops', 's', 'shop.shop_id = s.wx_shop_id');
        } elseif ((isset($filter['distributor_id']) && $filter['distributor_id']) || (isset($filter['salesman_id']) && $filter['salesman_id'])) {
            $criteria->leftJoin('m', 'members_rel_shop', 'shop', 'shop.user_id = m.user_id')
                ->leftJoin('shop', 'distribution_distributor', 'd', 'shop.shop_id = d.distributor_id');
        }

        $filter['user_id'] = $filter['user_id'] ?? [];
        if (!is_array($filter['user_id'])) {
            $filter['user_id'] = [$filter['user_id']];
        }

        if (isset($filter['wechat_nickname']) && $filter['wechat_nickname']) {
            $nickInfo = $this->getNickName($filter['company_id'], $filter['wechat_nickname']);
            if (empty($filter['user_id'])) {
                $filter['user_id'] = empty($nickInfo) ? '-1' : array_column($nickInfo, 'user_id');
            } else {
                $filter['user_id'] = array_intersect($filter['user_id'], array_column($nickInfo, 'user_id'));
            }
            unset($filter['wechat_nickname']);
        }
        if (empty($filter['user_id'])) {
            unset($filter['user_id']);
        }

        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $criteria->leftJoin('m', 'members_rel_tags', 'reltags', 'reltags.user_id = m.user_id');
        }
        $this->_filter($filter, $criteria);

        $count = $criteria->execute()->fetchColumn();
        return intval($count);
    }

    // 认证获取用户
    /*
    * @$identifier  user_id."_espier_".open_id."_espier_".unionid
    */

    public function getUserLoginInfo($identifier)
    {
        list($user_id, $openid, $unionid) = explode('_espier_', $identifier);
        $userEntity = $this->membersRepository->findOneBy(['user_id' => $user_id]);

        if (!empty($userEntity)) {
            if ($openid && $unionid && $openid != 'companyid') {
                $userService = new UserService(new WechatUserService());
                $user = $userService->getUserInfo(['unionid' => $unionid, 'open_id' => $openid]);
                $openPlatform = new OpenPlatform();
                if (isset($user['authorizer_appid']) && $user['authorizer_appid']) {
                    $companyId = $openPlatform->getCompanyId($user['authorizer_appid']);
                    $woaAppid = $openPlatform->getWoaAppidByCompanyId($companyId);
                }
            } else {
                $companyId = $unionid;
            }

            $memberInfo = $this->getMemberInfo(['user_id' => $user_id, 'company_id' => $companyId]);

            $result = [
                'id' => $userEntity->getUserId(),
                'user_id' => $userEntity->getUserId(),
                'disabled' => $userEntity->getDisabled() ?? 0,
                'company_id' => $userEntity->getCompanyId(),
                'wxapp_appid' => $user['authorizer_appid'] ?? '',
                'woa_appid' => $woaAppid ?? '',
                'open_id' => $user['open_id'] ?? '',
                'unionid' => $user['unionid'] ?? '',
                'nickname' => $user['nickname'] ?? '',
                'headimgurl' => $user['headimgurl'] ?? '',
                'grade_id' => $userEntity->getGradeId(),
                'mobile' => $userEntity->getMobile(),
                'username' => $memberInfo['username'] ?? '',
                'user_card_code' => $userEntity->getUserCardCode(),
                'member_card_code' => $userEntity->getUserCardCode(),
                'offline_card_code' => $userEntity->getOfflineCardCode(),
                'operator_type' => 'user',
                'inviter_id' => $userEntity->getInviterId(),
                'source_id' => $userEntity->getSourceId(),
                'monitor_id' => $userEntity->getMonitorId(),
                'latest_source_id' => $userEntity->getLatestSourceId(),
                'latest_monitor_id' => $userEntity->getLatestMonitorId(),
            ];
            return $result;
        }

        throw new UnauthorizedHttpException('', trans('MembersBundle/Members.get_user_info_error'));
    }

    //验证用户名密码
    public function checkUser($company_id, $mobile, $password, $check_type = 'password', $vcode = '')
    {
        $userEntity = $this->membersRepository->findOneBy(['company_id' => $company_id, 'mobile' => fixedencrypt($mobile)]);

        if ($check_type == 'mobile') {
            $regSettinService = new MemberRegSettingService();
            if (!$regSettinService->checkSmsVcode($mobile, $company_id, $vcode, 'login')) {
                throw new \Exception(trans('MembersBundle/Members.sms_code_error_exception'));
            }
            if (empty($userEntity)) {
                // 创建用户
                $createUser = [
                    'mobile'            => $mobile,
                    'password'          => substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10),
                    'birthday'          => "",
                    'sex'               => "",
                    'user_type'         => 'local',
                    'company_id'        => $company_id,
                    'inviter_id'        => 0,
                    'source_from'       => 'default',
                    'source_id'         => 0,
                    'monitor_id'        => 0,
                    'latest_source_id'  => 0,
                    'latest_monitor_id' => 0,
                    'unionid'           => '',
                    'open_id'           => '',
                    'user_id'           => 0,
                    'wxa_appid'         => '',
                    'authorizer_appid'  => '',
                    'api_from'          => 'h5app',
                    'auth_type'         => '',
                    'avatar'            => ''
                ];
                $this->createMember($createUser, true);
                $userEntity = $this->membersRepository->findOneBy(['company_id' => $company_id, 'mobile' => fixedencrypt($mobile)]);
            }
        } else {
            if (empty($userEntity)) {
                throw new \Exception(trans('MembersBundle/Members.mobile_not_registered_login'));
            }
            if (!$this->checkPassword($password, $userEntity->getPassword())) {
                throw new \Exception(trans('MembersBundle/Members.username_or_password_error'));
            }
        }
        $result = [
            'id' => $userEntity->getUserId() . "_espier_companyid_espier_" . $company_id,
            'user_id' => $userEntity->getUserId(),
            'company_id' => $userEntity->getCompanyId(),
            'grade_id' => $userEntity->getGradeId(),
            'mobile' => $userEntity->getMobile(),
            'user_card_code' => $userEntity->getUserCardCode(),
            'offline_card_code' => $userEntity->getOfflineCardCode(),
            'disabled' => $userEntity->getDisabled(),
            'operator_type' => 'user',
            'inviter_id' => $userEntity->getInviterId(),
            'source_id' => $userEntity->getSourceId(),
            'monitor_id' => $userEntity->getMonitorId(),
            'latest_source_id' => $userEntity->getLatestSourceId(),
            'latest_monitor_id' => $userEntity->getLatestMonitorId(),
        ];
        return $result;
    }

    private function checkPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public function usePointOpen($userId, $companyId)
    {
        $memberFilter = ['user_id' => $userId, 'company_id' => $companyId];
        $memberInfo = $this->getMemberInfo($memberFilter);
        if (!$memberInfo['use_point']) {
            $pointMemberRuleService = new PointMemberRuleService();
            $usePointRule = $pointMemberRuleService->getUsePointRule($companyId);
            if (0 == $usePointRule) {
                $this->updateMemberInfo(['use_point' => true], $memberFilter);
            }
            $depositTrade = new \DepositBundle\Services\DepositTrade();
            $deposit = $depositTrade->getDepositTradeRechargeCount($userId);
            if (isset($deposit['money_sum']) && $deposit['money_sum'] >= $usePointRule) {
                $this->updateMemberInfo(['use_point' => true], $memberFilter);
            }
        }
    }

    /**
     * 判读用户是否在vip组里面
     * @param $userId
     * @param $companyId
     * @param $validGrade
     * @return bool
     */
    public function isHaveVip($userId, $companyId, $validGrade)
    {
        if (!$userId) {
            return false;
        }
        if (!$validGrade) {
            return true;
        }
        // 会员计算
        $userGradeData = $this->getValidUserGradeUniqueByUserId($userId, $companyId);
        // $memberGrade = $this->getMemberGrade($companyId);
        $userGrade = $userGradeData['lv_type'] ?? 'normal';   //会员等级类型 vip、svip、normal
        if ('normal' == $userGrade) {
            $userGrade = $userGradeData['id'];
        }

        //检测指定的会员是否包含在活动指定的会员登记中
        // if ($validGrade && $userGrade
        //     && in_array($userGrade, $validGrade)) return true;
        if ($validGrade && $userGrade && in_array($userGrade, $validGrade)) {
            return true;
        } else {
            return false;
        }

        // return isset($memberGrade[$userGrade]) ? true : false;
    }

    // 验证登录密码

    /**
     *  获取会员的有效等级唯一标示
     */
    public function getValidUserGradeUniqueByUserId($userId, $companyId)
    {
        //$userGradeUnique = '';
        $userGradeUnique = [];
        if ($userId) {
            $vipGradeService = new VipGradeOrderService();
            $vipgrade = $vipGradeService->userVipGradeGet($companyId, $userId);
            if (($vipgrade['valid'] ?? 0) && ($vipgrade['is_vip'] ?? 0)) {
                // $userGradeUnique = $vipgrade['vip_type'];
                $userGradeUnique['id'] = $vipgrade['vip_grade_id'];
                $userGradeUnique['name'] = $vipgrade['grade_name'];
                $userGradeUnique['lv_type'] = $vipgrade['vip_type'];
                $userGradeUnique['discount'] = $vipgrade['discount'];
            } else {
                $filter = [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                ];
                $memberInfo = $this->getMemberInfo($filter);
                $memberCardService = new MemberCardService();
                if ($memberInfo['grade_id'] ?? 0) {
                    //$userGradeUnique = $memberInfo['grade_id'];
                    $gradeInfo = $memberCardService->getGradeByGradeId($memberInfo['grade_id']);
                    $userGradeUnique['id'] = $memberInfo['grade_id'];
                    $userGradeUnique['name'] = $gradeInfo['grade_name'];
                    $userGradeUnique['lv_type'] = 'normal';
                    $userGradeUnique['discount'] = $gradeInfo['privileges']['discount'];
                    if ($vipgrade['is_open'] ?? false) {
                        $userGradeUnique['userVipData'] = $vipgrade;
                    }
                }
            }
        }
        return $userGradeUnique;
    }

    /**
     * 获取会员普通等级 和 付费会员等级数组合集
     */
    public function getMemberGrade($companyId)
    {
        $vipGradeService = new VipGradeService();
        $vipGrade = $vipGradeService->lists(['company_id' => $companyId, 'is_disabled' => false]);
        $vipGrade = array_column($vipGrade, null, 'lv_type');

        $kaquanService = new MemberCardService();
        $grade = $kaquanService->getGradeListByCompanyId($companyId, false);
        $grade = array_column($grade, null, 'grade_id');

        if ($vipGrade && $grade) {
            $result = $vipGrade + $grade;
        } elseif ($vipGrade) {
            $result = $vipGrade;
        } elseif ($grade) {
            $result = $grade;
        }
        return $result ?? [];
    }

    /**
     * @brief 导购端筛选会员，如果有tag_id导购端筛选会员,需要进阶式筛选
     *
     * @param $filter
     * @param ''
     * @param $page
     * @param $pageSize
     *
     * @return
     */
    public function getMemberDataLists($filter, $col = 'all', $page = 1, $pageSize = 20)
    {
        $userIds = [];
        if (isset($filter['user_id'])) {
            $userIds = (array)$filter['user_id'];
        }
        unset($filter['tag_id']);

        if ($filter['distributor_id'] ?? null) {
            $conn = app('registry')->getConnection('default');
            $criteria = $conn->createQueryBuilder();
            $distributorIds = (array)$filter['distributor_id'];
            array_walk($distributorIds, function (&$colVal) use ($criteria) {
                $colVal = $criteria->expr()->literal($colVal);
            });
            $criteria->select('user_id')
                ->from('distribution_distributor_user')
                ->where($criteria->expr()->in('distributor_id', (array)$distributorIds));
            if ($userIds) {
                $criteria->where($criteria->expr()->in('user_id', $userIds));
            }
            $criteria->groupBy('user_id')
                ->having('count(user_id) =' . count($distributorIds));
            if ($pageSize > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $list = $criteria->execute()->fetchAll();
            if (!$list) {
                return ['list' => [], 'count' => 0];
            }
            $userIds = array_column($list, 'user_id');
        }
        unset($filter['distributor_id']);

        if ($userIds) {
            $filter['user_id'] = $userIds;
        }

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('members', 'm')
            ->leftJoin('m', 'members_info', 'info', 'info.user_id = m.user_id');

        $this->_filter($filter, $criteria);

        $result['total_count'] = $criteria->execute()->fetchColumn();
        if ($result['total_count'] <= 0) {
            $result['list'] = [];
            return $result;
        }

        if ($pageSize > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
        }
        if ($col == 'all') {
            $row = 'm.*, info.username, info.sex, info.birthday, info.address, info.email, info.industry, info.income, info.edu_background, info.habbit';
        } else {
            $row = $col;
        }
        $result['list'] = $criteria->select($row)->execute()->fetchAll();
        return $result;
    }

    /**
     * @brief 批量修改会员信息
     *
     * @param $filter
     * @param $params
     *
     * @return
     */
    public function batchUpdateMemberGradeData($filter, $params)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->update('members');
        foreach ($params as $key => $val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%' . $value . '%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb->execute();
    }

    /**
     * @brief 批量插入会员信息操作日志
     *
     * @param $data
     *
     * @return
     */
    public function batchInsertOperateLog($data)
    {
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder();

        $columns = array();
        foreach ($data[0] as $columnName => $value) {
            $columns[] = $columnName;
        }

        $sql = 'INSERT INTO members_operate_log (' . implode(', ', $columns) . ') VALUES ';

        foreach ($data as $value) {
            foreach ($value as &$v) {
                $v = $qb->expr()->literal($v);
            }
            $insertValue[] = '(' . implode(', ', $value) . ')';
        }

        $sql .= implode(',', $insertValue);

        return $conn->executeUpdate($sql);
    }

    public function getUserIdsByUserCardCode($companyId, $code)
    {
        $filter = [
            'user_card_code' => $code,
            'company_id' => $companyId,
        ];
        $member = $this->membersRepository->lists($filter);
        if (!$member) {
            return [];
        }
        return array_column($member['list'], 'user_id');
    }

    public function getUserIdsByUserCardCode2($code)
    {
        $filter = [
            'user_card_code' => $code,
        ];
        $member = $this->membersRepository->lists($filter);
        if (!$member) {
            return [];
        }
        return array_column($member['list'], 'user_id');
    }

    /**
     * 绑定会员与导购员的关系
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function bindUserSalespersonRel(array $data)
    {
        $filter = [
            'salesperson_id' => $data['salesperson_id'],
            'company_id' => $data['company_id'],
            'store_type' => 'distributor'
        ];
        if (isset($data['distributor_id'])) {
            $filter['shop_id'] = $data['distributor_id'];
        }
        $shopRelSalespersonRepository = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
        $salespersonInfo = $shopRelSalespersonRepository->getInfo($filter);
        if (!$salespersonInfo) {
            throw new ResourceException(trans('MembersBundle/Members.guide_not_exists'));
        }

        //获取导购员姓名
        $salesPersonName = '';
        $salesPersonService = new SalespersonService();
        $salespersonInfo = $salesPersonService->salesperson->getInfoById($data['salesperson_id']);
        if ($salespersonInfo) {
            $salesPersonName = $salespersonInfo['name'];
        }

        $membersAssoc = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $workWechatRelService = new WorkWechatRelService();
        $workWechatRelRepository = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
        foreach ($data['users'] as $user) {
            $filter = [
                'user_id' => $user,
                'company_id' => $data['company_id'],
            ];
            $userInfo = $membersAssoc->get($filter);
            if (!$userInfo) {
                continue;
            }
            $userList = $membersAssoc->getList(['unionid' => $userInfo['unionid']]);
            foreach ($userList['list'] as $v) {
                $conn = app('registry')->getConnection('default');
                $conn->beginTransaction();
                try {
                    $isFirstBind = true;//是否首次绑定
                    //解绑其他导购员
                    $filter = [
                        'user_id' => $v['user_id'],
                        'company_id' => $data['company_id']
                    ];
                    if ($workWechatRelRepository->count($filter) > 0) {
                        $workWechatRelRepository->updateBy($filter, ['is_bind' => 0]);
                        $isFirstBind = false;
                    }

                    //绑定当前导购员
                    $filter = [
                        'user_id' => $v['user_id'],
                        'company_id' => $data['company_id'],
                        'salesperson_id' => $data['salesperson_id'],
                    ];
                    $isBound = $workWechatRelRepository->getInfo($filter);
                    if ($isBound) {
                        $status = $workWechatRelRepository->updateOneBy($filter, ['is_bind' => 1, 'bound_time' => time()]); //修改
                        $data = $isBound;
                    } else {
                        $data = [
                            'company_id' => $data['company_id'],
                            'salesperson_id' => $data['salesperson_id'],
                            'unionid' => $userInfo['unionid'],
                            'user_id' => $v['user_id'],
                            'is_friend' => 0,
                            'is_bind' => 1,
                            'bound_time' => time(),
                            'add_friend_time' => 0
                        ];
                        $status = $workWechatRelRepository->create($data);
                    }

                    $data['is_first_bind'] = $isFirstBind;
                    $data['salesperson_name'] = $salesPersonName;
                    $workWechatRelService->saveWorkWechatRelLogs($data);

                    $conn->commit();
                } catch (\Exception $exception) {
                    $conn->rollback();
                    throw $exception;
                }
            }
        }

        if ($status ?? false) {
            $returnData = [
                'success' => true,
            ];
        } else {
            $returnData = [
                'success' => false,
            ];
        }
        return $returnData;
    }

    /**
     * 更新会员信息，members和member_info
     */
    public function membersInfoUpdate($params, $filter)
    {
        $info = $this->getMemberInfo($filter);
        if (!$info) {
            throw new Exception(trans('MembersBundle/Members.user_not_exists'));
        }
        $result = $this->membersRepository->update($params, $filter);
        $_filter = ['user_id' => $info['user_id'], 'company_id' => $info['company_id']];
        $result = $this->membersInfoRepository->updateBy($_filter, $params);
        return $result;
    }

    /**
     * 绑定会员
     * @param array $params 绑定参数
     * @return string token
     */
    public function bindMember(array $params): ?string
    {
        $companyId = (int)$params['company_id'];
        $mobile = (string)$params['username'];
        $unionid = (string)$params['union_id'];
        $checkType = $params['check_type'] ?? '';
        $password = $params['password'] ?? '';

        //手机验证码绑定 >> 验证短信验证码是否正确
        if (!empty($checkType) && !(new MemberRegSettingService())->checkSmsVcode($mobile, $companyId, $params['vcode'], $checkType)) {
            throw new ResourceException(trans('MembersBundle/Members.sms_code_error_service'));
        }

        $userWeChatService = new UserService(new WechatUserService());
        $userWeChatInfo = $userWeChatService->getUserInfo([
            "company_id" => $companyId,
            "unionid" => $unionid
        ]);
        if (empty($userWeChatInfo)) {
            throw new ResourceException(trans('MembersBundle/Members.user_wechat_info_error'));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 判断是否存在用户
            $userFilter = [
                "company_id" => $companyId,
                "mobile" => fixedencrypt($mobile)
            ];
            $userEntity = $this->membersRepository->findOneBy($userFilter);
            if (!$userEntity) {
                // 创建新用户, 内部会做绑定逻辑
                $userInfo = $this->createMember([
                    "mobile" => $mobile,
                    "region_mobile" => $mobile,
                    "mobile_country_code" => "86",
                    "company_id" => $companyId,
                    "wxa_appid" => "", // 小程序appid
                    "authorizer_appid" => $userWeChatInfo["authorizer_appid"] ?? "", // 公众号id
                    "sex" => $userWeChatInfo["sex"] ?? 0,
                    "username" => $userWeChatInfo["nickname"] ?? "",
                    "avatar" => $userWeChatInfo["headimgurl"] ?? "",
                    "email" => "",
                    "password" => $password,
                    "api_from" => "h5app",
                    "auth_type" => "wxapp",
                    "user_type" => "wechat",
                    "unionid" => $userWeChatInfo["unionid"] ?? "",
                    "open_id" => $userWeChatInfo["openid"] ?? "",
                    "force_password" => 1
                ]);
                $userInfo["is_new"] = 1;
            } else {
                // 密码绑定
                if (!empty($password) && !$this->checkPassword($password, $userEntity->getPassword())) {
                    throw new ResourceException(trans('MembersBundle/Members.account_or_password_error'));
                }
                $userInfo = $this->membersRepository->getDataByEntity($userEntity);
                $userInfo["is_new"] = 0;

                //绑定会员
                $this->createMemberAssociations($companyId, (int)$userInfo["user_id"], $unionid, 'wechat');
            }

            // 提交事务
            $conn->commit();

            //返回会员登录身份令牌
            $user = $this->getTokenData($userInfo);

            // 绑定成功代表用户接收隐私隐私协议
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

            return app('auth')->guard('h5api')->login(new GenericUser($user));
        } catch (ResourceException $resourceException) {
            $conn->rollback();
            throw $resourceException;
        } catch (\Throwable $throwable) {
            $conn->rollback();
            app("log")->info(sprintf("user_bind_member_error:%s", jsonEncode([
                "message" => $throwable->getMessage(),
                "file" => $throwable->getFile(),
                "line" => $throwable->getLine(),
            ])));
            throw new ResourceException(trans('MembersBundle/Members.unknown_error'));
        }
    }

    /**
     * 将用户信息与外部平台的信息做关联绑定 同一个user_type下是一对一的关系
     * @param int $companyId 企业id
     * @param int $userId 用户id
     * @param string $unionId 外部平台的唯一id
     * @param string $userType 用户类型
     * @return array
     */
    public function createMemberAssociations(int $companyId, int $userId, string $unionId, string $userType = "wechat"): array
    {
        $filter = [
            "company_id" => $companyId,
            "user_type" => $userType,
            "unionid" => $unionId
        ];
        $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $info = $membersAssociationsRepository->get($filter);
        if (!empty($info)) {
            if ($info["user_id"] == $userId) {
                return $info;
            } else {
                throw new ResourceException(trans('MembersBundle/Members.bind_failed_wechat_bound_to_other'));
            }
        }
        return $membersAssociationsRepository->create(array_merge($filter, ["user_id" => $userId]));
    }

    /**
     * 获取token数据
     * @param array $userInfo
     * @return array
     */
    public function getTokenData(array $userInfo): array
    {
        return [
            "id" => $userInfo["user_id"] . "_espier_companyid_espier_" . $userInfo["company_id"],
            "user_id" => $userInfo["user_id"],
            "company_id" => $userInfo["company_id"],
            // "grade_id" => $userInfo["grade_id"],
            // "mobile" => $userInfo["mobile"],
            // "user_card_code" => $userInfo["user_card_code"],
            // "member_card_code" => $userInfo["user_card_code"],
            // "offline_card_code" => $userInfo["offline_card_code"],
            // "disabled" => $userInfo["disabled"],
            "operator_type" => "user",
            // "inviter_id" => $userInfo["inviter_id"],
            // "source_id" => $userInfo["source_id"],
            // "monitor_id" => $userInfo["monitor_id"],
            // "latest_source_id" => $userInfo["latest_source_id"],
            // "latest_monitor_id" => $userInfo["latest_monitor_id"],

            // "wxapp_appid" => $userInfo["authorizer_appid"] ?? null,
            // "woa_appid" => $userInfo["woa_appid"] ?? null,
            "unionid" => $userInfo["unionid"] ?? null,
            "openid" => $userInfo["open_id"] ?? null,
            // "nickname" => $userInfo["nickname"] ?? "",
            // "username" => $userInfo["username"] ?? "",
            // "sex" => $userInfo["sex"] ?? 0,

            "is_new" => (int)($userInfo["is_new"] ?? 0), // 是否为新用户 【0 老用户】【1 新用户】
        ];
    }

    /**
     * 记录导购被访问的UV，请求营销中心接口
     * @param  string $companyId  企业ID
     * @param  string $workUserid 导购工号
     * @param  string $unionid    会员unionid
     */
    public function salespersonUniqueVisito($companyId, $workUserid, $unionid)
    {
        $marketingCenterRequest = new MarketingCenterRequest();
        $params = [
            'gu_user_id' => $workUserid,
            'unionid' => $unionid,
        ];
        $result = $marketingCenterRequest->call($companyId, 'salesperson.unique.visito', $params);
        return $result;
    }
    /**
     * 检查该账户是否可以注销
     * @param $company_id
     * @param $user_id
     * @return false|void
     */
    public function checkDeleteMembers($company_id, $user_id)
    {
        $filter['company_id'] = $company_id;
        $filter['user_id'] = $user_id;
        $members = $this->membersRepository->get($filter);
        if (empty($members)) {
            return false;
        }
        $filter['order_status|notin'] = ['DONE','CANCEL'];
        $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderList = $normalOrderRepository->getList($filter);
        if (!empty($orderList)) {
            return false;
        }
        return true;
    }

    /**
     * 删除会员信息
     * @param $company_id
     * @param $user_id
     * @return bool
     */
    public function deleteMembers($company_id, $user_id, $mobile)
    {
        $membersWechatUsersRepository = app('registry')->getManager('default')->getRepository(WechatUsers::class);
        $membersAddressRepository = app('registry')->getManager('default')->getRepository(MembersAddress::class);
        $membersDeleteRecordRepository = app('registry')->getManager('default')->getRepository(MembersDeleteRecord::class);
        $promoterRepository = app('registry')->getManager('default')->getRepository(Promoter::class);
        $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $vipGradeRelUserRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 数云模式，执行数云注销会员接口
            if (config('common.oem-shuyun')) {
                $shuyunMembersService = new ShuyunMembersService($company_id, $user_id);
                $shuyunMembersService->memberUnbind([]);
            }
            // 获取与用户关联的微信信息
            $membersAssociationsList = $membersAssociationsRepository->lists(['company_id' => $company_id,'user_id' => $user_id]);
            // 删除与用户关联的微信信息
            if (!empty($membersAssociationsList)) {
                $membersAssociationsRepository->deleteBy(['company_id' => $company_id,'user_id' => $user_id]);
                foreach ($membersAssociationsList as $membersAssociationsItem) {
                    $unionId = $membersAssociationsItem["unionid"] ?? "";
                    $membersWechatUsersRepository->deleteBy(['company_id' => $company_id,'unionid' => $unionId]);
                }
            }
            // 删除用户的地址信息
            $membersAddressRepository->deleteBy(['company_id' => $company_id,'user_id' => $user_id]);
            // 删除用户的详细信息
            $this->membersInfoRepository->deleteBy(['company_id' => $company_id,'user_id' => $user_id]);
            // 删除用户的登录信息
            $this->membersRepository->deleteBy(['company_id' => $company_id,'user_id' => $user_id]);
            // 删除推广员中该用户的信息
            $promoterRepository->updateOneBy(['company_id' => $company_id,'user_id' => $user_id], ['disabled' => 1]);
            // 删除该用户vip等级信息
            $vipGradeRelUserRepository->deleteBy(['company_id' => $company_id,'user_id' => $user_id]);
            // 记录注销操作
            $membersDeleteRecordRepository->create(['company_id' => $company_id,'user_id' => $user_id,'mobile' => $mobile]);

            $conn->commit();
            return true;
        } catch (\Throwable $throwable) {
            $conn->rollback();
            app('log')->info('file:'.$throwable->getFile().',line:'.$throwable->getLine().',message:'.$throwable->getMessage());
            throw new ResourceException(trans('MembersBundle/Members.logout_failed'));
        }
    }
}
