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

namespace MembersBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Routing\Helpers;
use Swagger\Annotations as SWG;
use Dingo\Api\Auth\Auth as Auth;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Jobs\BindSalseperson;
use WechatBundle\Services\OpenPlatform;
use DepositBundle\Services\DepositTrade;
use MembersBundle\Services\MemberService;
use Dingo\Api\Exception\ResourceException;
use CompanysBundle\Services\EmployeeService;
use KaquanBundle\Services\MemberCardService;
use MembersBundle\Services\SubscribeService;
use PointBundle\Services\PointMemberService;
use MembersBundle\Services\WechatUserService;
use PopularizeBundle\Services\SettingService;
use KaquanBundle\Services\UserDiscountService;
use PopularizeBundle\Services\PromoterService;
use KaquanBundle\Services\VipGradeOrderService;
use MembersBundle\Entities\MembersAssociations;
use MembersBundle\Services\MemberAddressService;
use PointBundle\Services\PointMemberRuleService;
use MembersBundle\Services\MemberItemsFavService;
use ThirdPartyBundle\Services\DmCrm\PointService;
use App\Http\Controllers\Controller;
use CompanysBundle\Services\Shops\ProtocolService;
use EspierBundle\Services\Cache\RedisCacheService;
use EspierBundle\Services\Config\ValidatorService;
use MembersBundle\Events\UpdateMemberSuccessEvent;
use MembersBundle\Services\MembersInvoicesService;
use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;
use MembersBundle\Services\MemberArticleFavService;
use MembersBundle\Services\MemberOperateLogService;
use MembersBundle\Services\MemberRegSettingService;
use MembersBundle\Services\MembersWhitelistService;
use Dingo\Api\Exception\StoreResourceFailedException;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\RelativesService;
use MembersBundle\Services\GoodsArrivalNoticeService;
use MembersBundle\Services\MemberBrowseHistoryService;
use MembersBundle\Services\MemberDistributionFavService;
use PromotionsBundle\Services\RegisterPromotionsService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;
use PromotionsBundle\Services\SpecificCrowdDiscountService;
use EspierBundle\Services\Config\ConfigRequestFieldsService;
use ThirdPartyBundle\Services\DmCrm\MemberService as DmMemberService;
use CompanysBundle\Services\SettingService as SelfdeliveryAddressService;

class Members extends Controller
{
    use Helpers;

    public $memberService;

    public $code_type = ['sign', 'forgot_password', 'login', 'update', 'merchant_login'];

    public function __construct()
    {
        $this->memberService = new MemberService();
    }

