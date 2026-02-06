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
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Entities\Distributor;
use SalespersonBundle\Services\SalespersonService;
use MembersBundle\Entities\MembersAssociations;

use Dingo\Api\Exception\ResourceException;
use PointBundle\Services\PointMemberService;
use ThirdPartyBundle\Services\ShopexCrm\GetMemberListService;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;
use WorkWechatBundle\Services\WorkWechatRelService;
use CommunityBundle\Services\CommunityChiefService;
use CommunityBundle\Services\CommunityChiefDistributorService;
use MembersBundle\Traits\GetCodeTrait;
use PopularizeBundle\Services\PromoterService;
use MembersBundle\Events\CreateMemberSuccessEvent;
use KaquanBundle\Services\UserDiscountService;
use MembersBundle\Entities\MembersDeleteRecord;
use MembersBundle\Services\MemberSalespersonNotifyService;

use EspierBundle\Services\Config\ConfigRequestFieldsService;

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
     *     @SWG\Parameter( in="query", type="string", required=false, name="employee_number", description="绑定导购编号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="store_bn", description="绑定门店编号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="birthday_start", description="生日开始日期，格式：Y-m-d" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="birthday_end", description="生日结束日期，格式：Y-m-d" ),
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
            'tag_id' => ['sometimes', '请确认您选择的会员标签是否存在'],
            'grade_id' => ['sometimes', '请确认您选择的会员等级是否存在'],
            'vip_grade' => ['sometimes|' . Rule::in(['notvip', 'svip', 'vip', 'vip,svip']), '付费会员类型参数不正确'],
            'promoter_mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '来源推广员请填写正确的手机号'],
            'employee_number' => ['sometimes|string', '导购编号'],
            'store_bn' => ['sometimes|string', '门店编号'],
            'birthday_start' => ['sometimes|date_format:Y-m-d', '请填写正确的生日开始日期'],
            'birthday_end' => ['sometimes|date_format:Y-m-d', '请填写正确的生日结束日期'],
            //'inviter_id' =>[],
            //'user_card_code' =>[],
            //'user_id' => [],
        ];
        $error = validator_params($postdata, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $filter = $this->dataFilter($postdata, $authdata);
        $user = app('auth')->user();
        if ($user->get('operator_type') == 'distributor') { //店铺端
            $filter['op_distributor'] = $user->get('distributor_id');
        }
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

        // 绑定导购编号或绑定门店编号查询
        if ((isset($postdata['store_name']) && $postdata['store_name']) ||
            (isset($postdata['employee_number']) && $postdata['employee_number'])) {

            $bindMemberIds = $this->memberService->getBindMemberIdsBySalesperson(
                $authdata['company_id'],
                $postdata['employee_number'] ?? null,
                $postdata['store_name'] ?? null
            );

            if (!empty($bindMemberIds)) {
                // 如果已有 user_id 条件，取交集；否则直接设置
                $existingUserIds = null;
                // 处理 user_id|in 的情况（优先级最高）
                if (isset($filter['user_id|in'])) {
                    $existingUserIds = is_array($filter['user_id|in']) ? $filter['user_id|in'] : [$filter['user_id|in']];
                    unset($filter['user_id|in']);
                } elseif (isset($filter['user_id']) && $filter['user_id']) {
                    $existingUserIds = is_array($filter['user_id']) ? $filter['user_id'] : [$filter['user_id']];
                    unset($filter['user_id']);
                }

                // 处理 user_id|notIn 的情况：需要排除 notIn 中的ID，然后取交集
                if (isset($filter['user_id|notIn'])) {
                    $notInIds = is_array($filter['user_id|notIn']) ? $filter['user_id|notIn'] : [$filter['user_id|notIn']];
                    $bindMemberIds = array_diff($bindMemberIds, $notInIds);
                    // 如果排除后没有剩余ID，设置为空结果
                    if (empty($bindMemberIds)) {
                        unset($filter['user_id|notIn']);
                        $filter['user_id|in'] = [-1];
                    } else {
                        unset($filter['user_id|notIn']);
                        if ($existingUserIds !== null) {
                            $filter['user_id|in'] = array_intersect($existingUserIds, $bindMemberIds);
                            if (empty($filter['user_id|in'])) {
                                $filter['user_id|in'] = [-1];
                            }
                        } else {
                            $filter['user_id|in'] = $bindMemberIds;
                        }
                    }
                } else {
                    if ($existingUserIds !== null) {
                        $filter['user_id|in'] = array_intersect($existingUserIds, $bindMemberIds);
                        // 如果交集为空，设置为不存在的ID，确保查询结果为空
                        if (empty($filter['user_id|in'])) {
                            $filter['user_id|in'] = [-1];
                        }
                    } else {
                        $filter['user_id|in'] = $bindMemberIds;
                    }
                }
            } else {
                // 如果没有找到绑定的会员，设置为不存在的ID，确保查询结果为空
                // 如果已有 user_id 条件，也需要清空并设置为空结果
                unset($filter['user_id'], $filter['user_id|in'], $filter['user_id|notIn']);
                $filter['user_id|in'] = [-1];
            }
        }

        unset($filter['distributor_id']);
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
                    // $value['sex'] = $value['sex'] == '0' ? '-' : data_masking('sex', (string) $value['sex']);
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

            // 为每个成员添加 requestFields
            if ($result['list']) {
                $userIds = array_column($result['list'], 'user_id');
                $membersInfoFilter = [
                    'user_id' => $userIds,
                    'company_id' => $companyId,
                ];
                // 批量获取所有成员的 members_info 数据
                $membersInfoList = $this->memberService->membersInfoRepository->getListNotPagination($membersInfoFilter, '*');
                $membersInfoIndex = [];
                foreach ($membersInfoList as $info) {
                    $membersInfoIndex[$info['user_id']] = $info;
                }

                // 获取验证字段配置
                $requestValidateList = (new ConfigRequestFieldsService())->getListAndHandleSettingFormat($companyId, ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO);

                // 为每个成员生成 requestFields
                foreach ($result['list'] as &$member) {
                    $requestFields = [];
                    $datapassRequestFields = [];

                    if (isset($membersInfoIndex[$member['user_id']])) {
                        $info = $membersInfoIndex[$member['user_id']];
                        $info["other_params"] = (array)jsonDecode($info["other_params"] ?? null);

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
                                unset($checkboxItem);
                            }
                            if ($item['field_type'] == ConfigRequestFieldsService::FIELD_TYPE_MOBILE) {
                                $datapassRequestFields['mobile'][] = $keyName;
                            }
                        }

                        // 转换字段值为描述
                        (new ConfigRequestFieldsService())->transformGetDescByValue($companyId, ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO, $requestFields);
                    }

                    $member["requestFields"] = $requestFields;
                    $member['datapassRequestFields'] = $datapassRequestFields;
                }
            }


            // 批量查询会员的绑定导购和绑定门店
            $this->memberService->batchGetBindSalesperson($companyId, $result['list']);

            // 批量查询注册分销商名称
            $regDistributorIds = array_filter(array_unique(array_column($result['list'], 'reg_distributor')), function($id) {
                return $id > 0;
            });
            $regDistributorNames = [];
            if (!empty($regDistributorIds)) {
                $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
                $distributorFilter = [
                    'distributor_id' => $regDistributorIds,
                    'company_id' => $companyId,
                ];
                $distributorList = $distributorRepository->getLists($distributorFilter, 'distributor_id,name');

                if (!empty($distributorList)) {
                    foreach ($distributorList as $distributor) {
                        $regDistributorNames[$distributor['distributor_id']] = $distributor['name'] ?? '';
                    }
                }
            }

            // 将分销商名称添加到会员列表中
            foreach ($result['list'] as &$member) {
                $regDistributorId = $member['reg_distributor'] ?? 0;
                $member['reg_distributor_name'] = $regDistributorNames[$regDistributorId] ?? '';
            }
            unset($member);

            // 批量查询分配的店铺（op_distributor）对应的门店名称
            $opDistributorIds = array_filter(array_unique(array_column($result['list'], 'op_distributor')), function($id) {
                return $id > 0;
            });
            $opDistributorNames = [];
            if (!empty($opDistributorIds)) {
                $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
                $distributorFilter = [
                    'distributor_id' => $opDistributorIds,
                    'company_id' => $companyId,
                ];
                $distributorList = $distributorRepository->getLists($distributorFilter, 'distributor_id,name');

                if (!empty($distributorList)) {
                    foreach ($distributorList as $distributor) {
                        $opDistributorNames[$distributor['distributor_id']] = $distributor['name'] ?? '';
                    }
                }
            }

            // 将门店名称添加到会员列表中，字段名为 maintain_store
            foreach ($result['list'] as &$member) {
                $opDistributorId = $member['op_distributor'] ?? 0;
                $member['maintain_store'] = $opDistributorNames[$opDistributorId] ?? '';
            }
            unset($member);

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

            // 绑定导购和绑定门店信息（通过 unionid 查询）
            if($unionid){
                $requestSalesperson = new MarketingCenterRequest();
                $resultSalesperson = $requestSalesperson->call($companyId, 'basics.member.getBindSalesperson', ['external_member_id' => $result['user_id']]);
                // [2025-08-01 17:09:24] production.DEBUG: MarketingCenter:result===>{"status":"success","code":"200","message":"success","data":{"salesperson_id":8,"work_userid":"18964058319","member_id":"8","external_member_type":1,"external_userid":"wmZ_c_cQAAZ8RAXTS1EZbpCv0TASdw1A","suite_external_userid":"","unionid":"oFvDQ69HXH8Wcbw19tE9h0RM4MvY","member_status":1,"bind_status":1,"bind_time":1753949254,"bind_cancel_time":1754063999,"friend_status":1,"become_friend_time":1753949070,"employee_number":"18964058319","store_bn":"09876555","store_name":"桂林路店"}}
                $salespersonData = $resultSalesperson['data'] ?? [];
                $result['salesperson_info'] = $salespersonData;

                // 绑定门店信息
                if (!empty($salespersonData)) {
                    $result['store_info'] = [
                        'store_bn' => $salespersonData['store_bn'] ?? '',
                        'store_name' => $salespersonData['store_name'] ?? '',
                    ];
                } else {
                    $result['store_info'] = [
                        'store_bn' => '',
                        'store_name' => '',
                    ];
                }
            } else {
                $result['salesperson_info'] = [];
                $result['store_info'] = [
                    'store_bn' => '',
                    'store_name' => '',
                ];
            }


            // 注册门店信息（根据 reg_distributor 查询）
            if (!empty($result['reg_distributor']) && $result['reg_distributor'] > 0) {
                $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
                $distributorInfo = $distributorRepository->getInfo([
                    'distributor_id' => $result['reg_distributor'],
                    'company_id' => $companyId,
                ]);
                if ($distributorInfo) {
                    $result['reg_distributor'] = $distributorInfo['name'] ?? '';
                } else {
                    $result['reg_distributor'] = '';
                }
            } else {
                $result['reg_distributor'] = '';
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


    /**
     * @SWG\Put(
     *     path="/member/regDistributor",
     *     summary="批量更新会员注册分销商",
     *     tags={"会员"},
     *     description="批量更新会员的注册分销商ID(reg_distributor字段)",
     *     operationId="reChangeRegDistributor",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="会员ID数组，支持数组格式或JSON字符串格式，如：[1,2,3] 或 \"[1,2,3]\"",
     *         required=true,
     *         type="array",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="分销商ID",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", example=true, description="操作状态"),
     *                 @SWG\Property(property="message", type="string", example="更新成功", description="提示信息"),
     *                 @SWG\Property(property="affected_rows", type="integer", example=5, description="受影响的行数"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function reChangeRegDistributor(Request $request){
        $companyId = app('auth')->user()->get('company_id');
        $inputData = $request->input();

        // 参数验证
        $rules = [
            'user_id' => ['required', '会员ID必填'],
            'distributor_id' => ['required|integer', '分销商ID必填'],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $userIds = $inputData['user_id'];
        $distributorId = intval($inputData['distributor_id']);

        // 确保 user_id 是数组
        if (!is_array($userIds)) {
            if (is_string($userIds)) {
                $userIds = json_decode($userIds, true);
            } else {
                $userIds = [$userIds];
            }
        }

        if (empty($userIds)) {
            throw new ResourceException('会员ID不能为空');
        }

        // 过滤掉无效的 user_id
        $userIds = array_filter(array_map('intval', $userIds), function($id) {
            return $id > 0;
        });

        if (empty($userIds)) {
            throw new ResourceException('有效的会员ID不能为空');
        }

        // 查询当前会员的门店信息，过滤掉门店一致的会员
        $membersRepository = app('registry')->getManager('default')->getRepository(\MembersBundle\Entities\Members::class);
        $currentMembers = $membersRepository->lists([
            'company_id' => $companyId,
            'user_id' => $userIds,
        ],["created" => "DESC"],1000,1,false);

        // 如果查询不到会员信息，则报错
        if (empty($currentMembers['list'])) {
            throw new ResourceException('未找到指定的会员信息');
        }

        // 过滤出需要更新的会员（门店不一致的）
        $needUpdateUserIds = [];
        foreach ($currentMembers['list'] as $member) {
            $currentDistributorId = intval($member['op_distributor'] ?? 0);
            // 如果当前门店和提交的门店不一致，则需要更新
            if ($currentDistributorId != $distributorId) {
                $needUpdateUserIds[] = intval($member['user_id']);
            }
        }

        // 如果所有会员的门店都一致，则不做处理
        if (empty($needUpdateUserIds)) {
            app('log')->info('批量更新会员注册分销商：所有会员门店已一致，无需更新', [
                'company_id' => $companyId,
                'user_ids' => $userIds,
                'distributor_id' => $distributorId,
            ]);

            return $this->response->array([
                'status' => true,
                'message' => '所有会员门店已一致，无需更新',
                'affected_rows' => 0,
            ]);
        }

        // 通知导购端批量解绑会员（在更新前先通知，只通知需要更新的会员）
        try {
            $notifyService = new MemberSalespersonNotifyService();
            $notifyResult = $notifyService->notifyUnbindMembers($companyId, $needUpdateUserIds);

            app('log')->info('批量更新会员注册分销商：通知导购端解绑结果', [
                'company_id' => $companyId,
                'user_ids' => $needUpdateUserIds,
                'notify_result' => $notifyResult,
            ]);
        } catch (\Exception $e) {
            // 通知失败不影响主流程，只记录日志
            app('log')->error('批量更新会员注册分销商：通知导购端解绑失败', [
                'company_id' => $companyId,
                'user_ids' => $needUpdateUserIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // 批量更新 reg_distributor 和 op_distributor（只更新需要更新的会员）
        $filter = [
            'company_id' => $companyId,
            'user_id' => $needUpdateUserIds,
        ];
        $updateData = [
            // 'reg_distributor' => $distributorId,
            'op_distributor' => $distributorId,
        ];

        $result = $membersRepository->updateBy($filter, $updateData);

        app('log')->info('批量更新会员注册分销商', [
            'company_id' => $companyId,
            'original_user_ids' => $userIds,
            'need_update_user_ids' => $needUpdateUserIds,
            'skipped_count' => count($userIds) - count($needUpdateUserIds),
            'distributor_id' => $distributorId,
            'affected_rows' => $result,
        ]);

        // 更新门店后，查询这批会员在当前门店下是否有好友关系的导购（只查询需要更新的会员）
        try {
            // 1. 获取门店的 shop_code（store_bn）
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple([
                'company_id' => $companyId,
                'distributor_id' => $distributorId
            ]);

            if (empty($distributorInfo) || empty($distributorInfo['shop_code'])) {
                app('log')->warning('批量更新会员注册分销商：未找到门店信息或门店编号', [
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                ]);
            } else {
                $storeBn = $distributorInfo['shop_code'];

                // 2. 获取会员的 unionid 列表（只查询需要更新的会员）
                $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
                $associations = $membersAssociationsRepository->lists([
                    'user_id' => $needUpdateUserIds,
                    'company_id' => $companyId,
                    'user_type' => 'wechat'
                ], 'user_id,unionid', 1, -1);

                if (!empty($associations)) {
                    $unionids = array_filter(array_column($associations, 'unionid'));

                    if (!empty($unionids)) {
                        // 3. 调用导购接口查询好友关系
                        $marketingCenterRequest = new MarketingCenterRequest();
                        $requestData = [
                            'store_bn' => $storeBn,
                            'unionids' => $unionids,
                        ];

                        app('log')->info('批量更新会员注册分销商：查询门店导购好友关系', [
                            'company_id' => $companyId,
                            'distributor_id' => $distributorId,
                            'store_bn' => $storeBn,
                            'unionids_count' => count($unionids),
                        ]);

                        $friendCheckResult = $marketingCenterRequest->call($companyId, 'members.store.friend.check', $requestData);

                        app('log')->info('批量更新会员注册分销商：查询门店导购好友关系结果', [
                            'company_id' => $companyId,
                            'distributor_id' => $distributorId,
                            'store_bn' => $storeBn,
                            'result' => $friendCheckResult,
                        ]);

                        // 4. 构建 unionid => user_id 的映射（无论是否有返回数据都需要）
                        $unionidToUserIdMap = [];
                        foreach ($associations as $assoc) {
                            if (!empty($assoc['unionid']) && !empty($assoc['user_id'])) {
                                $unionidToUserIdMap[$assoc['unionid']] = $assoc['user_id'];
                            }
                        }

                        // 记录有好友关系的 unionid
                        $hasFriendUnionids = [];

                        // 5. 如果查询到好友关系，更新对应会员信息
                        if (!empty($friendCheckResult) &&
                            !empty($friendCheckResult['data']) &&
                            is_array($friendCheckResult['data'])) {

                            // 收集需要更新的会员信息（有好友关系的）
                            $updateMembers = [];
                            foreach ($friendCheckResult['data'] as $friendData) {
                                $unionid = $friendData['unionid'] ?? '';
                                $workUserid = $friendData['work_userid'] ?? '';

                                if (!empty($unionid) && !empty($workUserid) && isset($unionidToUserIdMap[$unionid])) {
                                    $hasFriendUnionids[] = $unionid; // 记录有好友关系的 unionid
                                    $updateMembers[] = [
                                        'user_id' => $unionidToUserIdMap[$unionid],
                                        'work_userid' => $workUserid,
                                        'unionid' => $unionid,
                                    ];
                                }
                            }

                            // 批量更新有好友关系的会员信息
                            if (!empty($updateMembers)) {
                                $updateUserIds = array_column($updateMembers, 'user_id');

                                // 按 work_userid 分组，因为每个会员可能对应不同的导购
                                foreach ($updateMembers as $memberInfo) {
                                    $updateFilter = [
                                        'company_id' => $companyId,
                                        'user_id' => $memberInfo['user_id'],
                                    ];

                                    $updateData = [
                                        'is_become_friend' => 1,
                                        'has_fp' => 1,
                                        'fp_salesperson' => $memberInfo['work_userid'],
                                    ];

                                    $membersRepository->updateBy($updateFilter, $updateData);
                                }

                                app('log')->info('批量更新会员注册分销商：更新会员好友关系成功', [
                                    'company_id' => $companyId,
                                    'distributor_id' => $distributorId,
                                    'store_bn' => $storeBn,
                                    'updated_count' => count($updateMembers),
                                    'updated_user_ids' => $updateUserIds,
                                ]);
                            } else {
                                app('log')->warning('批量更新会员注册分销商：查询到好友关系但无法匹配到会员', [
                                    'company_id' => $companyId,
                                    'distributor_id' => $distributorId,
                                    'store_bn' => $storeBn,
                                    'friend_data' => $friendCheckResult['data'],
                                ]);
                            }
                        } else {
                            app('log')->info('批量更新会员注册分销商：未查询到好友关系或接口返回异常', [
                                'company_id' => $companyId,
                                'distributor_id' => $distributorId,
                                'store_bn' => $storeBn,
                                'result' => $friendCheckResult,
                            ]);
                        }

                        // 6. 更新没有好友关系的会员（has_fp = 0）
                        // 只有当导购接口成功返回数据时，才计算差集并更新
                        // 计算没有好友关系的 unionid（本次需要更新的 unionid - 导购返回的有好友关系的 unionid）
                        if (!empty($friendCheckResult) ) {

                            // 只有导购接口成功返回时，才计算差集
                            $noFriendUnionids = array_diff($unionids, $hasFriendUnionids);

                            if (!empty($noFriendUnionids)) {
                                $noFriendUserIds = [];
                                foreach ($noFriendUnionids as $unionid) {
                                    if (isset($unionidToUserIdMap[$unionid])) {
                                        $noFriendUserIds[] = $unionidToUserIdMap[$unionid];
                                    }
                                }

                                if (!empty($noFriendUserIds)) {
                                    // 批量更新没有好友关系的会员 has_fp = 0
                                    $updateFilter = [
                                        'company_id' => $companyId,
                                        'user_id' => $noFriendUserIds,
                                    ];

                                    $updateData = [
                                        'has_fp' => 0,
                                    ];

                                    $noFriendResult = $membersRepository->updateBy($updateFilter, $updateData);

                                    app('log')->info('批量更新会员注册分销商：更新无好友关系会员', [
                                        'company_id' => $companyId,
                                        'distributor_id' => $distributorId,
                                        'store_bn' => $storeBn,
                                        'updated_count' => count($noFriendUserIds),
                                        'updated_user_ids' => $noFriendUserIds,
                                        'affected_rows' => $noFriendResult,
                                    ]);
                                }
                            }
                        } else {
                            app('log')->info('批量更新会员注册分销商：导购接口返回异常，不更新无好友关系会员', [
                                'company_id' => $companyId,
                                'distributor_id' => $distributorId,
                                'store_bn' => $storeBn,
                                'result' => $friendCheckResult,
                            ]);
                        }
                    } else {
                        app('log')->warning('批量更新会员注册分销商：未找到会员unionid', [
                            'company_id' => $companyId,
                            'user_ids' => $userIds,
                        ]);
                    }
                } else {
                    app('log')->warning('批量更新会员注册分销商：未找到会员关联信息', [
                        'company_id' => $companyId,
                        'user_ids' => $userIds,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // 查询好友关系失败不影响主流程，只记录日志
            app('log')->error('批量更新会员注册分销商：查询门店导购好友关系失败', [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'user_ids' => $userIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $this->response->array([
            'status' => true,
            'message' => '更新成功',
            'affected_rows' => $result,
        ]);
    }
}
