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

namespace MembersBundle\Http\Api\V1\Action;

use Carbon\Carbon;
use CompanysBundle\Services\Shops\ProtocolService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Validation\Rule;
use KaquanBundle\Services\MemberCardService;
use DepositBundle\Services\DepositTrade;
use MembersBundle\Events\UpdateMemberSuccessEvent;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\MemberRegSettingService;
use MembersBundle\Services\WechatUserService;
use MembersBundle\Services\MemberTagsService;
use MembersBundle\Services\MemberOperateLogService;
use KaquanBundle\Services\VipGradeOrderService;
use MembersBundle\Traits\MemberSearchFilter;
use DistributionBundle\Services\DistributorUserService;
use SalespersonBundle\Services\SalespersonService;

use Dingo\Api\Exception\ResourceException;
use PointBundle\Services\PointMemberService;
use ThirdPartyBundle\Services\ShopexCrm\GetMemberListService;
use WorkWechatBundle\Services\WorkWechatRelService;
use CommunityBundle\Services\CommunityChiefService;
use CommunityBundle\Services\CommunityChiefDistributorService;
use MembersBundle\Traits\GetCodeTrait;
use PopularizeBundle\Services\PromoterService;
use MembersBundle\Events\CreateMemberSuccessEvent;
use KaquanBundle\Services\UserDiscountService;
use MembersBundle\Entities\MembersDeleteRecord;

use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;

class Members extends Controller
{
    use MemberSearchFilter;
    use GetCodeTrait;

    public $memberService;

    public function __construct()
    {
        // ID: 53686f704578
        $this->memberService = new MemberService();
        $this->limit = 100;
    }