    /**
     * @SWG\Get(path="/token/refresh",
     *   tags={"会员"},
     *   summary="H5刷新Token",
     *   description="刷新Token",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *             @SWG\Header(header="authorization", type="string", description="返回刷新后的token"),
     *         @SWG\Schema(
     *             @SWG\Property(property="data",type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="result", type="boolean", example="true"),
     *                 )
     *             ),
     *         ),
     *   ),
     *   @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Post(path="/wxapp/login",
     *   tags={"会员"},
     *   summary="用户登陆",
     *   description="用户登陆获取Token",
     *   operationId="login",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     in="query",
     *     name="username",
     *     description="登陆用户名",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(in="query", name="auth_type", required=true, description="登录类型:local", type="string"),
     *   @SWG\Parameter(in="query", name="password", required=false, description="登陆密码", type="string"),
     *   @SWG\Parameter(in="query", name="company_id", required=false, description="企业ID", type="string"),
     *   @SWG\Parameter(in="query", name="check_type", required=false, description="验证类型：mobile 手机验证码 password 密码", type="string"),
     *   @SWG\Parameter(in="query", name="vcode", required=false, description="短信验证码", type="string"),
     *   @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="data",type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="token", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *   @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    // public function login(Request $request) 路由中直接调用JWT登录


    /**
     * @SWG\Get(
     *     path="/wxapp/member/setting",
     *     tags={"会员"},
     *     summary="获取会员注册项",
     *     description="获取会员注册项",
     *     operationId="getRegSetting",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="is_edite_page", in="query", description="是否是可编辑页面", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="username", type="object", description="",required={"name","key","is_open","is_required","is_edit","element_type","field_type","items","range","select","checkbox"},
     *                   @SWG\Property(property="name", type="string", default="年收入", description="展示给用户看的字段内容"),
     *                   @SWG\Property(property="key", type="string", default="income", description="前后端交互的key"),
     *                   @SWG\Property(property="is_open", type="string", default="1", description="是否开启, [0 关闭] [1 开启]"),
     *                   @SWG\Property(property="is_required", type="string", default="", description="是否必填, [0 关闭] [1 开启]"),
     *                   @SWG\Property(property="is_edit", type="string", default="", description="是否可编辑, [0 关闭] [1 开启]"),
     *                   @SWG\Property(property="element_type", type="string", default="select", description="元素类型 [input 文本框] [numeric 数字] [date 日期] [select 下拉框] [checkbox 复选框]"),
     *                   @SWG\Property(property="field_type", type="integer", default="4", description="字段类型【1 文本】【2 数字】【3 日期】【4 单选项】【5 复选框】"),
     *                   @SWG\Property(property="range", type="array", description="数字或日期类型的范围，是一个数组对象",
     *                     @SWG\Items(required={"start","end"},
     *                           @SWG\Property(property="start", type="string", default="0", description="最小值，如果是null则无下限"),
     *                           @SWG\Property(property="end", type="string", default="999", description="最大值，如果是null则无上限"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="select", type="array", description="单选项的下拉框描述，是一个数组",
     *                       @SWG\Items(required={"value1","value2"},
     *                           @SWG\Property(property="value1", type="string", default="", description="下拉框描述1"),
     *                           @SWG\Property(property="value2", type="string", default="", description="下拉框描述2"),
     *                       ),
     *                   ),
     *                   @SWG\Property(property="checkbox", type="array", description="复选框的每个选项描述和是否默认选中，是一个数组对象",
     *                       @SWG\Items(type="object", required={"name", "ischecked"},
     *                           description="value为该选项的值，label为该选项的描述",
     *                           @SWG\Property(property="name", type="string", default="游戏", description="描述内容"),
     *                           @SWG\Property(property="ischecked", type="string", default="1", description="是否选中，1为选中，0为不选中"),
     *                       ),
     *                   ),
     *
     *              ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getRegSetting(Request $request)
    {
        $edited = $request->get('is_edite_page', false);
        $authInfo = $request->get('auth');
        if (!$edited) {
            return $this->response->array([]);
        }
        // 获取验证字段
        $data = (new ConfigRequestFieldsService())->getListAndHandleSettingFormat((int)$authInfo['company_id'], ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/agreement",
     *     tags={"会员"},
     *     summary="获取会员注册协议",
     *     description="获取会员注册协议",
     *     operationId="getRegAgreementSetting",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="",required={"type","title","content"},
     *               @SWG\Property(property="type", type="string", example="member_register", description="协议类型"),
     *               @SWG\Property(property="title", type="string", example="", description="协议标题"),
     *               @SWG\Property(property="content", type="string", example="", description="协议内容"),
     *            ),
     *         ),
     *     )
     * )
     */
    public function getRegAgreementSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = (int)$authInfo['company_id'];
        // 获取用户的注册协议
        $data = (new ProtocolService($companyId))->get([ProtocolService::TYPE_MEMBER_REGISTER]);
        $result = [
            "type" => (string)($data[ProtocolService::TYPE_MEMBER_REGISTER]["type"] ?? ""),
            "title" => (string)($data[ProtocolService::TYPE_MEMBER_REGISTER]["title"] ?? ""),
            "content" => (string)($data[ProtocolService::TYPE_MEMBER_REGISTER]["content"] ?? ""),
        ];
        // 老数据迭代
        if (empty($result["content"])) {
            $result['content'] = (new MemberRegSettingService())->getRegAgreement($companyId);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member",
     *     summary="获取会员信息",
     *     tags={"会员"},
     *     description="获取会员信息",
     *     operationId="getMemberInfo",
     *     @SWG\Parameter(
     *         name="open_id",
     *         in="query",
     *         description="用户表示",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券ID",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 ref="#/definitions/MemberInfo"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getMemberInfo(Request $request)
    {
        $userDiscountCount = 0;
        $authInfo = $request->get('auth');

        $memberCardService = new MemberCardService();
        $cardInfo = $memberCardService->getMemberCard($authInfo['company_id']);

        if (isset($authInfo['user_id']) && $authInfo['user_id']) {
            $filter = [
                'user_id' => $authInfo['user_id'],
                'company_id' => $authInfo['company_id'],
            ];
            $memberInfo = $this->memberService->getMemberInfo($filter, true);
            unset($memberInfo['region_mobile']);
            $mobile = $memberInfo['mobile'];
            if ($memberInfo) {
                $filter['disabled'] = 0;
                //是否员工
                $employeesService = new EmployeesService();
                $employee = $employeesService->getLists($filter, 'id', 1, 1);
                $memberInfo['is_employee'] = false;
                if ($employee) {
                    $memberInfo['is_employee'] = true;
                }
                $memberInfo['is_relative'] = false;

                $activityId = $request->input('activity_id', 0);
                //优先判断活动id
                if(!empty($activityId)){
                    $filter['activity_id'] = $activityId;
                    //是否亲友
                    $relativesService = new RelativesService();
                    $relative = $relativesService->getLists($filter, 'id,activity_id', 1, 1);
                    if ($relative) {
                        $memberInfo['is_relative'] = true;
                    }
                }else{
                    //兼容之前找谁都不知道是啥的逻辑
                    $code = $request->input('code', '');

                    if (! empty($code)) {
                        $employeesService = new EmployeesService();
                        list($enterpriseId, $activityId, $employeeUserId) = $employeesService->getInviteTicket($authInfo['company_id'], $code);

                        if (!$enterpriseId || !$activityId || !$employeeUserId) {
                            throw new ResourceException(trans('MembersBundle/Members.share_link_expired'));
                        }
                        $filter['activity_id'] = $activityId;
                        //是否亲友
                        $relativesService = new RelativesService();
                        $relative = $relativesService->getLists($filter, 'id,activity_id', 1, 1);
                        if ($relative) {
                            $memberInfo['is_relative'] = true;
                        }
                    }
                }


                $memberInfo['gradeInfo'] = $memberCardService->getGradeByGradeId($memberInfo['grade_id']);
                $memberInfo['nextGradeInfo'] = $memberCardService->getNextGradeByGradeId($memberInfo['grade_id']);
                $memberInfo['totalConsumption'] = $this->memberService->getTotalConsumption($memberInfo['user_id']);

                $wechatUserService = new WechatUserService();
                $wechatUserData = $wechatUserService->getUserInfo(['user_id' => $memberInfo['user_id']]);
                if (isset($wechatUserData['nickname']) && $wechatUserData['nickname']) {
                    $memberInfo['nickname'] = $wechatUserData['nickname'];
                }

                $memberInfo['open_id'] = $authInfo['open_id'] ?? '';
                // 数据脱敏
                $memberInfo['mobile'] = data_masking('mobile', (string) $memberInfo['mobile']);
                if (isset($memberInfo['requestFields']['birthday'])) {
                    $memberInfo['requestFields']['birthday'] = data_masking('birthday', (string) $memberInfo['requestFields']['birthday']);
                }
                if (isset($memberInfo['requestFields']['address'])) {
                    $memberInfo['requestFields']['address'] = data_masking('detailedaddress', (string) $memberInfo['requestFields']['address']);
                }
                if (isset($memberInfo['requestFields']['sex'])) {
                    $memberInfo['requestFields']['sex'] = $memberInfo['requestFields']['sex'] == '未知' ? '-' : data_masking('sex', (string) $memberInfo['requestFields']['sex']);
                }
                // 非数云模式，脱敏手机号
                if (!config('common.oem-shuyun')) {
                    foreach ($memberInfo['datapassRequestFields']['mobile'] as $value) {
                        $memberInfo['requestFields'][$value] = data_masking('mobile', (string) $memberInfo['requestFields'][$value]);
                    }
                }
            }
            $filter = [
                'user_id' => $authInfo['user_id'],
                'status' => 1,
                'company_id' => $authInfo['company_id'],
            ];
            $userDiscountService = new UserDiscountService();
            $userDiscountCount = $userDiscountService->getUserDiscountCount($filter);

            $depositTrade = new DepositTrade();
            $deposit = $depositTrade->getUserDepositTotal($authInfo['company_id'], $authInfo['user_id']);
            $userDiscountService = new UserDiscountService();
            $userDiscount = $userDiscountService->getUserDiscountCount(['user_id' => $authInfo['user_id'], 'company_id' => $authInfo['company_id']]);
            if (isset($authInfo['headimgurl']) && $authInfo['headimgurl']) {
                $memberInfo['avatar'] = $authInfo['headimgurl'];
            }
            if (isset($authInfo['nickname']) && !empty($authInfo['nickname'])) {
                $memberInfo['nickname'] = $authInfo['nickname'];
            }
            if (empty($memberInfo["username"])) {
                $memberInfo["username"] = $wechatUserData["nickname"] ?? "";
            }
            if (empty($memberInfo["avatar"])) {
                $memberInfo["avatar"] = $wechatUserData["headimgurl"] ?? "";
            }

            $filter_saleman = array(
                'user_id'    => $memberInfo["user_id"],
                'company_id' => $memberInfo["company_id"],
            );

            $salespersonService = new SalespersonService();
            $salesPersonList = $salespersonService->getSalespersonList($filter_saleman, ['created_time' => 'DESC'], 100, 1 );
            $employeeService = new EmployeeService();
            $filter_staff = [
                'company_id' => $memberInfo["company_id"],
                'mobile'=>$mobile,
                'operator_type'=>'self_delivery_staff'
            ];
            $deliveryStaffList = $employeeService->getListStaff($filter_staff);
            $result = [
                'memberInfo' => $memberInfo,
                'salesPersonList' => $salesPersonList,
                'deliveryStaffList' => $deliveryStaffList,
                'userDiscountCount' => $userDiscountCount,
                'deposit' => isset($deposit) ? $deposit : 0,
                'point' => 0,
                'coupon' => $userDiscount,
            ];
        }

        $result['point_open_status'] = false;
        $pointService = new PointMemberRuleService();
        $rule = $pointService->getPointRule($authInfo['company_id']);
        if ($rule['isOpenMemberPoint'] == 'true') {
            $result['point_open_status'] = true;
            $pointMemberService = new PointMemberService();
            $point = $pointMemberService->getInfo(['user_id' => $authInfo['user_id'], 'company_id' => $authInfo['company_id']]);
            $result['point'] = $point['point'] ?? 0;

            // 达摩crm, 会员积分
            $ns = new DmCrmSettingService();
            if ($ns->getDmCrmSetting($authInfo['company_id'])['is_open'] ?? '') {
                $pointService = new PointService($authInfo['company_id']);
                $paramsData = [
                    'mobile' => $mobile,
                ];
                $point = $pointService->getPoint($paramsData);
                $result['point'] = $point['integral'] ?? 0;
                $result['frozen_point'] = $point['frozenIntegral'] ?? 0;
            }
        }

        $result['cardInfo'] = $cardInfo;

        //获取付费会员卡信息
        $vipGradeService = new VipGradeOrderService();
        $vipgrade = $vipGradeService->userVipGradeGet($authInfo['company_id'], $authInfo['user_id']);
        $result['vipgrade'] = $vipgrade;

        // 是否开启推广员分销
        $settingService = new SettingService();
        $result['is_open_popularize'] = $settingService->getOpenPopularize($authInfo['company_id']);
        $result['is_open_popularize'] = ($result['is_open_popularize'] == 'false') ? false : true;

        $popularizeConfig = $settingService->getConfig($authInfo['company_id']);
        if ($result['is_open_popularize'] == true && isset($authInfo['user_id'])) {
            $promoterService = new PromoterService();
            $promoterInfo = $promoterService->getInfoByUserId($authInfo['user_id']);
            if ($promoterInfo && $promoterInfo['is_promoter']) {
                $result['is_promoter'] = true;
            } else {
                $result['is_promoter'] = false;
                // 如果只是内部，那么不是推广员的会员不显示
                if ($popularizeConfig['change_promoter']['type'] == 'internal') {
                    $result['is_open_popularize'] = false;
                }
            }
        }
        //获取会员定向优惠
        $specificCrowdDiscountService = new SpecificCrowdDiscountService();
        $activit = $specificCrowdDiscountService->getValideUserOrientation($authInfo['company_id'], $authInfo['user_id']);
        $result['is_staff'] = false;
        if ($activit) {
            $result['is_staff'] = true;
            $result['staff_discount'] = (100 - $activit['discount']) / 10;
        }

        // 获取域名配置 我的课时、我的已约
        $companyId = $authInfo['company_id'];
        $key = 'webUrlSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : [];
        if ($inputData) {
            $result['weburl']['classhour'] = $inputData['classhour'] ?? '';//我的课时
            $result['weburl']['arranged'] = $inputData['arranged'] ?? '';//我的已约
        }
        //获取储值配置
        $settingService = new SelfdeliveryAddressService();
        $rechargeOpen = $settingService->getRechargeSetting($companyId);
        $result['is_recharge_status'] = $rechargeOpen['recharge_status'];
        return $this->response->array($result);
    }

    /**
     * 用户id
     * @var int
     */
    private $userId = 0;

    /**
     * 获取用户id
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * 设置用户id
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }


    /**
     * @SWG\Post(
     *     path="/wxapp/member",
     *     summary="创建会员",
     *     tags={"会员"},
     *     description="获取会员信息",
     *     operationId="creatMember",
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string", required=true),
     *     @SWG\Parameter( name="auth_type", in="query", required=false, description="登录类型：【wxapp 微信登录】【wx_offiaccount 微信内服务号网页授权登陆方式】【local 手机号登录】", type="string"),
     *     @SWG\Parameter(in="query", name="company_id", required=false, description="企业ID", type="string"),
     *     @SWG\Parameter(in="query", name="password", required=false, description="登陆密码", type="string"),
     *     @SWG\Parameter(in="query", name="check_type", required=false, description="登陆时的短信验证码的类型", type="string"),
     *     @SWG\Parameter(in="query", name="vcode", required=false, description="登陆时的短信验证码", type="string"),
     *     @SWG\Parameter( name="user_name", in="query", description="姓名", required=false, type="string"),
     *     @SWG\Parameter( name="user_type", in="query", description="用户类型 【local 本地用户】【wechat 微信用户】", required=true, type="string"),
     *     @SWG\Parameter( name="sex", in="query", description="性别", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="token", type="string", default="", description="登录token"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function creatMember(Request $request)
    {
        $postData = $request->all();
        app('log')->debug('推荐关系跟踪 postdata：' . var_export($postData, 1));
        $auth_type = $postData['auth_type'] ?? '';
        // middleware中添加的参数获取方式
        $authInfo = $request->get('auth');
        app('log')->debug('推荐关系跟踪 authInfo：' . var_export($authInfo, 1));
        if (!isset($postData['username']) && isset($postData['user_name'])) {
            $postData['username'] = $postData['user_name'];
        }
        //(new ValidatorService())->check((int)$authInfo['company_id'], ConfigRequestFieldsService::MODULE_TYPE_MEMBER_REGISTER, $postData);
        if (empty($postData["mobile"])) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_required'));
        } elseif (!preg_match('/^1[3456789]{1}[0-9]{9}$/', $postData['mobile'])) {
            throw new ResourceException(trans('MembersBundle/Members.invalid_mobile'));
        }

        // 用户类型
        if (empty($postData["user_type"])) {
            throw new ResourceException(trans('MembersBundle/Members.user_type_required'));
        }

        // 如果认证类型不属于微信，那么需要判断密码和验证码 并 判断手机号是否存在
        if ($authInfo['api_from'] == 'h5app' && $auth_type != 'wxapp') {
            if (!$postData['password']) {
                throw new ResourceException(trans('MembersBundle/Members.password_required'));
            }
            if (!$postData['vcode']) {
                throw new ResourceException(trans('MembersBundle/Members.verification_code_required'));
            }
            $memberInfo = (new MemberService())->getInfoByMobile((int)$authInfo["company_id"], (string)$postData["mobile"]);
            if ($memberInfo) {
                if (!isset($memberInfo['other_params']['is_upload_member']) || $memberInfo['other_params']['is_upload_member'] != true) {
                    throw new ResourceException(trans('MembersBundle/Members.account_already_bound_mobile'));
                }
            }
        }

        // 检查白名单
        $result = (new MembersWhitelistService())->checkWhitelistValid($authInfo['company_id'], $postData['mobile'], $tips);
        if (!$result) {
            throw new ResourceException($tips);
        }

        // 获取推荐人id和来源类型
        $postData['inviter_id'] = null;
        $postData['source_from'] = null;
        if (!empty($postData['uid'])) {
            $memberInfo = $this->memberService->getMemberInfo([
                'user_id' => $postData['uid'],
                'company_id' => $authInfo['company_id'],
            ]);
            if ($memberInfo) {
                $postData['inviter_id'] = $postData['uid'] ?: 0;
            }
        } elseif(!empty($postData['puid'])) {
            $memberInfo = $this->memberService->getMemberInfo([
                'user_id' => $postData['puid'],
                'company_id' => $authInfo['company_id'],
            ]);
            if ($memberInfo) {
                $postData['inviter_id'] = $postData['puid'] ?: 0;
            }
        }

        // 信息验证
        if ($auth_type == "wxapp") {
            $wechatUserData = (new WechatUserService())->getSimpleUserInfo($authInfo['company_id'], $authInfo['unionid']);
            if (!$wechatUserData) {
                throw new ResourceException(trans('MembersBundle/Members.confirm_info_correct'));
            }
            if (is_null($postData['inviter_id'])) {
                $postData['inviter_id'] = $wechatUserData['inviter_id'] ?? 0;
                $postData['source_from'] = $wechatUserData['source_from'] ?? 'default';
            }
        } else {
            if (!(new MemberRegSettingService())->checkSmsVcode($postData['mobile'], $authInfo['company_id'], $postData['vcode'], $postData["check_type"] ?? "sign")) {
                throw new ResourceException(trans('MembersBundle/Members.sms_code_error'));
            }
        }


        $postData['source_id'] = isset($postData['source_id']) ? trim($postData['source_id']) : 0;

        $postData['monitor_id'] = isset($postData['monitor_id']) ? trim($postData['monitor_id']) : 0;
        $postData['latest_source_id'] = $postData['source_id'];
        $postData['latest_monitor_id'] = $postData['monitor_id'];

        $postData['company_id'] = $authInfo['company_id'];
        $postData['unionid'] = $authInfo['unionid'];
        $postData['open_id'] = $authInfo['open_id'] ?? '';
        $postData['user_id'] = $authInfo['user_id'];
        $postData['wxa_appid'] = $authInfo['wxapp_appid'];
        $postData['authorizer_appid'] = $authInfo['woa_appid'];
        $postData['api_from'] = $authInfo['api_from'];
        $postData['auth_type'] = $auth_type;
        $postData['sex'] = $postData['sex'] ?? 0;
        $postData['avatar'] = $authInfo['headimgurl'] ?? '';
        $postData["inviter_id"] = (int)($postData['inviter_id'] ?? 0);
        $postData["source_from"] = (string)($postData['source_from'] ?? "default");

        $result = $this->memberService->createMember($postData, true);
        // 传递用户id
        $this->setUserId((int)($result["user_id"] ?? 0));

        if ($authInfo['api_from'] == 'h5app' && $auth_type != 'wxapp') {
            $credentials = ['username' => $postData['mobile'], 'password' => $postData['password'], 'company_id' => $authInfo['company_id']];
            $token = app('auth')->guard('h5api')->attempt($credentials);
            return $this->response->array(['token' => $token]);
        } else {
            return $this->response->array($result);
        }
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/member",
     *     summary="修改会员信息",
     *     tags={"会员"},
     *     description="修改会员信息",
     *     operationId="updateMember",
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="country", in="query", description="国家", required=false, type="string"),
     *     @SWG\Parameter( name="province", in="query", description="省份", required=false, type="string"),
     *     @SWG\Parameter( name="city", in="query", description="城市", required=false, type="string"),
     *     @SWG\Parameter( name="language", in="query", description="语言", required=false, type="string"),
     *     @SWG\Parameter( name="username", in="query", description="姓名", required=true, type="string"),
     *     @SWG\Parameter( name="avatar", in="query", description="头像", required=true, type="string"),
     *     @SWG\Parameter( name="sex", in="query", description="性别, 这里给文字，后端根据文字来自行处理", required=true, type="string"),
     *     @SWG\Parameter( name="birthday", in="query", description="生日，例2021-05-10", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="query", description="家庭住址", required=true, type="string"),
     *     @SWG\Parameter( name="email", in="query", description="常用邮箱", required=true, type="string"),
     *     @SWG\Parameter( name="industry", in="query", description="从事行业", required=true, type="string"),
     *     @SWG\Parameter( name="income", in="query", description="年收入", required=true, type="string"),
     *     @SWG\Parameter( name="edu_background", in="query", description="学历, 这里给文字，后端根据文字来自行处理", required=true, type="string"),
     *     @SWG\Parameter( name="habbit.*.name", in="query", description="爱好多选项中的name，即多选项中的文字描述", required=true, type="string", default="游戏"),
     *     @SWG\Parameter( name="habbit.*.ischecked", in="query", description="该爱好是否被选中, true表示被选中, false表示未选中", required=true, type="boolean", default="true"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object", required={"user_id","company_id","grade_id","mobile","user_card_code","offline_card_code","inviter_id","source_from","source_id","monitor_id","latest_source_id","latest_monitor_id","authorizer_appid","use_point","wxa_appid","created","updated","disabled","remarks","third_data","username","avatar","sex","birthday","address","email","industry","income","edu_background","habbit","have_consume"},
     *                 @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                 @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                 @SWG\Property( property="grade_id", type="string", example="4", description="等级ID | 等级id | 会员等级"),
     *                 @SWG\Property( property="mobile", type="string", example="18321148690", description="手机号"),
     *                 @SWG\Property( property="user_card_code", type="string", example="324A50B01181", description="会员卡号"),
     *                 @SWG\Property( property="offline_card_code", type="string", example="null", description="线下会员卡号"),
     *                 @SWG\Property( property="inviter_id", type="string", example="0", description="推荐人id"),
     *                 @SWG\Property( property="source_from", type="string", example="default", description="来源类型 default默认"),
     *                 @SWG\Property( property="source_id", type="string", example="0", description="来源id"),
     *                 @SWG\Property( property="monitor_id", type="string", example="0", description="监控id"),
     *                 @SWG\Property( property="latest_source_id", type="string", example="0", description="最近来源id"),
     *                 @SWG\Property( property="latest_monitor_id", type="string", example="0", description="最近监控页面id"),
     *                 @SWG\Property( property="authorizer_appid", type="string", example="wx6b8c2837f47e8a09", description="公众号的appid"),
     *                 @SWG\Property( property="use_point", type="string", example="false", description="是否可以使用积分"),
     *                 @SWG\Property( property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="小程序的appid"),
     *                 @SWG\Property( property="created", type="string", example="1598845028", description=""),
     *                 @SWG\Property( property="updated", type="string", example="1600917506", description="修改时间"),
     *                 @SWG\Property( property="disabled", type="string", example="false", description="是否禁用 true=禁用,false=启用"),
     *                 @SWG\Property( property="remarks", type="string", example="null", description="备注"),
     *                 @SWG\Property( property="third_data", type="string", example="100102866937", description="百胜等第三方返回的数据"),
     *                 @SWG\Property( property="username", type="string", example="null", description="名称"),
     *                 @SWG\Property( property="avatar", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/28Qz0boz9fjJYJiapHjxu5nNcBKDZMcNlrpctITqfTawwnsw8Wu9Af4k6DzIXlSv01m3nvxV48Nic9JsroJ9NuGA/132", description="头像"),
     *                 @SWG\Property( property="sex", type="string", example="0", description="性别。0 未知；1 男；2 女"),
     *                 @SWG\Property( property="birthday", type="string", example="null", description="出生日期"),
     *                 @SWG\Property( property="address", type="string", example="null", description="具体地址"),
     *                 @SWG\Property( property="email", type="string", example="null", description="常用邮箱"),
     *                 @SWG\Property( property="industry", type="string", example="null", description="所属行业 | 从事行业"),
     *                 @SWG\Property( property="income", type="string", example="null", description="收入"),
     *                 @SWG\Property( property="edu_background", type="string", example="null", description="学历"),
     *                 @SWG\Property( property="habbit", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="爱好"),
     *                 ),
     *                 @SWG\Property( property="have_consume", type="string", example="true", description="是否有消费"),
     *             ),
     *          ),
     *     ),
     * )
     */
    public function updateMember(Request $request)
    {
        // middleware中添加的参数获取方式
        $authInfo = $request->get('auth');
        // 公司id
        $companyId = (int)$authInfo['company_id'];
        $postData = $request->all();
        if (isset($postData['birthday']) && strstr($postData['birthday'], '****-**-*')) {
            unset($postData['birthday']);
        }
        if (isset($postData['address']) && strstr($postData['address'], '******')) {
            unset($postData['address']);
        }
        if (isset($postData['sex']) && (strstr($postData['sex'], '*') || strstr($postData['sex'], '-'))) {
            unset($postData['sex']);
        }
        unset($postData["company_id"], $postData['mobile']);
        if (!$postData) {
            throw new ResourceException(trans('MembersBundle/Members.data_required'));
        }
        // 参数转换
        (new ConfigRequestFieldsService())->transformGetValueByDesc($companyId, ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO, $postData);
        // 验证规则
        $configRequestService = new ValidatorService();
        $requestFields = $configRequestService->check($companyId, ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO, $postData, true);
        // 用户自定义的数据
        $customData = [];
        foreach ($requestFields as $key => $requestField) {
            $isDefault = (bool)($requestField["is_default"] ?? false);
            // 默认的字段或不存在表单中的字段不会被保存进custom_data
            if ($isDefault || !isset($postData[$key])) {
                continue;
            }
            $customData[$key] = $postData[$key];
        }
        // 追加参数
        $postData["other_params"] = [
            "custom_data" => $customData,
            // 前端透传的参数
            "isGetWxInfo" => (bool)$request->input("isGetWxInfo", false)
        ];
        // 设置过滤条件
        $filter = ['user_id' => $authInfo['user_id'], 'company_id' => $companyId];
        // 更新用户信息
        $this->memberService->memberInfoUpdate($postData, $filter);
        // 更新微信用户信息, 如果不存在unionid和openid则有可能是app环境
        if (!empty($authInfo['unionid']) && !empty($authInfo['open_id'])) {
            (new WechatUserService())->update([
                "headimgurl" => (string)($postData["avatar"] ?? ""),
                "country" => (string)$request->input("country"),
                "province" => (string)$request->input("province"),
                "city" => (string)$request->input("city"),
                "sex" => (int)($postData["sex"] ?? 0),
                "language" => (string)$request->input("language"),
                "nickname" => (string)($postData["username"] ?? ""),
            ], [
                'unionid' => $authInfo['unionid'],
                'open_id' => $authInfo['open_id'],
                'company_id' => $companyId,
                'authorizer_appid' => $authInfo['wxapp_appid'],
            ]);
        }
        // 数云模式,去数云更新用户信息
        if (config('common.oem-shuyun')) {
            $this->memberService->shuyunModify($companyId, $authInfo['user_id'], $postData);
        }
        $result = $this->memberService->getMemberInfo($filter);
        event(new UpdateMemberSuccessEvent($result));
        $dmMemberService = new DmMemberService($companyId);
        if ($dmMemberService->isOpen) {
            $dmMemberService->updateMemberInfoByMobile([
                'mobile' => $authInfo['mobile'],
                'name' => $result['username'],
                'sex' => $result['sex'],
                'birthday' => $result['birthday'],
                'email' => $result['email'],
            ]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/memberinfo",
     *     summary="修改会员信息（不使用通用的验证规则）",
     *     tags={"会员"},
     *     description="修改会员信息（不使用通用的验证规则）",
     *     operationId="updateMemberNotUseValidationConfig",
     *     @SWG\Parameter( name="username", in="query", description="姓名", required=true, type="string"),
     *     @SWG\Parameter( name="avatar", in="query", description="头像", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(required={"data"},
     *             @SWG\Property(
     *                 property="data",
     *                 type="object", required={"user_id","company_id","grade_id","mobile","user_card_code","offline_card_code","inviter_id","source_from","source_id","monitor_id","latest_source_id","latest_monitor_id","authorizer_appid","use_point","wxa_appid","created","updated","disabled","remarks","third_data","username","avatar","sex","birthday","address","email","industry","income","edu_background","habbit","have_consume"},
     *                 @SWG\Property( property="user_id", type="string", default="20264", description="用户id"),
     *                 @SWG\Property( property="company_id", type="string", default="1", description="公司id"),
     *                 @SWG\Property( property="grade_id", type="string", default="4", description="等级ID | 等级id | 会员等级"),
     *                 @SWG\Property( property="mobile", type="string", default="18321148690", description="手机号"),
     *                 @SWG\Property( property="user_card_code", type="string", default="324A50B01181", description="会员卡号"),
     *                 @SWG\Property( property="offline_card_code", type="string", default="null", description="线下会员卡号"),
     *                 @SWG\Property( property="inviter_id", type="string", default="0", description="推荐人id"),
     *                 @SWG\Property( property="source_from", type="string", default="default", description="来源类型 default默认"),
     *                 @SWG\Property( property="source_id", type="string", default="0", description="来源id"),
     *                 @SWG\Property( property="monitor_id", type="string", default="0", description="监控id"),
     *                 @SWG\Property( property="latest_source_id", type="string", default="0", description="最近来源id"),
     *                 @SWG\Property( property="latest_monitor_id", type="string", default="0", description="最近监控页面id"),
     *                 @SWG\Property( property="authorizer_appid", type="string", default="wx6b8c2837f47e8a09", description="公众号的appid"),
     *                 @SWG\Property( property="use_point", type="string", default="false", description="是否可以使用积分"),
     *                 @SWG\Property( property="wxa_appid", type="string", default="wx912913df9fef6ddd", description="小程序的appid"),
     *                 @SWG\Property( property="created", type="string", default="1598845028", description=""),
     *                 @SWG\Property( property="updated", type="string", default="1600917506", description="修改时间"),
     *                 @SWG\Property( property="disabled", type="string", default="false", description="是否禁用 true=禁用,false=启用"),
     *                 @SWG\Property( property="remarks", type="string", default="null", description="备注"),
     *                 @SWG\Property( property="third_data", type="string", default="100102866937", description="百胜等第三方返回的数据"),
     *                 @SWG\Property( property="username", type="string", default="null", description="名称"),
     *                 @SWG\Property( property="avatar", type="string", default="https://thirdwx.qlogo.cn/mmopen/vi_32/28Qz0boz9fjJYJiapHjxu5nNcBKDZMcNlrpctITqfTawwnsw8Wu9Af4k6DzIXlSv01m3nvxV48Nic9JsroJ9NuGA/132", description="头像"),
     *                 @SWG\Property( property="sex", type="string", default="0", description="性别。0 未知；1 男；2 女"),
     *                 @SWG\Property( property="birthday", type="string", default="null", description="出生日期"),
     *                 @SWG\Property( property="address", type="string", default="null", description="具体地址"),
     *                 @SWG\Property( property="email", type="string", default="null", description="常用邮箱"),
     *                 @SWG\Property( property="industry", type="string", default="null", description="所属行业 | 从事行业"),
     *                 @SWG\Property( property="income", type="string", default="null", description="收入"),
     *                 @SWG\Property( property="edu_background", type="string", default="null", description="学历"),
     *                 @SWG\Property( property="habbit", type="array",
     *                      @SWG\Items( type="string", default="undefined", description="爱好"),
     *                 ),
     *                 @SWG\Property( property="have_consume", type="string", default="true", description="是否有消费"),
     *             ),
     *          ),
     *     ),
     * )
     */
    public function updateMemberNotUseValidationConfig(Request $request)
    {
        // middleware中添加的参数获取方式
        $authInfo = $request->get('auth');
        // 公司id
        $companyId = (int)$authInfo['company_id'];
        // 参数验证
        $requestData = $request->only(["username", "avatar"]);
        // if ($messageBag = validation($requestData, [
        //     "username" => ["required"],
        //     "avatar" => ["required"],
        // ], [
        //     "username.*" => "会员昵称参数有误",
        //     "avatar.*" => "会员头像参数有误",
        // ])) {
        //     throw new ResourceException($messageBag->first());
        // }
        // 设置过滤条件
        $filter = ['user_id' => $authInfo['user_id'], 'company_id' => $companyId];
        // 更新用户信息
        $this->memberService->memberInfoUpdate($requestData, $filter);
        if ($authInfo['unionid'] && $authInfo['open_id']) {
            // 更新微信用户信息
            (new WechatUserService())->update([
                "headimgurl" => (string)($requestData["avatar"] ?? ""),
                "nickname" => (string)($requestData["username"] ?? ""),
            ], [
                'unionid' => $authInfo['unionid'],
                'open_id' => $authInfo['open_id'],
                'company_id' => $companyId,
                'authorizer_appid' => $authInfo['wxapp_appid'],
            ]);
        }
        $result = $this->memberService->getMemberInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/member/mobile",
     *     tags={"会员"},
     *     summary="更新会员手机号",
     *     description="更新会员手机号",
     *     operationId="updateMemberMobile",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="old_mobile", in="query", description="会员的旧手机", required=true, type="string"),
     *     @SWG\Parameter(name="old_region_mobile", in="query", description="会员的旧手机（带区号）", required=false, type="string"),
     *     @SWG\Parameter(name="old_country_code", in="query", description="会员的旧手机的区号", required=false, type="string"),
     *     @SWG\Parameter(name="new_mobile", in="query", description="会员的新手机", required=true, type="string"),
     *     @SWG\Parameter(name="new_region_mobile", in="query", description="会员的新手机（带区号）", required=false, type="string"),
     *     @SWG\Parameter(name="new_country_code", in="query", description="会员的新手机的区号", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="", required={"user_id", "company_id","grade_id","mobile","region_mobile","user_card_code","offline_card_code","inviter_id","source_from","source_id","monitor_id","latest_source_id","latest_monitor_id","authorizer_appid","use_point","wxa_appid","created","updated","disabled","remarks","third_data"},
     *               @SWG\Property(property="user_id", type="string", example="20320", description=""),
     *               @SWG\Property(property="company_id", type="string", example="43", description=""),
     *               @SWG\Property(property="grade_id", type="string", example="52", description=""),
     *               @SWG\Property(property="mobile", type="string", example="17321265274", description=""),
     *               @SWG\Property(property="region_mobile", type="string", example="17321265274", description=""),
     *               @SWG\Property(property="user_card_code", type="string", example="D5A2088045A1", description=""),
     *               @SWG\Property(property="offline_card_code", type="string", example="", description=""),
     *               @SWG\Property(property="inviter_id", type="string", example="0", description=""),
     *               @SWG\Property(property="source_from", type="string", example="default", description=""),
     *               @SWG\Property(property="source_id", type="string", example="0", description=""),
     *               @SWG\Property(property="monitor_id", type="string", example="0", description=""),
     *               @SWG\Property(property="latest_source_id", type="string", example="0", description=""),
     *               @SWG\Property(property="latest_monitor_id", type="string", example="0", description=""),
     *               @SWG\Property(property="authorizer_appid", type="string", example="", description=""),
     *               @SWG\Property(property="use_point", type="string", example="", description=""),
     *               @SWG\Property(property="wxa_appid", type="string", example="wxbc41819b322cbd3f", description=""),
     *               @SWG\Property(property="created", type="integer", example="1620379456", description=""),
     *               @SWG\Property(property="updated", type="integer", example="1620467714", description=""),
     *               @SWG\Property(property="disabled", type="string", example="", description=""),
     *               @SWG\Property(property="remarks", type="string", example="", description=""),
     *               @SWG\Property(property="third_data", type="string", example="", description=""),
     *            ),
     *         ),
     *     )
     * )
     */
    public function updateMemberMobile(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = (int)$authInfo["company_id"];
        $status = false;
        $result = [];
        // 过期时间
        $ttl = env("UPDATE_MEMBER_MOBILE_TTL", 3600 * 24 * 30);
        // 30天内只可修改一次
        (new RedisCacheService($companyId, sprintf("updateMemberMobileCD:%d", $authInfo["user_id"]), $ttl))
            ->setConnection("members")
            ->get(function () use ($request, $authInfo, $companyId, &$status, &$result) {
                // 原来的手机号信息
                $oldData = [
                    "mobile" => (string)$request->input("old_mobile"),
                    "region_mobile" => (string)$request->input("old_region_mobile"),
                    "country_code" => (string)$request->input("old_country_code"),
                ];
                // 新的手机信息
                $newData = [
                    "mobile" => (string)$request->input("new_mobile"),
                    "region_mobile" => (string)$request->input("new_region_mobile"),
                    "country_code" => (string)$request->input("new_country_code"),
                ];
                // 新手机号验证
                if (!preg_match('/^1[3456789]{1}[0-9]{9}$/', $newData["mobile"])) {
                    throw new ResourceException(trans('MembersBundle/Members.enter_valid_mobile'));
                }
                // 验证码
                $knowSmsCode = (new MemberRegSettingService())->loadSmsVcode($newData["mobile"], $companyId, "update");
                if ($request->input("smsCode") != $knowSmsCode) {
                    throw new ResourceException("短信验证码错误");
                }
                // 更新手机号
                $result = (new MemberService())->updateMemberMobile($newData, [
                    "company_id" => $authInfo["company_id"],
                    "user_id" => $authInfo["user_id"]
                ]);
                //记录操作日志
                if ($result) {
                    (new MemberOperateLogService())->create([
                        'user_id' => $authInfo["user_id"],
                        'company_id' => $authInfo["company_id"],
                        'operate_type' => 'mobile',
                        'old_data' => jsonEncode($oldData),
                        'new_data' => jsonEncode($newData),
                        'operater' => "用户在云店自行修改",
                    ]);
                }
                $status = true;
                return true;
            });

        if (!$status) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_can_change_once_in_30_days'));
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/reset/password",
     *     summary="修改密码",
     *     tags={"会员"},
     *     description="修改密码",
     *     operationId="resetMemberPassword",
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string", required=true),
     *     @SWG\Parameter( name="password", in="query", description="密码", type="string", required=true),
     *     @SWG\Parameter( name="vcode", in="query", description="短信验证码，新用户注册不需要短信验证码", type="string", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Member",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function resetMemberPassword(Request $request)
    {
        $postData = $request->all();
        // middleware中添加的参数获取方式
        $authInfo = $request->get('auth');

        if ($messageBag = validation($postData, [
            'mobile' => 'required',
            'password' => ['required', 'alpha_num', 'between:6,16'],
            'vcode' => 'nullable',
        ], [
            "mobile.required" => "手机号必填！",
            "password.required" => "密码必填！",
            "password.alpha_num" => "密码只能是字母和数字的组合！",
            "password.between" => "密码长度6～16个字符之间！",
        ])) {
            throw new ResourceException($messageBag->first());
        }

        $memberService = new MemberService();

        if ($authInfo['user_id'] ?? 0) {
            $memberInfo = $memberService->getMemberInfo(['user_id' => $authInfo['user_id'], 'company_id' => $authInfo['company_id']]);
            $postData['mobile'] = $memberInfo['mobile'];
        } else {
            $memberInfo = $memberService->getMemberInfo(['mobile' => $postData['mobile'], 'company_id' => $authInfo['company_id']]);
        }
        if (!$memberInfo) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_not_registered'));
        }
        $regSettinService = new MemberRegSettingService();

        if (isset($postData['vcode']) && !$regSettinService->checkSmsVcode($postData['mobile'], $authInfo['company_id'], $postData['vcode'], 'forgot_password')) {
            throw new ResourceException('短信验证码错误');
        }


        // 小程序注册默认生成随机密码
        if (isset($postData['api_from']) && $postData['api_from'] == 'wechat') {
            $postData['password'] = substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10); // 生成随机密码
        }
        $postData['password'] = password_hash($postData['password'], PASSWORD_DEFAULT);

        $result = $this->memberService->updateMemberInfo($postData, ['mobile' => $postData['mobile'], 'company_id' => $authInfo['company_id']]);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/image/code",
     *     summary="获取图片验证码",
     *     tags={"会员"},
     *     description="获取图片验证码",
     *     operationId="getImageVcode",
     *     @SWG\Parameter( name="type", in="query", description="类型: sign, forgot_password,login", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="imageToken", type="string", example="0728afec66e66aadd95827ddc883b04d", description="图片token"),
     *                  @SWG\Property( property="imageData", type="string", example="data:image/png;base64,/9j/4AAQSkZJRgABAQEAYABgAA...", description="base64图片"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getImageVcode(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $type = $request->input('type', 'sign');

        if (!in_array($type, $this->code_type)) {
            throw new ResourceException(trans('MembersBundle/Members.image_captcha_type_error'));
        }

        $memberRegSettingService = new MemberRegSettingService();
        list($token, $imgData) = $memberRegSettingService->generateImageVcode($companyId, $type);
        return $this->response->array([
            'imageToken' => $token,
            "imageData" => $imgData,
        ]);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/member/sms/code",
     *     summary="获取手机短信验证码",
     *     tags={"会员"},
     *     description="获取手机短信验证码",
     *     operationId="getImageVcode",
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="token",
     *         in="query",
     *         description="图片验证码token",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="yzm",
     *         in="query",
     *         description="图片验证码的值",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         description="验证码类型 【sign 注册验证码】【forget_password 重置密码验证码】【login 登录验证码】【update 修改手机号】",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="message", type="string", example="短信发送成功", description="描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getSmsCode(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $phone = $request->input('mobile');
        if (!$phone && preg_match("/^1\d{10}$/", $phone)) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_error'));
        }
        $type = $request->input('type', 'sign');

        if (!in_array($type, $this->code_type)) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_verification_type_error'));
        }
        // 校验手机号是否注册
        $memberService = new MemberService();
        if (($authInfo['user_id'] ?? 0) && $type == 'forgot_password') {
            $memberInfo = $memberService->getMemberInfo(['user_id' => $authInfo['user_id'], 'company_id' => $authInfo['company_id']]);
            $phone = $memberInfo['mobile'];
        } else {
            $memberInfo = $memberService->getMemberInfo(['mobile' => $phone, 'company_id' => $authInfo['company_id']]);
        }
        if ($memberInfo && $type == 'sign') {
            if (!isset($memberInfo['other_params']['is_upload_member']) || $memberInfo['other_params']['is_upload_member'] != true) {
                throw new ResourceException(trans('MembersBundle/Members.mobile_already_registered'));
            }
        } elseif (!$memberInfo && $type == 'forgot_password') {
            throw new ResourceException(trans('MembersBundle/Members.mobile_not_registered_yet'));
        }


        $memberRegSettingService = new MemberRegSettingService();
        // 更新手机没有图片验证码
        if ($type != 'update') {
            $token = $request->input('token');
            $yzmcode = $request->input('yzm');
            if (!$memberRegSettingService->checkImageVcode($token, $companyId, $yzmcode, $type)) {
                throw new ResourceException(trans('MembersBundle/Members.image_captcha_error'));
            }
        }
        if ($type == 'sign') {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);

            $exist = $membersAssociationsRepository->get(['user_id' => $authInfo['user_id'], 'unionid' => $authInfo['unionid'], 'company_id' => $authInfo['company_id'], 'user_type' => 'baidu']);
            if ($exist) {
                if (!isset($memberInfo['other_params']['is_upload_member']) || $memberInfo['other_params']['is_upload_member'] != true) {
                    throw new ResourceException(trans('MembersBundle/Members.account_already_bound_mobile'));
                }
            }
        }
        $memberRegSettingService->generateSmsVcode($phone, $companyId, $type);
        return $this->response->array(['message' => "短信发送成功"]);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/barcode",
     *     summary="获取条形码/二维码",
     *     tags={"会员"},
     *     description="获取条形码/二维码",
     *     operationId="getBarcode",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="barcode_url", type="string", example="data:image/png;base64,/9j/4AAQSkZJRgABAQEAYABgAA...", description="base64条形码"),
     *                  @SWG\Property( property="qrcode_url", type="string", example="data:image/png;base64,/9j/4AAQSkZJRgABAQEAYABgAA...", description="base64二维码图片"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getBarcode(Request $request)
    {
        $authInfo = $request->get('auth');
        $content = $authInfo['user_card_code'];
        if (!$content) {
            return $this->response->error('获取失败！', 411);
        }

        $result = $this->memberService->generateBarCode($content);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/decryptPhoneInfo",
     *     summary="获取授权手机号-仅支持微信",
     *     tags={"会员"},
     *     description="手机号授权解密获取手机号",
     *     operationId="getDecryptPhoneNumber",
     *     @SWG\Parameter( name="encryptedData", in="query", description="小程序登录时用户授权手机号的加密数据", required=true, type="string"),
     *     @SWG\Parameter( name="iv", in="query", description="小程序登录时用户授权手机号加密算法的初始向量", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getDecryptPhoneNumber(Request $request)
    {
        $authInfo = $request->get('auth');

        $inputData = $request->input();
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($authInfo['wxapp_appid']);
        $wxParams = [
            'code' => $inputData['code'],
            'appid' => $authInfo['wxapp_appid'],
        ];
        $res = $app->auth->session($inputData['code']); //调用微信获取sessionkey接口，返回session_key,openid,unionid

        if (empty($res['session_key'])) {
            throw new StoreResourceFailedException('用户登录失败！');
        }

        $data = $app->encryptor->decryptData($res['session_key'], $inputData['iv'], $inputData['encryptedData']);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/addresslist",
     *     summary="会员地址列表",
     *     tags={"会员"},
     *     description="地址列表",
     *     operationId="getAddressList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter( name="address_id", in="query", description="地址列表", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="条数", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/Address",
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getAddressList(Request $request)
    {
        //$settingService = new SelfdeliveryAddressService();
        //$selfDeliveryAddress = $settingService->selfdeliveryAddressGet($authInfo['company_id']);
        $inputData = $request->input();

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $orderBy = ['is_def' => 'desc', 'updated' => 'desc'];
        $memberAddressService = new MemberAddressService();
        if ($id = $request->input('address_id')) {
            $filter['address_id'] = $id;
        }
        if ($request->input('receipt_type', '') == 'dada' && $request->input('city', '')) {
            $filter['city|contains'] = mb_trim($request->input('city'), '市');
        }


        if(isset($inputData['promoter_user_id'])
        && $inputData['promoter_user_id']
        &&  $authInfo['user_id'] == $inputData['promoter_user_id'] ){
            $filter['user_id'] = $inputData['buy_user_id'] ;
        }

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);
        $result = $memberAddressService->lists($filter, $page, $pageSize, $orderBy);

        //if ($selfDeliveryAddress) {
        //    foreach ($selfDeliveryAddress as $address) {
        //        $address['isSelfDelivery'] = true;
        //        array_push($result['list'], $address);
        //    }
        //    $result['total_count'] += count($selfDeliveryAddress);
        //}
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/address/{address_id}",
     *     summary="地址详情",
     *     tags={"会员"},
     *     description="地址详情",
     *     operationId="getAddress",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="address_id", in="path", description="地址id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Address",
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getAddress($address_id, Request $request)
    {
        $params['address_id'] = $address_id;
        $validator = app('validator')->make($params, [
            'address_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('MembersBundle/Members.query_address_detail_error'), $validator->errors());
        }

        $authInfo = $request->get('auth');
        $memberAddressService = new MemberAddressService();
        $filter = [
            'address_id' => $address_id,
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id'],
        ];
        $result = $memberAddressService->getInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/address",
     *     summary="添加收货地址",
     *     tags={"会员"},
     *     description="添加收货地址",
     *     operationId="createAddress",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="username", in="formData", description="收货人", required=true, type="string"),
     *     @SWG\Parameter( name="telephone", in="formData", description="手机", required=true, type="string"),
     *     @SWG\Parameter( name="province", in="formData", description="省", type="string"),
     *     @SWG\Parameter( name="city", in="formData", description="市", type="string"),
     *     @SWG\Parameter( name="county", in="formData", description="区县", type="string"),
     *     @SWG\Parameter( name="adrdetail", in="formData", description="详细地址", type="string"),
     *     @SWG\Parameter( name="postalCode", in="formData", description="邮编", type="string"),
     *     @SWG\Parameter( name="is_def", in="formData", description="是否默认地址", type="boolean"),
     *     @SWG\Parameter( name="third_data", in="formData", description="第三方数据 目前仅限dw使用", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Address",
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function createAddress(Request $request)
    {
        $params = $request->input();
        $params['is_def'] = $params['is_def'] ?? 0;
        // if (!isset($params['postalCode'])) {
        //     $params['postalCode'] = '000000';
        // }

        $rules = [
            'username' => ['required|zhstring', '请填写正确的收货人姓名'],
            'telephone' => ['required|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            // 'postalCode' => ['required|postcode', '请填写正确的邮政编码'],
            'province' => ['required|zhstring', '请填写正确的省份'],
            'city' => ['required|zhstring', '请填写正确的城市'],
            'county' => ['required|zhstring', '请填写正确的区/县'],
            'adrdetail' => ['required', '请填写正确的详细地址'],
            //            'is_def' => ['required|in:0,1', '默认数值错误'],
            //             'postalCode' => 'required',
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if (isset($params['is_def']) && !in_array($params['is_def'], [0, 1])) {
            throw new ResourceException(trans('MembersBundle/Members.default_address_enable_error'));
        }

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];

        if(isset($params['promoter_user_id']) && $params['promoter_user_id'] == $authInfo['user_id']
        && $params['buy_user_id']){
            $params['user_id'] =  $params['buy_user_id'];
            // $filter['promoter_user_id'] =  $params['promoter_user_id'];
        }

        $memberAddressService = new MemberAddressService();
        $result = $memberAddressService->createAddress($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/member/address/{address_id}",
     *     summary="修改收货地址",
     *     tags={"会员"},
     *     description="修改收货地址",
     *     operationId="updateAddress",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="address_id", in="path", description="地址id", required=true, type="string"),
     *     @SWG\Parameter( name="username", in="formData", description="收货人", required=true, type="string"),
     *     @SWG\Parameter( name="telephone", in="formData", description="手机", required=true, type="string"),
     *     @SWG\Parameter( name="province", in="formData", description="省", type="string"),
     *     @SWG\Parameter( name="city", in="formData", description="市", type="string"),
     *     @SWG\Parameter( name="county", in="formData", description="区县", type="string"),
     *     @SWG\Parameter( name="adrdetail", in="formData", description="详细地址", type="string"),
     *     @SWG\Parameter( name="postalCode", in="formData", description="邮编", type="string"),
     *     @SWG\Parameter( name="is_def", in="formData", description="是否默认地址", type="boolean"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Address",
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateAddress($address_id, Request $request)
    {
        $params = $request->input();
        $params['address_id'] = $address_id;

        // if (!isset($params['postalCode'])) {
        //     $params['postalCode'] = '000000';
        // }

        $rules = [
            'address_id' => ['required|numeric|min:1', '缺少地址id'],
            'username' => ['required|zhstring', '请填写正确的收货人姓名'],
            'telephone' => ['required|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            // 'postalCode' => ['required|postcode', '请填写正确的邮政编码'],
            'province' => ['required|zhstring', '请填写正确的省份'],
            'city' => ['required|zhstring', '请填写正确的城市'],
            'county' => ['required|zhstring', '请填写正确的区/县'],
            'adrdetail' => ['required', '请填写正确的详细地址'],
            //            'is_def' => ['required|in:0,1', '默认数值错误'],
            //             'postalCode' => 'required',
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if (isset($params['is_def'])) {
            $params['is_def'] = $params['is_def'] === 'true' || $params['is_def'] === '1';
        }

        $authInfo = $request->get('auth');

        $filter['address_id'] = $address_id;
        $filter['user_id'] = $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];
        //替换todo
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $memberAddressService = new MemberAddressService();
        $result = $memberAddressService->updateAddress($filter, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/member/address/{address_id}",
     *     summary="删除地址",
     *     tags={"会员"},
     *     description="删除地址",
     *     operationId="deleteAddress",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter(name="address_id", in="path", description="地址id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *                  @SWG\Property( property="address_id", type="string", example="534", description="地址id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteAddress($address_id, Request $request)
    {
        $params['address_id'] = $address_id;
        $validator = app('validator')->make($params, [
            'address_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('MembersBundle/Members.delete_address_error'), $validator->errors());
        }

        $authInfo = $request->get('auth');
        $memberAddressService = new MemberAddressService();
        $params = [
            'address_id' => $address_id,
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id'],
        ];
        $result = $memberAddressService->deleteBy($params);

        return $this->response->array(['status' => $result, 'address_id' => $address_id]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/address_area",
     *     summary="地区列表(废弃)",
     *     tags={"会员"},
     *     description="地区列表(废弃)",
     *     operationId="getAddressArea",
     *     @SWG\Response(response=200, description="成功返回结构"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getAddressArea(Request $request)
    {
        $data = json_decode(file_get_contents(storage_path('static/district.json')), true);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/collect/item",
     *     summary="会员收藏商品列表",
     *     tags={"会员"},
     *     description="商品收藏列表",
     *     operationId="getItemsFavList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(name="page", in="path", description="页码", required=false, type="string"),
     *     @SWG\Parameter(name="pageSize", in="path", description="条数", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="fav_id", type="string", example="367", description="收藏id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                          @SWG\Property( property="item_id", type="string", example="2017", description="商品id"),
     *                          @SWG\Property( property="item_name", type="string", example="兔兔1 有规格名字要长名字要长名字要长名字要长名字要长名字要", description="商品名称"),
     *                          @SWG\Property( property="item_image", type="string", example="http://bbctest.aixue7.com/1/2019/08/23/63c0d6a721dc6d20213a35a87e8fd452UMboiZvdn5Y0ebejT6JvbbknokFkjEei", description="商品图片"),
     *                          @SWG\Property( property="item_price", type="string", example="9500", description="商品价格"),
     *                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型 normal:普通商品 pointsmall:积分商城商品"),
     *                          @SWG\Property( property="point", type="string", example="9500", description="积分商城商品的积分价格"),
     *                          @SWG\Property( property="created", type="string", example="1611669453", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1611669453", description="修改时间"),
     *                          @SWG\Property( property="price", type="string", example="9500", description="商品价格"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getItemsFavList(Request $request)
    {
        $distributorService = new DistributorService();
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        $pageSize = $request->input('pageSize', 100);
        $page = $request->input('page', 1);
        $memberItemsFavService = new MemberItemsFavService();
        $result = $memberItemsFavService->lists($filter, $page, $pageSize);
        if ($request->get('distributor_id', null)) {
            $params['distributor_id'] = $request->get('distributor_id');
        }
        $params['company_id'] = $authInfo['company_id'];
        $params['item_id'] = array_column($result['list'], 'item_id');
        $itemsService = new ItemsService();
        $itemList = $itemsService->getItemListData($params, 1, $pageSize);
        $itemList = $itemsService->getItemsListMemberPrice($itemList, $authInfo['user_id'], $authInfo['company_id']);
        //营销标签
        $itemList = $itemsService->getItemsListActityTag($itemList, $authInfo['company_id']);
        $itemList = array_column($itemList['list'], null, 'item_id');
        foreach ($result['list'] as $key => $value) {
            if (isset($itemList[$value['item_id']])) {
                if ($itemList[$value['item_id']]['distributor_id']) {
                    $result['list'][$key]['distributor_id'] = $itemList[$value['item_id']]['distributor_id'];
                }

                $result['list'][$key]['price'] = $itemList[$value['item_id']]['price'];
                $result['list'][$key]['market_price'] = floatval($itemList[$value['item_id']]['market_price']);

                if (isset($itemList[$value['item_id']]['member_price'])) {
                    $result['list'][$key]['price'] = $itemList[$value['item_id']]['member_price'];
                }

                if (isset($itemList[$value['item_id']]['activity_price'])) {
                    $result['list'][$key]['price'] = $itemList[$value['item_id']]['activity_price'];
                }
            }

            if (isset($params['distributor_id']) && !($result['list'][$key]['distributor_id'] ?? 0)) {
                $result['list'][$key]['distributor_id'] = $params['distributor_id'];
            }
        }
        if ($result['list']) {
            $result['list'] = array_values($result['list']);
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/collect/item/num",
     *     summary="会员收藏商品数量",
     *     tags={"会员"},
     *     description="会员收藏商品数量",
     *     operationId="getItemsFavNum",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="fav_total_count", type="string", example="1", description="收藏总数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getItemsFavNum(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        $memberItemsFavService = new MemberItemsFavService();
        $result['fav_total_count'] = $memberItemsFavService->count($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/collect/item/{item_id}",
     *     summary="添加收藏商品",
     *     tags={"会员"},
     *     description="添加收藏商品",
     *     operationId="addItemsFav",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品id", required=true, type="string"),
     *     @SWG\Parameter( name="item_type", in="formData", description="商品类型 normal:普通商品 poingsmall:积分商城商品  默认:normal", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="fav_id", type="string", example="367", description="收藏id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                  @SWG\Property( property="item_id", type="string", example="2017", description="商品id"),
     *                  @SWG\Property( property="item_name", type="string", example="兔兔1 有规格名字要长名字要长名字要长名字要长名字要长名字要", description="商品名称"),
     *                  @SWG\Property( property="item_image", type="string", example="http://bbctest.aixue7.com/1/2019/08/23/63c0d6a721dc6d20213a35a87e8fd452UMboiZvdn5Y0ebejT6JvbbknokFkjEei", description="商品图片"),
     *                  @SWG\Property( property="item_price", type="string", example="9500", description="商品价格（分）"),
     *                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型 normal:普通商品 pointsmall:积分商城商品"),
     *                  @SWG\Property( property="point", type="string", example="9500", description="积分商城商品的积分价格"),
     *                  @SWG\Property( property="created", type="string", example="1611669453", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1611669453", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function addItemsFav($item_id, Request $request)
    {
        $params['item_id'] = $item_id;

        $rules = [
            'item_id' => ['required', '没有选择收藏的商品'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['item_type'] = $request->get('item_type', 'normal');
        $memberItemsFavService = new MemberItemsFavService();

        $result = $memberItemsFavService->addItemsFav($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/member/collect/item",
     *     summary="删除收藏商品",
     *     tags={"会员"},
     *     description="删除收藏商品",
     *     operationId="deleteItemsFav",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="item_ids", in="formData", description="商品集合", required=true, type="string"),
     *     @SWG\Parameter( name="is_empty", in="formData", description="in:true,false", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="1", description="结果状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteItemsFav(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'item_ids' => 'required_if:is_empty,false',
            'is_empty' => 'required|in:true,false',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('MembersBundle/Members.delete_favorite_item_error'), $validator->errors());
        }

        $authInfo = $request->get('auth');
        $memberItemsFavService = new MemberItemsFavService();
        $params = [
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id'],
            'item_ids' => $params['item_ids'] ?? [],
            'is_empty' => (isset($params['is_empty']) && $params['is_empty'] == 'true') ? true : false,
        ];
        $result = $memberItemsFavService->removeItemsFav($params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/browse/history/list",
     *     summary="获取商品浏览记录",
     *     tags={"会员"},
     *     description="获取商品浏览记录",
     *     operationId="getBrowseHistory",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="40", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="history_id", type="string", example="3249", description="id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                          @SWG\Property( property="item_id", type="string", example="5093", description="商品id"),
     *                          @SWG\Property( property="created", type="string", example="1605788548", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1609750730", description="修改时间"),
     *                          @SWG\Property( property="itemData", type="object",
     *                                  @SWG\Property( property="item_id", type="string", example="5093", description="商品id"),
     *                                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                                  @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                                  @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                                  @SWG\Property( property="store", type="string", example="11856", description="商品库存"),
     *                                  @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                                  @SWG\Property( property="sales", type="string", example="147", description="商品销量"),
     *                                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                                  @SWG\Property( property="rebate", type="string", example="0", description=""),
     *                                  @SWG\Property( property="rebate_conf", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                                  @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                                  @SWG\Property( property="point", type="string", example="0", description="积分"),
     *                                  @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                                  @SWG\Property( property="goods_id", type="string", example="5093", description="商品集合ID"),
     *                                  @SWG\Property( property="brand_id", type="string", example="1382", description="品牌id"),
     *                                  @SWG\Property( property="item_name", type="string", example="大屏测试", description="商品名称"),
     *                                  @SWG\Property( property="item_unit", type="string", example="测试", description="商品计量单位"),
     *                                  @SWG\Property( property="item_bn", type="string", example="S5FAD27C9C2C44", description="商品编码"),
     *                                  @SWG\Property( property="brief", type="string", example="测试", description="图片简介"),
     *                                  @SWG\Property( property="price", type="string", example="1", description="商品价格"),
     *                                  @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                                  @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                                  @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                                  @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                                  @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                                  @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                                  @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                                  @SWG\Property( property="item_address_province", type="string", example="110000", description="产地省"),
     *                                  @SWG\Property( property="item_address_city", type="string", example="110100", description="产地市"),
     *                                  @SWG\Property( property="regions_id", type="string", example="110000,110100,110101", description="产地地区id"),
     *                                  @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                                  @SWG\Property( property="templates_id", type="string", example="1", description="运费模板id"),
     *                                  @SWG\Property( property="is_default", type="string", example="true", description="商品是否为默认商品"),
     *                                  @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                                  @SWG\Property( property="default_item_id", type="string", example="5093", description="默认商品ID"),
     *                                  @SWG\Property( property="pics", type="array",
     *                                      @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrfFPfB9nyal8uqQaYfmI3c35JFglTPqzE1t9HHZ9MbSULmHhzUYWdajVP05FcWwmCmGfibPAeFgFag/0?wx_fmt=jpeg", description="图片"),
     *                                  ),
     *                                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                                  @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后 | 有效期的类型 | 有效期的类型, DATE_TYPE_FIX_TIME_RANGE: 指定日期范围内，DATE_TYPE_FIX_TERM:固定天数后"),
     *                                  @SWG\Property( property="item_category", type="string", example="1688", description="商品主类目"),
     *                                  @SWG\Property( property="rebate_type", type="string", example="default", description="分佣计算方式"),
     *                                  @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
     *                                  @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                                  @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                                  @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                                  @SWG\Property( property="tax_rate", type="string", example="0", description="税率"),
     *                                  @SWG\Property( property="created", type="string", example="1605183433", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1609837742", description="修改时间"),
     *                                  @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                                  @SWG\Property( property="videos", type="string", example="https://bbctest.aixue7.com/videos/1/2020/11/11/f2c46179d4b42302bfc91f4c537f4ab2NKrIkwJoyHsqjyPlBa7v11UixGZ0RZ30", description="视频"),
     *                                  @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                                  @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                                  @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                                  @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                                  @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                                  @SWG\Property( property="profit_type", type="string", example="0", description="分润类型"),
     *                                  @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                                  @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                                  @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                                  @SWG\Property( property="type", type="string", example="0", description="商品类型，0普通，1跨境商品，可扩展"),
     *                                  @SWG\Property( property="tdk_content", type="string", example="null", description="tdk详情"),
     *                                  @SWG\Property( property="itemId", type="string", example="5093", description="商品ID"),
     *                                  @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                                  @SWG\Property( property="itemName", type="string", example="大屏测试", description="商品名称"),
     *                                  @SWG\Property( property="itemBn", type="string", example="S5FAD27C9C2C44", description="货号"),
     *                                  @SWG\Property( property="companyId", type="string", example="1", description="公司ID"),
     *                                  @SWG\Property( property="item_main_cat_id", type="string", example="1688", description=""),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getBrowseHistory(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);
        $memberBrowseHistoryServiceService = new MemberBrowseHistoryService();
        $result = $memberBrowseHistoryServiceService->getBrowseHistoryList($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/browse/history/save",
     *     summary="保存商品浏览记录",
     *     tags={"会员"},
     *     description="保存商品浏览记录",
     *     operationId="saveBrowseHistory",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="query",
     *         description="商品ID",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function saveBrowseHistory(Request $request)
    {
        $params = $request->input();

        $validator = app('validator')->make($params, [
            'item_id' => 'required',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('MembersBundle/Members.param_error'), $validator->errors());
        }

        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);
        $memberBrowseHistoryServiceService = new MemberBrowseHistoryService();
        $result = $memberBrowseHistoryServiceService->saveBrowseHistory($params);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/collect/article",
     *     summary="会员收藏心愿单列表",
     *     tags={"会员"},
     *     description="会员收藏心愿单列表",
     *     operationId="getArticleFavList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="string", required=false),
     *     @SWG\Parameter( name="pageSize", in="query", description="条数", type="string", required=false),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="article_id", type="string", example="1", description="文章id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="title", type="string", example="秃头少女自救指南！黄致列同款韩国TS洗发水有多好用？", description="标题"),
     *                          @SWG\Property( property="summary", type="string", example="#熬夜少女必备#", description="摘要"),
     *                          @SWG\Property( property="content", type="string", example="", description="文章详细内容"),
     *                          @SWG\Property( property="sort", type="string", example="3", description="排序"),
     *                          @SWG\Property( property="image_url", type="string", example="https://cdn.watsonsestore.com.cn/image/3/2020/05/07/b9e0ec86d4103d8e2cc50aeb105c4f85LqtPdlEcrZnZI1JRrVhI6KzhTrXcSaiX", description="文章封面"),
     *                          @SWG\Property( property="share_image_url", type="string", example="null", description="分享图片"),
     *                          @SWG\Property( property="created", type="string", example="1561953223", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1593771492", description="修改时间"),
     *                          @SWG\Property( property="author", type="string", example="Marionnaud玛莉娜", description="作者"),
     *                          @SWG\Property( property="operator_id", type="string", example="1", description="作者id"),
     *                          @SWG\Property( property="release_time", type="string", example="1571816150", description="文章发布时间"),
     *                          @SWG\Property( property="article_type", type="string", example="bring", description="文章类型，general:普通文章; bring:带货文章"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="head_portrait", type="string", example="https://cdn.watsonsestore.com.cn/3/2019/12/23/c1edf9d380e74ca95a21a0a7455c4557yoPMQMuTL4YEu5HdUWlzgyXesFGYjgiY", description="作者头像"),
     *                          @SWG\Property( property="province", type="string", example="null", description="省"),
     *                          @SWG\Property( property="city", type="string", example="null", description="市"),
     *                          @SWG\Property( property="area", type="string", example="null", description="区"),
     *                          @SWG\Property( property="regions", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="regions_id", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_id", type="string", example="2", description="文章类目id"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getArticleFavList(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);
        $memberArticleFavService = new MemberArticleFavService();
        $result = $memberArticleFavService->getArticleFavList($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/collect/article/{article_id}",
     *     summary="添加心愿单商品",
     *     tags={"会员"},
     *     description="添加心愿单商品",
     *     operationId="addArticleFav",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="article_id", in="formData", description="文章id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="fav_id", type="string", example="59", description="收藏id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                  @SWG\Property( property="article_id", type="string", example="58", description="文章id"),
     *                  @SWG\Property( property="created", type="string", example="1611717805", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function addArticleFav($article_id, Request $request)
    {
        $params = $request->input();

        $params['article_id'] = $article_id;

        $rules = [
            'article_id' => ['required', '没有选择收藏的心愿单'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);

        $memberArticleFavService = new MemberArticleFavService();

        $result = $memberArticleFavService->addArticleFav($params);

        return $this->response->array($result);
    }


    /**
     * @SWG\Delete(
     *     path="/wxapp/member/collect/article",
     *     summary="删除收藏心愿单",
     *     tags={"会员"},
     *     description="删除收藏心愿单",
     *     operationId="deleteArticleFav",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="article_id", in="formData", description="文章id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteArticleFav(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'article_id' => 'required',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('MembersBundle/Members.delete_favorite_wishlist_error'), $validator->errors());
        }

        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);
        $memberArticleFavService = new MemberArticleFavService();
        $params = [
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
            'article_id' => $params['article_id'],
        ];
        $result = $memberArticleFavService->removeArticleFav($params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/collect/article/num",
     *     summary="会员心愿单收藏数量",
     *     tags={"会员"},
     *     description="会员心愿单收藏数量",
     *     operationId="getArticleFavNum",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="0", description="总数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getArticleFavNum(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);
        $memberArticleFavService = new MemberArticleFavService();
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id']
        ];
        $result['total_count'] = $memberArticleFavService->count($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/collect/article/info",
     *     summary="会员收藏心愿单详情",
     *     tags={"会员"},
     *     description="会员收藏心愿单详情",
     *     operationId="getArticleFavInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="article_id", type="string", example="1", description="文章id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="title", type="string", example="秃头少女自救指南！黄致列同款韩国TS洗发水有多好用？", description="标题"),
     *                  @SWG\Property( property="summary", type="string", example="#熬夜少女必备#", description="摘要"),
     *                  @SWG\Property( property="content", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="name", type="string", example="writing", description="名称"),
     *                          @SWG\Property( property="base", type="object",
     *                                  @SWG\Property( property="title", type="string", example="", description="标题"),
     *                                  @SWG\Property( property="subtitle", type="string", example="", description="副标题"),
     *                                  @SWG\Property( property="padded", type="string", example="false", description=""),
     *                          ),
     *                          @SWG\Property( property="config", type="object",
     *                                  @SWG\Property( property="align", type="string", example="left", description="配置"),
     *                          ),
     *                          @SWG\Property( property="data", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="content", type="string", example="集美们大家好~这里是玛莉~努力让自己和大家一边变美的Mary~", description="文章详细内容 "),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="sort", type="string", example="3", description="文章排序"),
     *                  @SWG\Property( property="image_url", type="string", example="https://cdn.watsonsestore.com.cn/image/3/2020/05/07/b9e0ec86d4103d8e2cc50aeb105c4f85LqtPdlEcrZnZI1JRrVhI6KzhTrXcSaiX", description="文章封面"),
     *                  @SWG\Property( property="share_image_url", type="string", example="null", description="分享图片"),
     *                  @SWG\Property( property="created", type="string", example="1561953223", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1593771492", description="修改时间"),
     *                  @SWG\Property( property="author", type="string", example="Marionnaud玛莉娜", description="作者"),
     *                  @SWG\Property( property="operator_id", type="string", example="1", description="作者id"),
     *                  @SWG\Property( property="release_status", type="string", example="true", description="文章发布状态"),
     *                  @SWG\Property( property="release_time", type="string", example="1571816150", description="文章发布时间"),
     *                  @SWG\Property( property="article_type", type="string", example="bring", description="文章类型，general:普通文章; bring:带货文章"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="店铺id"),
     *                  @SWG\Property( property="head_portrait", type="string", example="https://cdn.watsonsestore.com.cn/3/2019/12/23/c1edf9d380e74ca95a21a0a7455c4557yoPMQMuTL4YEu5HdUWlzgyXesFGYjgiY", description="作者头像"),
     *                  @SWG\Property( property="province", type="string", example="null", description="省"),
     *                  @SWG\Property( property="city", type="string", example="null", description="市"),
     *                  @SWG\Property( property="area", type="string", example="null", description="区"),
     *                  @SWG\Property( property="regions", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="regions_id", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="category_id", type="string", example="2", description="文章类目id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getArticleFavInfo(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);
        $memberArticleFavService = new MemberArticleFavService();
        $result = $memberArticleFavService->getArticleFavInfo($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/decryptPhone",
     *     summary="获取授权手机号-仅支持微信",
     *     tags={"会员"},
     *     description="手机号授权解密获取手机号",
     *     operationId="getNoAuthDecryptPhoneNumber",
     *     @SWG\Parameter( name="appid", in="query", description="小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="登录时获取的 code", required=true, type="string"),
     *     @SWG\Parameter( name="encryptedData", in="query", description="小程序登录时用户授权手机号的加密数据", required=true, type="string"),
     *     @SWG\Parameter( name="iv", in="query", description="小程序登录时用户授权手机号加密算法的初始向量", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="open_id", type="string", example="0", description="open_id"),
     *                  @SWG\Property( property="union_id", type="string", example="0", description="union_id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getNoAuthDecryptPhoneNumber(Request $request)
    {
        $inputData = $request->input();
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($inputData['appid']);
        $wxParams = [
            'code' => $inputData['code'],
            'appid' => $inputData['appid'],
        ];
        $res = $app->auth->session($inputData['code']); //调用微信获取sessionkey接口，返回session_key,openid,unionid

        if (empty($res['session_key'])) {
            throw new StoreResourceFailedException('用户登录失败！');
        }

        $data = $app->encryptor->decryptData($res['session_key'], $inputData['iv'], $inputData['encryptedData']);

        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/collect/distribution/{distributor_id}",
     *     summary="添加收藏店铺",
     *     tags={"会员"},
     *     description="添加收藏店铺",
     *     operationId="addDistributionFav",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="fav_id", type="string", example="81", description="收藏id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                  @SWG\Property( property="distributor_id", type="string", example="12", description="分销商id"),
     *                  @SWG\Property( property="created", type="string", example="1611729158", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function addDistributionFav($distributor_id, Request $request)
    {
        $params = $request->input();

        $params['distributor_id'] = $distributor_id;

        $rules = [
            'distributor_id' => ['required', '没有选择收藏的店铺'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);

        $memberDistributionFavService = new MemberDistributionFavService();

        $result = $memberDistributionFavService->addDistributionFav($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/member/collect/distribution",
     *     summary="删除收藏店铺",
     *     tags={"会员"},
     *     description="删除收藏店铺",
     *     operationId="deleteArticleFav",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteDistributionFav(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'distributor_id' => 'required',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('删除收藏店铺出错.', $validator->errors());
        }

        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);
        $memberDistributionFavService = new MemberDistributionFavService();
        $params = [
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
            'distributor_id' => $params['distributor_id'],
        ];
        $result = $memberDistributionFavService->removeDistributionFav($params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/collect/distribution",
     *     summary="会员收藏店铺列表",
     *     tags={"会员"},
     *     description="会员收藏店铺列表",
     *     operationId="getDistributionFavList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                          ref="#/definitions/Distribution"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getDistributionFavList(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params = array_merge((array)$params, (array)$authInfo);
        $memberDistributionFavService = new MemberDistributionFavService();
        $result = $memberDistributionFavService->getDistributionFavList($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/collect/distribution/num",
     *     summary="会员收藏店铺数量",
     *     tags={"会员"},
     *     description="会员收藏店铺数量",
     *     operationId="getDistributionFavNum",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getDistributionFavNum(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        if (isset($params['distributor_id']) && $params['distributor_id']) {
            $filter['distributor_id'] = $params['distributor_id'];
        } else {
            $filter['user_id'] = $authInfo['user_id'];
        }
        $memberDistributionFavService = new MemberDistributionFavService();
        $result['total_count'] = $memberDistributionFavService->count($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/collect/distribution/check",
     *     summary="是否收藏店铺",
     *     tags={"会员"},
     *     description="是否收藏店铺",
     *     operationId="checkDistributionFav",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(name="distributor_id", in="query", description="店铺ID", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="is_fav", type="string", example="true", description="是否收藏"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function checkDistributionFav(Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $validator = app('validator')->make($params, [
            'distributor_id' => 'required',
        ]);
        if ($validator->fails() || empty($authInfo['user_id'])) {
            return $this->response->array(['is_fav' => false]);
        }
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['distributor_id'] = $params['distributor_id'];

        $memberDistributionFavService = new MemberDistributionFavService();
        $result = $memberDistributionFavService->getInfo($filter);
        $is_fav = $result ? true : false;
        return $this->response->array(['is_fav' => $is_fav]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/invoicelist",
     *     summary="会员发票列表",
     *     tags={"会员"},
     *     description="会员发票列表",
     *     operationId="getInvoiceList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getInvoiceList(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $orderBy = ['is_def' => 'desc', 'created' => 'desc'];
        $memberInvoicesService = new MembersInvoicesService();

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $result = $memberInvoicesService->lists($filter, $page, $pageSize, $orderBy);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/invoice/{invoices_id}",
     *     summary="发票详情",
     *     tags={"会员"},
     *     description="发票详情",
     *     operationId="getInvoice",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="invoices_id", in="path", description="发票id", required=true, type="string"),
     *     @SWG\Response(response=200, description="成功返回结构"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getInvoice($invoice_id, Request $request)
    {
        $params['invoices_id'] = $invoice_id;
        $validator = app('validator')->make($params, [
            'invoices_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('查询发票详情出错.', $validator->errors());
        }

        $authInfo = $request->get('auth');
        $memberInvoicesService = new MembersInvoicesService();
        $filter = [
            'invoices_id' => $invoice_id,
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id'],
        ];
        $result = $memberInvoicesService->getInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/invoice",
     *     summary="添加发票信息",
     *     tags={"会员"},
     *     description="添加发票信息",
     *     operationId="createInvoice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="invoices_type", in="formData", description="抬头类型", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="名称", required=true, type="string"),
     *     @SWG\Parameter( name="telephone", in="formData", description="手机", required=true, type="string"),
     *     @SWG\Parameter( name="tax_number", in="formData", description="税号", type="string"),
     *     @SWG\Parameter( name="business_address", in="formData", description="详细地址", type="string"),
     *     @SWG\Parameter( name="bank", in="formData", description="银行", type="string"),
     *     @SWG\Parameter( name="bank_account", in="formData", description="银行账号", type="string"),
     *     @SWG\Parameter( name="is_def", in="formData", description="是否默认地址", type="boolean"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="invoices_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="user_id", type="string"),
     *                     @SWG\Property(property="username", type="string"),
     *                     @SWG\Property(property="telephone", type="string"),
     *                     @SWG\Property(property="province", type="string"),
     *                     @SWG\Property(property="city", type="string"),
     *                     @SWG\Property(property="county", type="string"),
     *                     @SWG\Property(property="adrdetail", type="string"),
     *                     @SWG\Property(property="postalCode", type="string"),
     *                     @SWG\Property(property="is_def", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function createInvoice(Request $request)
    {
        $params = $request->input();
        $rules = [
            'invoices_type' => ['required|in:personal,corporate', '请选择抬头类型'],
            'name' => ['required', '请填写发票抬头'],
            'telephone' => ['required_if:invoices_type,corporate', '请填写正确的手机号'],
            'tax_number' => ['required_if:invoices_type,corporate', '请填写税号'],
            'business_address' => ['required_if:invoices_type,corporate', '请填写详细地址'],
            'bank' => ['required_if:invoices_type,corporate', '请填写银行'],
            'bank_account' => ['required_if:invoices_type,corporate', '请填写银行账号'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['is_def'] = $params['is_def'] ?? 0;

        $memberInvoicesService = new MembersInvoicesService();

        $result = $memberInvoicesService->createInvoices($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/member/invoice/{invoice_id}",
     *     summary="修改发票信息",
     *     tags={"会员"},
     *     description="修改发票信息",
     *     operationId="updateInvoice",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="invoices_id", in="path", description="发票ID", required=true, type="string"),
     *     @SWG\Parameter( name="invoices_type", in="formData", description="抬头类型", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="名称", required=true, type="string"),
     *     @SWG\Parameter( name="telephone", in="formData", description="手机", required=true, type="string"),
     *     @SWG\Parameter( name="tax_number", in="formData", description="税号", type="string"),
     *     @SWG\Parameter( name="business_address", in="formData", description="详细地址", type="string"),
     *     @SWG\Parameter( name="bank", in="formData", description="银行", type="string"),
     *     @SWG\Parameter( name="bank_account", in="formData", description="银行账号", type="string"),
     *     @SWG\Parameter( name="is_def", in="formData", description="是否默认地址", type="boolean"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="invoices_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="user_id", type="string"),
     *                     @SWG\Property(property="username", type="string"),
     *                     @SWG\Property(property="telephone", type="string"),
     *                     @SWG\Property(property="province", type="string"),
     *                     @SWG\Property(property="city", type="string"),
     *                     @SWG\Property(property="county", type="string"),
     *                     @SWG\Property(property="adrdetail", type="string"),
     *                     @SWG\Property(property="postalCode", type="string"),
     *                     @SWG\Property(property="is_def", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateInvoice($invoice_id, Request $request)
    {
        $params = $request->input();
        $params['invoices_id'] = $invoice_id;

        $rules = [
            'invoices_id' => ['required|numeric|min:1', '缺少发票信息id'],
            'invoices_type' => ['required|in:personal,corporate', '请选择抬头类型'],
            'name' => ['required', '请填写发票抬头'],
            'telephone' => ['required_if:invoices_type,corporate', '请填写正确的手机号'],
            'tax_number' => ['required_if:invoices_type,corporate', '请填写税号'],
            'business_address' => ['required_if:invoices_type,corporate', '请填写详细地址'],
            'bank' => ['required_if:invoices_type,corporate', '请填写银行'],
            'bank_account' => ['required_if:invoices_type,corporate', '请填写银行账号'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $authInfo = $request->get('auth');

        $filter['invoices_id'] = $invoice_id;
        $filter['user_id'] = $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];
        $memberInvoicesService = new MembersInvoicesService();
        $result = $memberInvoicesService->updateInvoices($filter, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/member/invoice/{invoice_id}",
     *     summary="删除发票",
     *     tags={"会员"},
     *     description="删除发票",
     *     operationId="deleteInvoice",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter(name="invoice_id", in="path", description="发票id", required=true, type="string"),
     *     @SWG\Response(response=200, description="成功返回结构"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteInvoice($invoice_id, Request $request)
    {
        $params['invoices_id'] = $invoice_id;
        $validator = app('validator')->make($params, [
            'invoices_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除发票信息出错.', $validator->errors());
        }

        $authInfo = $request->get('auth');
        $memberInvoicesService = new MembersInvoicesService();
        $params = [
            'invoices_id' => $invoice_id,
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id'],
        ];
        $result = $memberInvoicesService->deleteBy($params);

        return $this->response->array(['status' => $result, 'invoices_id' => $invoice_id]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/statistical",
     *     summary="获取会员优惠券、积分、收藏商品数量",
     *     tags={"会员"},
     *     description="获取会员优惠券、积分、收藏商品数量",
     *     operationId="getMemberStatistical",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="discount_total_count", type="string", example="3", description=""),
     *                  @SWG\Property( property="point_total_count", type="string", example="200", description="积分总数"),
     *                  @SWG\Property( property="fav_total_count", type="string", example="0", description="收藏总数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getMemberStatistical(Request $request)
    {
        $authInfo = $request->get('auth');

        //统计优惠券数量
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['status'] = [1, 10];
        $filter['end_date|gt'] = time();
        $userDiscountService = new UserDiscountService();
        $result['discount_total_count'] = $userDiscountService->getUserDiscountCount($filter);
        //统计积分
        $pointMemberService = new PointMemberService();
        $pointMember = $pointMemberService->getInfo(['user_id' => $authInfo['user_id'], 'company_id' => $authInfo['company_id']]);
        $result['point_total_count'] = isset($pointMember['point']) ? $pointMember['point'] : 0;



        //统计收藏商品数量
        $pageSize = $request->input('pageSize', 100);
        $page = $request->input('page', 1);
        $memberItemsFavService = new MemberItemsFavService();
        $favlist = $memberItemsFavService->lists(['user_id' => $authInfo['user_id'], 'company_id' => $authInfo['company_id']], $page, $pageSize);
        $itemsService = new ItemsService();
        $distributorService = new DistributorService();
        foreach ($favlist['list'] as $key => $value) {
            $itemsInfo = $itemsService->get($value['item_id']);
            if ($itemsInfo) {
                if ($itemsInfo['distributor_id']) {
                    $tmpDistributor = $distributorService->lists(['distributor_id' => $itemsInfo['distributor_id'], 'company_id' => $authInfo['company_id'], 'is_valid' => 'true']);
                    if (!$tmpDistributor['list']) {
                        unset($favlist['list'][$key]);
                        $favlist['total_count']--;
                        continue;
                    }
                }
                $favlist['list'][$key]['price'] = $itemsService->getItemsMemberPrice($value['item_id'], $authInfo['user_id'], $authInfo['company_id']);
            }
        }
        //统计收藏商品数量
        $favcount = $favlist['total_count'];

        //统计收藏软文数量
        $memberArticleFavService = new MemberArticleFavService();
        $articleFilter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id']
        ];
        $articleCount = $memberArticleFavService->count($articleFilter) ?? 0;

        //统计收藏店铺数量

        $storeFilter['company_id'] = $authInfo['company_id'];
        if (isset($params['distributor_id']) && $params['distributor_id']) {
            $storeFilter['distributor_id'] = $params['distributor_id'];
        } else {
            $storeFilter['user_id'] = $authInfo['user_id'];
        }
        $memberDistributionFavService = new MemberDistributionFavService();
        $storeCount = $memberDistributionFavService->count($storeFilter) ?? 0;
        $totalCount = $favcount + $articleCount + $storeCount;
        $result['fav_total_count'] = $totalCount ?? 0;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/memberinfo",
     *     summary="获取会员可编辑信息",
     *     tags={"会员"},
     *     description="获取会员可编辑信息",
     *     operationId="getMemberEditInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="info", type="object",
     *                      @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                      @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                      @SWG\Property( property="username", type="string", example="null", description="名称"),
     *                      @SWG\Property( property="avatar", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/28Qz0boz9fjJYJiapHjxu5nNcBKDZMcNlrpctITqfTawwnsw8Wu9Af4k6DzIXlSv01m3nvxV48Nic9JsroJ9NuGA/132", description="头像"),
     *                      @SWG\Property( property="sex", type="string", example="0", description="性别。0 未知；1 男；2 女"),
     *                      @SWG\Property( property="birthday", type="string", example="null", description="出生日期"),
     *                      @SWG\Property( property="address", type="string", example="null", description="具体地址"),
     *                      @SWG\Property( property="email", type="string", example="null", description="常用邮箱"),
     *                      @SWG\Property( property="industry", type="string", example="null", description="所属行业"),
     *                      @SWG\Property( property="income", type="string", example="null", description="收入"),
     *                      @SWG\Property( property="edu_background", type="string", example="null", description="学历"),
     *                      @SWG\Property( property="habbit", type="array",
     *                          @SWG\Items( type="string", example="undefined", description="爱好"),
     *                      ),
     *                      @SWG\Property( property="have_consume", type="string", example="true", description="是否有消费"),
     *                  ),
     *                  @SWG\Property( property="registerSetting",
     *                      ref="#/definitions/MemberSetting"
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getMemberEditInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $memberInfo = $this->memberService->getMemberInfoData($authInfo['user_id'], $authInfo['company_id']);
        return $this->response->array($memberInfo);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/whitelist/status",
     *     summary="获取会员白名单设置",
     *     tags={"会员"},
     *     description="获取会员白名单设置",
     *     operationId="getWhitelistStatus",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="false", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getWhitelistStatus(Request $request)
    {
        $authInfo = $request->get('auth');
        $settingService = new SelfdeliveryAddressService();
        $config = $settingService->getWhitelistSetting($authInfo['company_id']);
        return $this->response->array(['status' => $config['whitelist_status']]);
    }

    /**
     * @SWG\Post(
     *     path="wxapp/member/subscribe/item/{item_id}",
     *     summary="商品缺货通知订阅",
     *     tags={"会员"},
     *     description="商品缺货通知订阅",
     *     operationId="itemsSubscribe",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                  @SWG\Property( property="item_id", type="string", example="2017", description="商品id"),
     *                  @SWG\Property( property="item_name", type="string", example="兔兔1 有规格名字要长名字要长名字要长名字要长名字要长名字要", description="标题"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function itemsSubscribe($item_id, Request $request)
    {
        $params['item_id'] = $item_id;
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['open_id'] = $authInfo['open_id'];
        $params['wxa_appid'] = $authInfo['wxapp_appid'];
        $params['source'] = 'wechat';
        if (isset($authInfo['alipay_user_id']) && $authInfo['alipay_user_id']) {
            $params['open_id'] = $authInfo['alipay_user_id'];
            $params['source'] = 'alipay';
        }
        $params['distributor_id'] = $request->get('distributor_id', 0);

        $goodsArrivalNotice = new SubscribeService(new GoodsArrivalNoticeService());
        $result = $goodsArrivalNotice->subscribe->create($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="wxapp/member/item/is_subscribe/{item_id}",
     *     summary="获取订阅列表",
     *     tags={"会员"},
     *     description="获取订阅列表",
     *     operationId="itemsSubscribe",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                  @SWG\Property( property="item_id", type="string", example="2017", description="商品id"),
     *                  @SWG\Property( property="item_name", type="string", example="兔兔1 有规格名字要长名字要长名字要长名字要长名字要长名字要", description="标题"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function IsSubscribe($item_id, Request $request)
    {
        $params['item_id'][] = $item_id;
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $source = 'wechat';
        if (isset($authInfo['alipay_user_id']) && $authInfo['alipay_user_id']) {
            $source = 'alipay';
        }

        if (!($authInfo['user_id'] ?? 0)) {
            return $this->response->array([]);
        }
        $params['distributor_id'] = $request->get('distributor_id', 0);
        $goodsArrivalNotice = new SubscribeService(new GoodsArrivalNoticeService());
        $result = $goodsArrivalNotice->subscribe->getList($params, $source, $authInfo['user_id']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/is_new",
     *     summary="判断是否为新用户",
     *     tags={"会员"},
     *     description="判断是否为新用户",
     *     operationId="isNewMember",
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="company_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="会员手机号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="is_new", type="integer", description="是否为新用户 【0 老用户】【1 新用户】"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function isNewMember(Request $request)
    {
        $authData = (array)$request->attributes->get("auth");
        $companyId = (int)($authData["company_id"] ?? 0); // 企业id

        $mobile = $request->input('mobile'); // 手机号

        // 获取用户信息
        if ($companyId > 0 && !empty($mobile)) {
            $userInfo = $this->memberService->getInfoByMobile($companyId, $mobile);
        } else {
            $userInfo = [];
        }

        return $this->response->array([
            "is_new" => $userInfo ? 0 : 1
        ]);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/member/bind",
     *     summary="会员绑定",
     *     tags={"会员"},
     *     description="会员绑定",
     *     operationId="bind",
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="company_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         description="会员手机号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="vcode",
     *         in="query",
     *         description="短信验证码的值",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="check_type",
     *         in="query",
     *         description="login 登录验证码",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="query",
     *         description="会员密码",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="union_id",
     *         in="query",
     *         description="union_id",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="open_id",
     *         in="query",
     *         description="open_id",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="token", type="string", description="如果token为null表示该用户未注册，如果token有值表示用户登录成功"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function bindMember(Request $request)
    {
        $authData = (array)$request->attributes->get("auth");

        $requestData = $request->input();
        $requestData["company_id"] = (int)($authData["company_id"] ?? 0); // 企业id

        if ($messageBag = validation($requestData, [
            "username" => ["required", "regex:/^1[3456789]{1}[0-9]{9}$/"], // 手机号
            "union_id" => "required", // 短信验证码
            "check_type" => "required_without:password", // 短信验证码类型 login
            "vcode" => "required_without:password", // 用户密码
            "password" => ["required_without:check_type,vcode", "alpha_num", "digits_between:6,16"], // 密码
        ], [
            "username.required" => "手机号必填！",
            "username.regex" => "手机号格式有误！",
            "union_id.required" => "参数有误！",
            "check_type.required_without" => "短信验证码必填！",
            "vcode.required_without" => "短信验证码必填！",
            "password.required_without" => "密码必填！",
        ])) {
            throw new ResourceException($messageBag->first());
        }

        $token = $this->memberService->bindMember($requestData);

        return $this->response->array(["token" => $token]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/bindSalesperson",
     *     summary="绑定导购",
     *     tags={"会员"},
     *     description="绑定导购",
     *     operationId="bindSalesperson",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="work_userid",
     *         in="formData",
     *         description="导购员工编号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="status", type="boolean", example="true"),
     *                 ),
     *             ),
     *         ),
     *     ),
     * @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */

    public function bindSalesperson(Request $request)
    {
        $postdata = $request->all('work_userid');
        $rules = [
            'work_userid' => ['required', '导购员工编号不能为空'],
        ];
        $error = validator_params($postdata, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $authInfo = $request->get('auth');
        app('log')->info('memberservice创建会员 绑定导购:3 params===>'.__FUNCTION__.':'.__FILE__.':'.__LINE__.json_encode($authInfo));
        // $queue = (new BindSalseperson($authInfo['company_id'], $authInfo['unionid'], $postdata['work_userid'], 2, $authInfo['mobile']))->onQueue('slow');
        // app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
        // 增加绑定导购关联关系
        $salespersonService = new SalespersonService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'work_userid' => $postdata['work_userid'],
            'salesperson_type' => 'shopping_guide',
            'is_valid' => 'true',
        ];
        $salespersonInfo = $salespersonService->getSalespersonDetail($filter);
        if ( empty($salespersonInfo) ) {
            // throw new ResourceException('导购不存在');
            return $this->response->array(['status' => false,'msg'=>'导购不存在']);
        }
        $salesperson_id = $salespersonInfo['salesperson_id'];
        $workWechatRepositories = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
        //查找用户已绑定的导购员
        $filter = [
            'user_id' => $authInfo['user_id'],
            'is_bind' => 1,
            'company_id' => $authInfo['company_id']
        ];
        $bound = $workWechatRepositories->getInfo($filter);
        if ( $bound ) {
            if ( $bound['user_id'] == $authInfo['user_id'] ) {
                // throw new ResourceException('已与当前导购员绑定');
                return $this->response->array(['status' => false,'msg'=>'已与当前导购员绑定']);
            } else {
                // throw new ResourceException('已绑定导购员，无法绑定');
                return $this->response->array(['status' => false,'msg'=>'已绑定导购员，无法绑定']);
            }
        }
        // 创建绑定关系
        $filter = [
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id'],
            'salesperson_id' => $salesperson_id,
        ];
        $isBound = $workWechatRepositories->getInfo($filter);
        if ($isBound) {
            $status = $workWechatRepositories->updateOneBy($filter, ['is_bind' => 1]); //修改
        } else {
            $data = [
                'user_id' => $authInfo['user_id'],
                'salesperson_id' => $salesperson_id,
                'company_id' => $authInfo['company_id'],
                'unionid' => $authInfo['unionid'],
                'is_friend' => 0,
                'is_bind' => 1,
                'bound_time' => time(),
                'add_friend_time' => 0
            ];
            $status = $workWechatRepositories->create($data);
        }
        // 同步第三方
        $queue = (new BindSalseperson($authInfo['company_id'], $authInfo['unionid'], $postdata['work_userid'], 2, $authInfo['mobile'], $authInfo['user_id']))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);


        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/salesperson/uniquevisito",
     *     summary="记录导购被访问的UV",
     *     tags={"会员"},
     *     description="记录导购被访问的UV",
     *     operationId="salespersonUniqueVisito",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="work_userid",
     *         in="query",
     *         description="导购员工编号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function salespersonUniqueVisito(Request $request)
    {
        $postdata = $request->all('work_userid');
        $rules = [
            'work_userid' => ['required', '导购员工编号不能为空'],
        ];
        $error = validator_params($postdata, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $authInfo = $request->get('auth');
        $memberService = new MemberService();
        $memberService->salespersonUniqueVisito($authInfo['company_id'], $postdata['work_userid'], $authInfo['unionid']);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/member",
     *     summary="注销会员",
     *     tags={"会员"},
     *     description="注销会员",
     *     operationId="memberDelete",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="is_delete",
     *         in="query",
     *         description="是否删除",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *                  @SWG\Property( property="msg", type="string", example="true", description="提示信息"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteMember(Request $request)
    {
        $is_delete = $request->input('is_delete');
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $user_id = $authInfo['user_id'];
        $mobile = $authInfo['mobile'];
        if (empty($company_id) || empty($user_id) || empty($mobile)) {
            throw new ResourceException(['status' => false, 'msg' => trans('MembersBundle/Members.not_logged_in')]);
        }
        $memberService = new MemberService();
        $checkResult = $memberService->checkDeleteMembers($company_id, $user_id);
        if (empty($checkResult)) {
            $member_logout_config = ProtocolService::TYPE_MEMBER_LOGOUT_CONFIG;
            $configData = (new ProtocolService($company_id))->get([$member_logout_config]);
            $msg = $configData[$member_logout_config]['title'] ?? ProtocolService::TYPE_TITLE_DEFAULT[$member_logout_config];

            return $this->response->array(['status' => false,'msg' => $msg]);
        }
        if (!empty($is_delete)) {
            $memberService->deleteMembers($company_id, $user_id, $mobile);
            return $this->response->array(['status' => true,'msg' => '注销成功']);
        }
        $mobile = substr_replace($mobile, '****', 3, 4);
        return $this->response->array(['status' => true,'msg' => $mobile]);
    }
}