    /**
     * @SWG\Post(
     *     path="/members/register/setting",
     *     summary="设置会员注册项",
     *     tags={"会员"},
     *     description="设置会员注册项",
     *     operationId="setMemberRegItems",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         description="姓名",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="sex",
     *         in="query",
     *         description="性别",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="birthday",
     *         in="query",
     *         description="出生日期",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         description="家庭住址",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         description="常用邮箱",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="industry",
     *         in="query",
     *         description="从事行业",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="income",
     *         in="query",
     *         description="年收入",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="edu_background",
     *         in="query",
     *         description="学历",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="habbit",
     *         in="query",
     *         description="爱好",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function setMemberRegItems(Request $request)
    {
        $params = $request->input();
        $companyId = (int)app('auth')->user()->get('company_id');

        $regSettinService = new MemberRegSettingService();
        if (isset($params['content']) && $params['content']) {
            $regSettinService->setRegAgreement($companyId, $params['content']);
            // 新数据的更新写入
            $protocolService = new ProtocolService($companyId);
            $data = $protocolService->get([ProtocolService::TYPE_MEMBER_REGISTER]);
            $protocolService->set(ProtocolService::TYPE_MEMBER_REGISTER, [
                "title" => (string)($data[ProtocolService::TYPE_MEMBER_REGISTER]["title"] ?? ""),
                "content" => (string)$params["content"]
            ]);
        } else {
            $regSettinService->setRegItem($companyId, $params);
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/members/register/setting",
     *     summary="获取会员注册项",
     *     tags={"会员"},
     *     description="获取会员注册项",
     *     operationId="getMemberRegItems",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="setting",
     *                          ref="#/definitions/MemberSetting"
     *                  ),
     *                  @SWG\Property( property="registerSettingStatus", type="string", example="true", description="是否开启注册录入"),
     *                  @SWG\Property( property="content_agreement", type="string", example="", description="注册协议"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getMemberRegItems()
    {
        $companyId = app('auth')->user()->get('company_id');
        $regSettinService = new MemberRegSettingService();
        $result = $regSettinService->getRegItem($companyId);
        $result['content_agreement'] = $regSettinService->getRegAgreement($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/members",
     *     summary="获取会员列表",
     *     tags={"会员"},
     *     description="获取会员列表",
     *     operationId="getMemberList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页数",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="显示数量",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="source",
     *         in="query",
     *         description="会员来源",
     *         type="string",
     *     ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_start_begin", description="开始时间" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_start_end", description="结束日期" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="have_consume", description="有无购买记录" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="店铺" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="shop_id", description="门店" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="tag_id", description="标签" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="grade_id", description="等级" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="vip_grade", description="付费会员类型" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="remarks", description="备注" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="username", description="姓名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="user_id", type="string", example="20399", description="用户id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="grade_id", type="string", example="4", description="等级ID"),
     *                          @SWG\Property( property="mobile", type="string", example="18530870713", description="手机号"),
     *                          @SWG\Property( property="user_card_code", type="string", example="A3514D180BA5", description="会员卡号"),
     *                          @SWG\Property( property="authorizer_appid", type="string", example="", description="appid"),
     *                          @SWG\Property( property="wxa_appid", type="string", example="", description="appid"),
     *                          @SWG\Property( property="source_id", type="string", example="0", description="来源id"),
     *                          @SWG\Property( property="monitor_id", type="string", example="0", description=" 监控id"),
     *                          @SWG\Property( property="latest_source_id", type="string", example="0", description="最近来源id"),
     *                          @SWG\Property( property="latest_monitor_id", type="string", example="0", description="最近监控页面id"),
     *                          @SWG\Property( property="created", type="string", example="1611903667", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1611903667", description="修改时间"),
     *                          @SWG\Property( property="created_year", type="string", example="2021", description="创建年份"),
     *                          @SWG\Property( property="created_month", type="string", example="1", description="创建月份"),
     *                          @SWG\Property( property="created_day", type="string", example="29", description="创建日期"),
     *                          @SWG\Property( property="offline_card_code", type="string", example="null", description="线下会员卡号"),
     *                          @SWG\Property( property="inviter_id", type="string", example="0", description="推荐人id"),
     *                          @SWG\Property( property="source_from", type="string", example="default", description="来源类型 default默认"),
     *                          @SWG\Property( property="password", type="string", example="$2y$10$gLAjMjE6a3TP.4UmZbAeZe//E3sjs89JeFnp/wtYjQKPMTvIvXhdm", description="密码"),
     *                          @SWG\Property( property="disabled", type="string", example="0", description="是否禁用。0:可用；1:禁用"),
     *                          @SWG\Property( property="use_point", type="string", example="0", description="是否可以使用积分"),
     *                          @SWG\Property( property="remarks", type="string", example="null", description="备注"),
     *                          @SWG\Property( property="third_data", type="string", example="null", description="第三方数据"),
     *                          @SWG\Property( property="username", type="string", example="www", description="姓名"),
     *                          @SWG\Property( property="sex", type="string", example="0", description="性别。0 未知 1 男 2 女"),
     *                          @SWG\Property( property="birthday", type="string", example="null", description="出生日期"),
     *                          @SWG\Property( property="address", type="string", example="null", description="地址"),
     *                          @SWG\Property( property="email", type="string", example="null", description="常用邮箱"),
     *                          @SWG\Property( property="industry", type="string", example="null", description="从事行业"),
     *                          @SWG\Property( property="income", type="string", example="null", description="收入"),
     *                          @SWG\Property( property="edu_background", type="string", example="null", description="学历"),
     *                          @SWG\Property( property="habbit", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="tagList", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="inviter", type="string", example="-", description=""),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="156", description="总数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getMemberList(Request $request)
    {
        // ID: 53686f704578
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        $authdata = app('auth')->user()->get();

        //验证参数todo
        $postdata = $request->all();
        $postdata['page'] = $page;
        $postdata['pageSize'] = $limit;
        $rules = [
            'page' => ['required|integer|min:1', '分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:100', '每页显示数量最大100'],
            // 'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'remarks' => ['sometimes|string|max:255', '最多输入255字'],
            'username' => ['sometimes|string|max:50', '最多输入50字'],
            'name' => ['sometimes|string|max:50', '最多输入50字'],
            'time_start_begin' => ['sometimes|integer', '请填写正确的开始日期'],
            'time_start_end' => ['sometimes|integer', '请填写正确的结束日期'],
            'have_consume' => ['sometimes|' . Rule::in(['true', 'false']), '有无购买记录参数不正确'],
            'distributor_id' => ['sometimes|integer|min:1', '请确认您选择的店铺是否存在'],
            'shop_id' => ['sometimes|integer|min:1', '请确认您选择的门店是否存在'],
            'tag_id' => ['sometimes|integer|min:1', '请确认您选择的会员标签是否存在'],
            'grade_id' => ['sometimes', '请确认您选择的会员等级是否存在'],
            'vip_grade' => ['sometimes|' . Rule::in(['notvip', 'svip', 'vip', 'vip,svip']), '付费会员类型参数不正确'],
            'promoter_mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '来源推广员请填写正确的手机号'],
            //'inviter_id' =>[],
            //'user_card_code' =>[],
            //'user_id' => [],
        ];
        $error = validator_params($postdata, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $filter = $this->dataFilter($postdata, $authdata);
        if (isset($postdata['inviter_mobile']) && $postdata['inviter_mobile']) {
            $inviterId = $this->memberService->getUserIdByMobile($postdata['inviter_mobile'], $authdata['company_id']);
            $filter['inviter_id'] = $inviterId ?: '-1';
        }
        if (isset($postdata['wechat_nickname']) && $postdata['wechat_nickname']) {
            $filter['wechat_nickname'] = $postdata['wechat_nickname'];
        }

        if (isset($postdata['salesman_mobile']) && $postdata['salesman_mobile']) {
            $salespersonService = new SalespersonService();
            $salesmanInfo = $salespersonService->getInfo(['mobile' => $postdata['salesman_mobile'], 'company_id' => $authdata['company_id']]);
            $filter['user_id'] = -1;
            if ($salesmanInfo) {
                $workWechatRelService = new WorkWechatRelService();
                $workWechatRel = $workWechatRelService->getInfo([['salesperson_id' => $salesmanInfo['salesperson_id'], 'company_id' => $authdata['company_id']]]);
                if ($workWechatRel) {
                    $filter['user_id'] = $workWechatRel['user_id'];
                }
            }
        }
        // 来源推广员手机号
        if (isset($postdata['promoter_mobile']) && $postdata['promoter_mobile']) {
            $pUserId = $this->memberService->getUserIdByMobile($postdata['promoter_mobile'], $authdata['company_id']);
            $promoterUserIds = $this->getPromoterUserIds($authdata['company_id'], $pUserId);
            if (isset($filter['user_id']) && $filter['user_id']) {
                $filter['user_id'] = is_array($filter['user_id']) ? $filter['user_id'] : [$filter['user_id']];
                $filter['user_id'] = array_column($filter['user_id'], $promoterUserIds);
            } else {
                $filter['user_id'] = $promoterUserIds;
            }
        }
        $result['list'] = $this->memberService->getMemberList($filter, $page, $limit);
        $result['total_count'] = $this->memberService->getMemberCount($filter);

        if ($result['list']) {
            $vipGradeOrderService = new VipGradeOrderService();
            $result['list'] = $vipGradeOrderService->userListVipGradeGet($authdata['company_id'], $result['list']);
        }

        $companyId = $authdata['company_id'];

        $inviterList = [];
        $inviterIds = array_column($result['list'], 'inviter_id');
        if ($inviterIds) {
            $inviterList = $this->memberService->getMobileByUserIds($companyId, $inviterIds);
        }
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        if ($result['list']) {
            //获取会员标签
            $userIds = array_column($result['list'], 'user_id');
            $userFilter = [
                'user_id' => $userIds,
                'company_id' => $companyId,
            ];
            $memberTagService = new MemberTagsService();
            $tagList = $memberTagService->getUserRelTagList($userFilter);
            foreach ($tagList as $tag) {
                $newTags[$tag['user_id']][] = $tag;
            }
            $communityChiefService = new CommunityChiefService();
            $chiefs = $communityChiefService->getChiefIDByUserID($userFilter);
            if ($chiefs) {
                $communityChiefDistributorService = new CommunityChiefDistributorService();
                $chiefDistributors = $communityChiefDistributorService->getLists(['chief_id' => array_column($chiefs, 'chief_id')], 'chief_id,distributor_id');
                foreach ($chiefDistributors as $key => $value) {
                    unset($chiefDistributors[$key]);
                    $key = $value['chief_id'].'_'.$value['distributor_id'];
                    $chiefDistributors[$key] = $value;
                }
            }
            // 数云模式
            if (config('common.oem-shuyun')) {
                $promoterService = new PromoterService();
                // 是否可调整上级（非推广员、有上级推广员）
                $isCanChangepid = $promoterService->getMemberIsCanChangepid($companyId, $userIds);
            }
            
            $allMobile = [];
            foreach ($result['list'] as &$value) {
                $value['habbit'] = json_decode($value['habbit'], true);
                $value['tagList'] = $newTags[$value['user_id']] ?? [];
                $value['is_chief'] = isset($chiefs[$value['user_id']]) ? '1' : '0';
                if ($value['is_chief'] && isset($filter['distributor_id'])) {
                    $value['is_chief'] = isset($chiefDistributors[$chiefs[$value['user_id']]['chief_id'].'_'.$filter['distributor_id']]) ? '1' : '0';
                }
                $value['inviter'] = $inviterList[$value['inviter_id']] ?? '-';
                // 数云模式
                if (config('common.oem-shuyun')) {
                    $value['is_can_changepid'] = $isCanChangepid[$value['user_id']] ?? false;
                    // 查询可用的上级推广员信息
                    $parentPromoterLists = $promoterService->getPromoterParentList([
                        'user_id' => $value['user_id'],
                        'company_id' => $companyId,
                        'disabled' => 0,
                    ], 1);
                    if ($parentPromoterLists['total_count'] > 0) {
                        $promoterData = $parentPromoterLists['list'][0];
                        $value['promoter_info'] = [
                            'promoter_id' => $promoterData['promoter_id'],
                            'promoter_name' => $promoterData['promoter_name'],
                            'promoter_mobile' => $promoterData['mobile'],
                            'promoter_identity' => $promoterData['identity_name'],
                            'is_subordinates' => $promoterData['is_subordinates'],
                        ];
                    }
                }
                $allMobile[] = $value['mobile'];
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $value['username'] = data_masking('truename', (string) $value['username']);
                    $value['inviter'] = $value['inviter'] == '-' ? $value['inviter'] : data_masking('mobile', (string) $value['inviter']);
                    $value['sex'] = $value['sex'] == '0' ? '-' : data_masking('sex', (string) $value['sex']);
                }

            }
            if (config('crm.crm_sync')) {
                $GetMemberListService = new GetMemberListService();
                $strMobile = implode(',', $allMobile);
                $crmResult = $GetMemberListService->GetMemberList($strMobile);
                $allTag = [];
                if (!empty($crmResult['result']['items'])) {
                    foreach ($crmResult['result']['items'] as $key => $item) {
                        $crmTags = array_merge($item['dynamic_tags'], $item['static_tags']);
                        foreach ($crmTags as $keyTag => &$crmTag) {
                            $crmTag['tag_id'] = 'crm';
                        }
                        unset($crmTag);
                        $allTag[$item['ext_member_id']] = $crmTags;
                    }
                }
                foreach ($result['list'] as &$val) {
                    if (!empty($allTag[$val['user_id']])) {
                        $val['tagList'] = array_merge($val['tagList'], $allTag[$val['user_id']]);
                    }
                }
            }
            
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member",
     *     summary="获取会员信息",
     *     tags={"会员"},
     *     description="获取会员信息",
     *     operationId="getMemberInfo",
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="user_id", description="会员ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2353", description="ID"),
     *                          @SWG\Property( property="user_id", type="string", example="20399", description="用户id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="journal_type", type="string", example="1", description="积分交易类型，1:入账；2:全额退；3:部分退；4:提现记账 | 积分交易类型，1:注册送积分 2.推荐送分 3.充值返积分 4.推广注册返积分 5.积分换购 6.储值兑换积分 7.订单返积分 8.会员等级返佣 9.取消订处理积分 10.售后处理积分 11.大转盘抽奖送积分 12:管理员手动调整积分"),
     *                          @SWG\Property( property="point_desc", type="string", example="注册赠送积分", description="积分描述"),
     *                          @SWG\Property( property="income", type="string", example="1", description="收入"),
     *                          @SWG\Property( property="outcome", type="string", example="0", description="支出"),
     *                          @SWG\Property( property="order_id", type="string", example="", description="订单编号"),
     *                          @SWG\Property( property="outin_type", type="string", example="in", description=""),
     *                          @SWG\Property( property="point", type="string", example="1", description="积分"),
     *                          @SWG\Property( property="created", type="string", example="1611903668", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1611903668", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getMemberInfo(Request $request)
    {
        $params = $request->all('mobile', 'user_id');
        if (!$params['user_id'] && !$params['mobile']) {
            return $this->response->array(['username' => '无', 'mobile' => '无', 'gradeInfo' => '']);
            // throw new ResourceException(trans('MembersBundle/Members.user_id_or_mobile_required'));
        }
        if ($params['mobile']) {
            $filter['mobile'] = $params['mobile'];
        }
        if ($params['user_id']) {
            $filter['user_id'] = $params['user_id'];
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        //等级信息、总消费额
        $result = $this->memberService->getMemberInfo($filter, true);

        $userDiscountCount = 0;
        if ($result) {
            //获取会员标签
            $memberTagService = new MemberTagsService();
            $tagFilter['user_id'] = $result['user_id'];
            $tagFilter['company_id'] = $companyId;
            $tagList = $memberTagService->getListTags($tagFilter);
            $result['tagList'] = $tagList['list'];

            //会员卡信息
            $memberCardService = new MemberCardService();
            $result['cardInfo'] = $memberCardService->getMemberCard($companyId);

            //等级信息
            $result['gradeInfo'] = $memberCardService->getGradeByGradeId($result['grade_id']);

            //微信信息
            $wechatUserService = new WechatUserService();
            $filter = [
                'user_id' => $result['user_id'],
                'company_id' => $companyId,
            ];
            $result['wechatUserInfo'] = $wechatUserService->getUserInfo($filter);
            $unionid = $result['wechatUserInfo']['unionid'] ?? '';
            if($unionid){
                $requestSalesperson = new MarketingCenterRequest();
                $resultSalesperson = $requestSalesperson->call($companyId, 'basics.member.getBindSalesperson', ['unionid' => $unionid]);
                // [2025-08-01 17:09:24] production.DEBUG: MarketingCenter:result===>{"status":"success","code":"200","message":"success","data":{"salesperson_id":8,"work_userid":"18964058319","member_id":"8","external_member_type":1,"external_userid":"wmZ_c_cQAAZ8RAXTS1EZbpCv0TASdw1A","suite_external_userid":"","unionid":"oFvDQ69HXH8Wcbw19tE9h0RM4MvY","member_status":1,"bind_status":1,"bind_time":1753949254,"bind_cancel_time":1754063999,"friend_status":1,"become_friend_time":1753949070,"employee_number":"18964058319","store_bn":"09876555","store_name":"桂林路店"}}
                $result['salesperson_info'] = $resultSalesperson['data'] ?? [];
            }

            $depositTrade = new DepositTrade();
            $result['deposit'] = $depositTrade->getUserDepositTotal($companyId, $result['user_id']);

            $vipGradeService = new VipGradeOrderService();
            $vipgrade = $vipGradeService->userVipGradeGet($companyId, $result['user_id']);
            $result['vipgrade'] = $vipgrade ? $vipgrade : ['is_vip' => false];

            $pointMemberService = new PointMemberService();
            $pointMember = $pointMemberService->getInfo(['user_id' => $result['user_id'], 'company_id' => $companyId]);

            $result['point'] = isset($pointMember['point']) ? $pointMember['point'] : 0;
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            if ($datapassBlock) {
                $result['mobile'] = data_masking('mobile', (string) $result['mobile']);
                $result['username'] = data_masking('truename', (string) ($result['username'] ?? ''));
                $result['birthday'] = data_masking('birthday', (string) ($result['birthday'] ?? ''));
                $result['address'] = data_masking('detailedaddress', (string) ($result['address'] ?? ''));
                $result['sex'] = (($result['sex'] ?? '') == '0') ? '-' : data_masking('sex', (string) ($result['sex'] ?? ''));
            }

            $filter = [
                'user_id' => $result['user_id'],
                'status' => [1, 10],
                'company_id' => $companyId,
                'end_date|gt' => time(),
            ];
            $userDiscountService = new UserDiscountService();
            $result['coupon_num'] = $userDiscountService->getUserDiscountCount($filter);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Patch(
     *     path="/member",
     *     summary="更新会员信息",
     *     tags={"会员"},
     *     description="更新会员信息",
     *     operationId="updateMemberInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="disabled", description="禁用" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="user_id", description="会员ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="remarks", description="备注" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Member"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateMemberInfo(Request $request)
    {
        $params = $inputdata = $request->all('user_id', 'disabled', 'remarks','name');
        $rules = [
            'user_id' => ['required|min:1', '缺少会员id'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
            'user_id' => $params['user_id'],
        ];
        unset($params['user_id']);

        $result = $this->memberService->updateMemberInfo($params, $filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/member",
     *     summary="更新会员信息",
     *     tags={"会员"},
     *     description="更新会员信息",
     *     operationId="updateMobileById",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="oldMobile",
     *         in="query",
     *         description="旧手机号",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="newMobile",
     *         in="query",
     *         description="最新手机号",
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Member"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateMobileById(Request $request)
    {
        $inputdata = $request->all('user_id', 'oldMobile', 'newMobile');
        if (!$inputdata['newMobile']) {
            throw new ResourceException(trans('MembersBundle/Members.invalid_mobile'));
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $filter['mobile'] = $inputdata['oldMobile'];
        $filter['user_id'] = $inputdata['user_id'];
        $params['mobile'] = trim($inputdata['newMobile']);
        $result = $this->memberService->updateMemberMobile($params, $filter);
        //记录操作日志
        if ($result) {
            if (app('auth')->user()->get('operator_type') == 'staff') {
                $sender = '员工-' . app('auth')->user()->get('username') . '-' . app('auth')->user()->get('mobile');
            } else {
                $sender = app('auth')->user()->get('username');
            }
            $operateLog = new MemberOperateLogService();
            $operateParams = [
                'user_id' => $inputdata['user_id'],
                'company_id' => $companyId,
                'operate_type' => 'mobile',
                'old_data' => $inputdata['oldMobile'],
                'new_data' => $inputdata['newMobile'],
                'operater' => $sender,
            ];
            $logResult = $operateLog->create($operateParams);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/salesman",
     *     summary="设置会员的导购员",
     *     tags={"会员"},
     *     description="设置会员的导购员",
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="店铺ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="salesman_id", description="导购员" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="user_ids", description="会员ID集合" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones")))
     * )
     */
    public function setMemberSalesman(Request $request)
    {
        $distributorUserService = new DistributorUserService();

        $distributor_id = $request->get('distributor_id');
        $input_data = $request->input();
        $user_ids = $input_data['user_ids'];

        if (!is_array($user_ids)) {
            $user_ids = json_decode($user_ids, true);
        }
        $rules = [
            'user_ids.*.user_id' => ['required', '会员id必填'],
            'salesman_id' => ['required', '导购员必填'],
        ];
        $errorMessage = validator_params($input_data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');
        $filter = ['user_ids' => $user_ids, 'company_id' => $companyId, 'distributor_id' => $distributor_id];

        $result = $distributorUserService->updateUserSalesman($filter, ['salesman_id' => $input_data['salesman_id']]);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Put(
     *     path="/member/grade",
     *     summary="更新会员等级",
     *     tags={"会员"},
     *     description="更新会员等级",
     *     operationId="updateGradeById",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="grade_id", description="等级ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="old_grade_id", description="旧的等级ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="remarks", description="备注" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 ref="#/definitions/Member"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateGradeById(Request $request)
    {
        $inputdata = $request->all('user_id', 'grade_id', 'old_grade_id', 'remarks');

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $filter['user_id'] = $inputdata['user_id'];
        $params['grade_id'] = $inputdata['grade_id'];

        $result = $this->memberService->memberUpdate($params, $filter);

        //记录操作日志
        if ($result) {
            $this->memberService->saveMemberOperateLog($inputdata, $companyId);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Patch(
     *     path="/member/grade",
     *     summary="批量更新会员等级",
     *     tags={"会员"},
     *     description="批量更新会员等级",
     *     operationId="updateGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="user_ids",
     *         in="query",
     *         description="用户id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="grade_id", description="等级ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="remarks", description="备注" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Member"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateGrade(Request $request)
    {
        if (!$request->get('user_ids')) {
            throw new ResourceException(trans('MembersBundle/Members.user_not_specified'));
        }
        $companyId = app('auth')->user()->get('company_id');
        $input_data = $request->input();
        $user_ids = $input_data['user_ids'];

        if (!is_array($user_ids)) {
            $user_ids = json_decode($user_ids, true);
        }
        $rules = [
            'user_ids.*.user_id' => ['required', '商品id必填'],
            'grade_id' => ['required', '会员等级必填'],
        ];
        $errorMessage = validator_params($input_data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        foreach ($user_ids as $v) {
            $filter['company_id'] = $companyId;
            $filter['user_id'] = $v['user_id'];
            $params['grade_id'] = $input_data['grade_id'];

            $inputdata = $params;
            $inputdata['remarks'] = trim($input_data['remarks']);
            $info = $this->memberService->getMemberInfo($filter);
            $inputdata['old_grade_id'] = $info['grade_id'];
            $inputdata['user_id'] = $info['user_id'];
            $result = $this->memberService->memberUpdate($params, $filter);

            //记录操作日志
            if ($result) {
                $this->memberService->saveMemberOperateLog($inputdata, $companyId);
            }
        }
        return $request->all();
    }

    /**
     * @SWG\Get(
     *     path="/operate/loglist",
     *     summary="获取会员操作日志",
     *     tags={"会员"},
     *     description="获取会员操作日志",
     *     operationId="gerMemberOperateLogList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="4", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="757", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                          @SWG\Property( property="operate_type", type="string", example="mobile", description="log类型，mobile：修改手机号,grade_id:修改会员等级"),
     *                          @SWG\Property( property="remarks", type="string", example="null", description="备注"),
     *                          @SWG\Property( property="old_data", type="string", example="18321148691", description="修改前历史数据"),
     *                          @SWG\Property( property="new_data", type="string", example="18321148690", description="新修改的数据"),
     *                          @SWG\Property( property="operater", type="string", example="欢迎", description="管理员描述"),
     *                          @SWG\Property( property="created", type="string", example="1611911424", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function gerMemberOperateLogList(Request $request)
    {
        $inputdata = $request->all('user_id');
        $inputdata['company_id'] = app('auth')->user()->get('company_id');
        $operateLog = new MemberOperateLogService();
        $result = $operateLog->lists($inputdata);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/bindusersalespersonrel",
     *     summary="添加或修改会员与导购员的绑定关系",
     *     tags={"会员"},
     *     description="添加或修改会员与导购员的绑定关系",
     *     operationId="bindUserSalespersonRel",
     *     @SWG\Parameter(name="Authorization",in="header",description="JWT验证token",type="string"),
     *     @SWG\Parameter(name="users",in="query",description="用户id: [20264]",required=true,type="string"),
     *     @SWG\Parameter(name="salesperson_id",in="query",description="导购员id",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="success", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function bindUserSalespersonRel(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->input();
        $params['company_id'] = $companyId;
        $rule = [
            'company_id' => ['required', '企业id必填'],
            'users' => ['required', '用户必选'],
            'salesperson_id' => ['required', '导购员必选'],
        ];
        $error = validator_params($params, $rule);
        if ($error) {
            throw new ResourceException($error);
        }

        $params['users'] = json_decode($params['users'], true);
        if ($params == []) {
            throw new ResourceException(trans('MembersBundle/Members.please_select_user'));
        }
        $data = [
            'company_id' => $params['company_id'],
            'users' => $params['users'],
            'salesperson_id' => $params['salesperson_id']
        ];
        if ($request->get('distributor_id') && !$params['distributor_id']) {
            $data['distributor_id'] = $request->get('distributor_id');
        }
        $result = $this->memberService->bindUserSalespersonRel($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/member/update",
     *     summary="修改会员信息",
     *     tags={"会员"},
     *     description="修改会员信息",
     *     operationId="updateMember",
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         description="姓名",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="sex",
     *         in="query",
     *         description="性别",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="birthday",
     *         in="query",
     *         description="生日",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         description="家庭地址",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         description="email",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="industry",
     *         in="query",
     *         description="行业",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="income",
     *         in="query",
     *         description="年收入",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="edu_background",
     *         in="query",
     *         description="学历",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="habbit",
     *         in="query",
     *         description="爱好",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="user_id", type="string", example="20369", description="用户id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="username", type="string", example="钟先生", description="姓名"),
     *                  @SWG\Property( property="avatar", type="string", example="", description="头像"),
     *                  @SWG\Property( property="sex", type="string", example="1", description="性别。0 未知 1 男 2 女"),
     *                  @SWG\Property( property="birthday", type="string", example="null", description="出生日期"),
     *                  @SWG\Property( property="address", type="string", example="null", description="地址"),
     *                  @SWG\Property( property="email", type="string", example="null", description="常用邮箱"),
     *                  @SWG\Property( property="industry", type="string", example="null", description="从事行业"),
     *                  @SWG\Property( property="income", type="string", example="null", description="收入"),
     *                  @SWG\Property( property="edu_background", type="string", example="null", description="学历"),
     *                  @SWG\Property( property="habbit", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="自行更改字段描述"),
     *                  ),
     *                  @SWG\Property( property="created", type="string", example="1609836805", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1611913832", description="修改时间"),
     *                  @SWG\Property( property="have_consume", type="string", example="false", description="是否有消费"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateMember(Request $request)
    {
        app('log')->info('updateMember');
        $companyId = app('auth')->user()->get('company_id');
        $data = $request->all();
        $rule = [
            'user_id' => ['required', '用户ID必传'],
        ];
        $error = validator_params($data, $rule);
        if ($error) {
            throw new ResourceException($error);
        }

        $postdata = [];
        if (isset($data['username']) && $data['username']) {
            $postdata['username'] = $data['username'];
        }
        if (isset($data['sex'])) {
            $postdata['sex'] = $data['sex'];
        }
        if (isset($data['birthday']) && $data['birthday']) {
            $postdata['birthday'] = Carbon::parse($data['birthday'])->rawFormat("Y-m-d");
        }
        if (isset($data['address'])) {
            $postdata['address'] = $data['address'];
        }
        if (isset($data['email'])) {
            $postdata['email'] = $data['email'];
        }
        if (isset($data['industry'])) {
            $postdata['industry'] = $data['industry'];
        }
        if (isset($data['income'])) {
            $postdata['income'] = $data['income'];
        }
        if (isset($data['edu_background'])) {
            $postdata['edu_background'] = $data['edu_background'];
        }
        if (isset($data['habbit'])) {
            $memberRegSettingService = new MemberRegSettingService();
            $genId = $memberRegSettingService->genReidsId($companyId);
            $setting = app('redis')->connection('members')->get($genId);
            $habbit = [];
            if ($setting) {
                $setting = json_decode($setting, true);
                $habbitSetting = $setting['setting']['habbit']['items'];
                foreach ($habbitSetting as $v) {
                    if (in_array($v['name'], $data['habbit'])) {
                        $v['ischecked'] = 'true';
                    } else {
                        $v['ischecked'] = 'false';
                    }
                    $habbit[] = $v;
                }
            }
            $postdata['habbit'] = $habbit;
        }
        app('log')->info('$postdata'.var_export($postdata, 1));
        if (!$postdata) {
            throw new ResourceException(trans('MembersBundle/Members.data_required'));
        }
        $filter = ['user_id' => $data['user_id'], 'company_id' => $companyId];
        $result = $this->memberService->memberInfoUpdate($postdata, $filter);
        event(new UpdateMemberSuccessEvent($result));
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/image/code",
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
        $companyId = app('auth')->user()->get('company_id');

        $type = $request->input('type', 'sign');

        $memberRegSettingService = new MemberRegSettingService();
        list($token, $imgData) = $memberRegSettingService->generateImageVcode($companyId, $type);
        return $this->response->array([
            'imageToken' => $token,
            'imageData' => $imgData,
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/member/sms/code",
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
        $companyId = app('auth')->user()->get('company_id');

        $mobile = $request->input('mobile');
        if (!$mobile && preg_match("/^1\d{10}$/", $mobile)) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_error'));
        }
        $type = $request->input('type', 'sign');

        // 校验手机号是否注册
        $memberInfo = $this->memberService->getMemberInfo(['mobile' => $mobile, 'company_id' => $companyId]);
        if ($memberInfo && $type == 'sign') {
            throw new ResourceException(trans('MembersBundle/Members.mobile_already_registered'));
        }

        $memberRegSettingService = new MemberRegSettingService();
        $token = $request->input('token');
        $yzmcode = $request->input('yzm');
        if (!$memberRegSettingService->checkImageVcode($token, $companyId, $yzmcode, $type)) {
            throw new ResourceException(trans('MembersBundle/Members.image_captcha_error'));
        }
        $memberRegSettingService->generateSmsVcode($mobile, $companyId, $type);

        return $this->response->array(['status' => true]);
    }

    public function createMember(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $postData = $request->all();
        if (empty($postData['mobile'])) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_required'));
        } elseif (!preg_match('/^1[3456789]{1}[0-9]{9}$/', $postData['mobile'])) {
            throw new ResourceException(trans('MembersBundle/Members.invalid_mobile'));
        }

        /*if (!$postData['vcode']) {
            throw new ResourceException(trans('MembersBundle/Members.verification_code_required'));
        }

        if (!(new MemberRegSettingService())->checkSmsVcode($postData['mobile'], $companyId, $postData['vcode'], $postData['check_type'] ?? 'sign')) {
            throw new ResourceException(trans('MembersBundle/Members.sms_code_error'));
        }*/

        $memberInfo = $this->memberService->getInfoByMobile((int)$companyId, (string)$postData['mobile']);
        if ($memberInfo) {
            throw new ResourceException(trans('MembersBundle/Members.mobile_already_exists'));
        }

        $memberCardService = new MemberCardService();
        $defaultGradeInfo = $memberCardService->getDefaultGradeByCompanyId($companyId);
        if (!$defaultGradeInfo) {
            throw new ResourceException(trans('MembersBundle/Members.missing_default_level'));
        }

        //新增-会员信息
        $memberInfo = [
            'company_id' => $companyId,
            'username' => randValue(8),
            'mobile' => $postData['mobile'],
            'grade_id' => $defaultGradeInfo['grade_id'],
            'password' => substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10),
        ];
        $memberInfo['user_card_code'] = $this->getCode();
        $memberInfo['region_mobile'] = $memberInfo['mobile'];
        $memberInfo['mobile_country_code'] = '86';
        $memberInfo['other_params'] = json_encode([]);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->memberService->membersRepository->create($memberInfo);
            $memberInfo['user_id'] = $result['user_id'];
            $this->memberService->membersInfoRepository->create($memberInfo);
            $ifRegisterPromotion = true;
            $member_logout_config = ProtocolService::TYPE_MEMBER_LOGOUT_CONFIG;
            $privacyData = (new ProtocolService($companyId))->get([$member_logout_config]);
            if (empty($privacyData[$member_logout_config]['new_rights'])) {
                $membersDeleteRecordRepository = app('registry')->getManager('default')->getRepository(MembersDeleteRecord::class);
                $membersDeleteRecord = $membersDeleteRecordRepository->getInfo(['company_id' => $companyId,'mobile' => $postData['mobile']]);
                if (!empty($membersDeleteRecord)) {
                    $ifRegisterPromotion = false;
                }
            }

            $promoterService = new PromoterService();
            $promoterService->create($memberInfo);

            //记录新会员和店铺的关系
            $dataParams = [
                'distributor_id' => $postData['distributor_id'] ?? 0,
                'user_id' => $result['user_id'],
                'company_id' => $companyId,
                'salesperson_id' => 0,
                'inviter_id' => 0,
            ];
            $distributorUserService = new DistributorUserService();
            $distributorUserService->createData($dataParams);

            $date = date('Ymd');
            $redisKey = 'Member:' . $companyId . ':' . $date;
            app('redis')->sadd($redisKey, $result['user_id']);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException(trans('MembersBundle/Members.member_add_failed'));
        }

        $eventData = [
            'user_id' => $result['user_id'],
            'company_id' => $companyId,
            'mobile' => $postData['mobile'],
            'openid' => '',
            'wxa_appid' => '',
            'source_id' => 0,
            'monitor_id' => 0,
            'inviter_id' => 0,
            'salesperson_id' => 0,
            'distributor_id' => $postData['distributor_id'] ?? 0,
            'if_register_promotion' => $ifRegisterPromotion,
        ];
        event(new CreateMemberSuccessEvent($eventData));

        //等级信息
        $result['gradeInfo'] = $memberCardService->getGradeByGradeId($result['grade_id']);

        $vipGradeService = new VipGradeOrderService();
        $vipgrade = $vipGradeService->userVipGradeGet($companyId, $result['user_id']);
        $result['vipgrade'] = $vipgrade ? $vipgrade : ['is_vip' => false];

        return $this->response->array($result);
    }
}
