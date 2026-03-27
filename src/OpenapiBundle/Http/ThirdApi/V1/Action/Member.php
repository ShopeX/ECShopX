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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use OpenapiBundle\Http\Controllers\Controller as Controller;


use MembersBundle\Services\WechatUserService;
use MembersBundle\Services\MemberService;

use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Traits\GetCodeTrait;
use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\VipGradeOrderService;
use MembersBundle\Entities\MembersAssociations;
use OrdersBundle\Traits\GetOrderServiceTrait;
use MembersBundle\Services\MemberBrowseHistoryService;
use MembersBundle\Services\MemberTagsService;
use GoodsBundle\Services\ItemsService;
use OrdersBundle\Services\TradeService;
use ThirdPartyBundle\Services\DmCrm\MemberService as DmMemberService;
use MembersBundle\Services\TagLibraryPushService;
use MembersBundle\Entities\MemberTagGroup;
use MembersBundle\Entities\MemberTagGroupRel;
use MembersBundle\Entities\MemberTags;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Entities\Distributor;
use SalespersonBundle\Services\SalespersonService;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;

class Member extends Controller
{
    use GetCodeTrait;
    use GetOrderServiceTrait;
    /**
     * @SWG\Get(
     *     path="/ecx.member.query",
     *     summary="查询会员信息接口",
     *     tags={"会员"},
     *     description="查询会员信息接口",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.query" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="unionid", description="unionid 和手机号必须二选一" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="is_member", type="string", example="N", description="是否会员"),
     *                  @SWG\Property( property="mobile", type="string", example="", description="手机号"),
     *                  @SWG\Property( property="unionid", type="string", example="", description="unionid"),
     *                  @SWG\Property( property="uid", type="string", example="", description="用户id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function memberInfo(Request $request)
    {
        $params = $request->all();

        $rules = [
            'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'unionid' => ['sometimes|string', '请填写unionid'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        if ((!isset($params['unionid']) || empty($params['unionid'])) && (!isset($params['mobile']) || empty($params['mobile']))) {
            $this->api_response('fail', 'unionid或者手机号必填', null, 'E0001');
        }
        $companyId = $request->get('auth')['company_id'];
        $return = [
            "is_member" => "N", //是否为会员
            "mobile" => "",    //返回会员手机号
            "unionid" => "",//返回会员unionid
            "uid" => "" //返回会员内部ID
        ];
        if (isset($params['mobile']) && $params['mobile']) {
            $filter['company_id'] = $companyId;
            $filter['mobile'] = $params['mobile'];
            $memberService = new MemberService();
            $result = $memberService->getMemberInfo($filter);
            if (!$result) {
                $this->api_response('true', '操作成功', $return, 'E0000');
            }
            $wechatUserService = new WechatUserService();
            $result['unionid'] = $wechatUserService->getUnionidByUserId($result['user_id'], $companyId);
            $return = [
                "is_member" => "Y", //是否为会员
                "mobile" => $result['mobile'],    //返回会员手机号
                "unionid" => $result['unionid'],//返回会员unionid
                "uid" => $result['user_id'] //返回会员内部ID
            ];
            $this->api_response('true', '操作成功', $return, 'E0000');
        } elseif (isset($params['unionid']) && $params['unionid']) {
            $wechatUserService = new WechatUserService();
            $wechatInfo = $wechatUserService->getAssociationsByUnionid($params['unionid'], $companyId);
            if (!$wechatInfo) {
                $this->api_response('true', '操作成功', $return, 'E0000');
            }
            $filter['company_id'] = $companyId;
            $filter['user_id'] = $wechatInfo['user_id'];
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo($filter);

            $result = array_merge($wechatInfo, $memberInfo);

            $return = [
                "is_member" => "Y", //是否为会员
                "mobile" => $result['mobile'],    //返回会员手机号
                "unionid" => $result['unionid'],//返回会员unionid
                "uid" => $result['user_id'] //返回会员内部ID
            ];
            $this->api_response('true', '操作成功', $return, 'E0000');
        }

        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    // 会员信息创建
    /**
     * @SWG\Post(
     *     path="/ecx.member.create",
     *     summary="会员信息创建",
     *     tags={"会员"},
     *     description="会员信息创建",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.create" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="mobile", type="string", example="", description="手机号"),
     *                  @SWG\Property( property="uid", type="string", example="", description="用户id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function memberCreate(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];

        $params = $request->all();

        $rules = [
            'mobile' => ['required', '手机号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage, null, 'E0001');
        }
        $mobile = $params['mobile'];

        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);

        $member = $membersRepository->get(['company_id' => $companyId, 'mobile' => $mobile]);
        if ($member) {
            $this->api_response('fail', '当前手机号已经是会员', null, 'E0001');
        }

        //新增-会员信息
        $memberInfo = [
            'company_id' => $companyId,
            'mobile' => trim($mobile),
            'sex' => $this->getSex(''),
            'created' => time(),
            'password' => substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10),
        ];

        $memberInfo['user_card_code'] = $this->getCode();

        $memberCardService = new MemberCardService();
        $defaultGradeInfo = $memberCardService->getDefaultGradeByCompanyId($companyId);
        $memberInfo['grade_id'] = $defaultGradeInfo['grade_id'];

        $result = [];
        $return = [
            'mobile' => '',
            'uid' => ''
        ];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $membersRepository->create($memberInfo);
            $memberInfo['user_id'] = $result['user_id'];

            $membersInfoRepository->create($memberInfo);
            $return = [
                'mobile' => $mobile,
                'uid' => $result['user_id'],
            ];
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            $this->api_response('fail', '保存数据错误', null, 'E0001');
        }
        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    private function getSex($str)
    {
        if ($str == '男') {
            return 1;
        } elseif ($str == '女') {
            return 2;
        } else {
            return 0;
        }
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.basicInfo",
     *     summary="会员基础信息",
     *     tags={"会员"},
     *     description="会员基础信息",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.basicInfo" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="unionid", description="unionid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function basicInfo(Request $request)
    {
        $params = $request->all();
        $rules = [
            'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'unionid' => ['sometimes|string', '请填写unionid'],
            'external_member_id' => ['sometimes|string', '会员id'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        if ((!isset($params['unionid']) || empty($params['unionid'])) && (!isset($params['mobile']) || empty($params['mobile'])) && (!isset($params['external_member_id']) || empty($params['external_member_id']))) {
            $this->api_response('fail', 'unionid或者手机号或会员id必填', null, 'E0001');
        }
        $companyId = $request->get('auth')['company_id'];
        $filter = ['company_id' => $companyId];
        $return = [];
        $memberService = new MemberService();
        if (($params['mobile'] ?? null) || ($params['external_member_id'] ?? null)) {
            empty(($params['mobile'] ?? null)) ?: $filter['mobile'] = $params['mobile'];
            empty(($params['external_member_id'] ?? null)) ?: $filter['user_id'] = $params['external_member_id'];
            $memberInfo = $memberService->getMemberInfo($filter);
        } else {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $exist = $membersAssociationsRepository->get(['unionid' => $params['unionid'], 'company_id' => $companyId, 'user_type' => 'wechat']);
            if (!$exist) {
                $this->api_response('true', '操作成功', $return, 'E0000');
            }
            $filter['user_id'] = $exist['user_id'];
            $memberInfo = $memberService->getMemberInfo($filter);
        }
        if ($memberInfo) {
            $return['user_id'] = $memberInfo['user_id'];
            $return['mobile'] = $memberInfo['mobile'];
            $return['username'] = $memberInfo['username'];
            $return['birthday'] = $memberInfo['birthday'];
            $return['create_time'] = $memberInfo['created'] ?? '';
            $memberCardService = new MemberCardService();
            $return['gradeInfo'] = $memberCardService->getGradeByGradeId($memberInfo['grade_id']);
            $vipGradeService = new VipGradeOrderService();
            $return['vipgrade'] = $vipGradeService->userVipGradeGet($companyId, $memberInfo['user_id']);
        }
        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.frequentItems",
     *     summary="会员常购清单",
     *     tags={"会员"},
     *     description="会员常购清单",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.frequentItems" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="integer", required=true, name="timeRange", description="时间段 0:一年内 1:半年内 2:三个月内"),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="unionid", description="unionid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function frequentItems(Request $request)
    {
        //timeRange: 0:一年内 1:半年内 2:三个月内
        $params = $request->all();
        $rules = [
            'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'unionid' => ['sometimes|string', '请填写unionid'],
            'timeRange' => ['string|required', '请填写时间段'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        if ((!isset($params['unionid']) || empty($params['unionid'])) && (!isset($params['mobile']) || empty($params['mobile'])) && !($params['external_member_id'] ?? '') ) {
            $this->api_response('fail', 'unionid或者手机号必填', null, 'E0001');
        }
        $companyId = $request->get('auth')['company_id'];
        $memberService = new MemberService();
        if (isset($params['mobile']) && $params['mobile']) {
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
        } elseif ( ($params['external_member_id'] ?? '') ) {// 用id查询常购清单
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'user_id' => $params['external_member_id']]);
        } else {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $memberInfo = $membersAssociationsRepository->get(['unionid' => $params['unionid'], 'company_id' => $companyId, 'user_type' => 'wechat']);
        }
        if (!$memberInfo) {
            $this->api_response('fail', '参数无效', null, 'E0001');
        }

        $orderService = $this->getOrderService('normal');
        $frequentItems = $orderService->getFrequentItemListByTime($companyId, $memberInfo['user_id'], $params['timeRange']);
        $return = ['count' => 0, 'list' => []];
        if ($frequentItems) {
            foreach ($frequentItems as $item) {
                $return['list'][] = [
                    'item_name' => $item['item_name'],
                    'price' => bcdiv($item['price'], 100, 2),
                    'buy_num' => $item['buy_num'],
                    'pic' => isset($item['pics'][0]) ? $item['pics'][0] : '',
                    'sales_num' => $item['sales_num']
                ];
            }
        }
        $return['count'] = count($return['list']);
        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.browserHistory",
     *     summary="会员浏览足迹",
     *     tags={"会员"},
     *     description="会员浏览足迹",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.browserHistory" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="unionid", description="unionid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function browseHistory(Request $request)
    {
        $params = $request->all();
        $rules = [
            'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'unionid' => ['sometimes|string', '请填写unionid'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        if ((!isset($params['unionid']) || empty($params['unionid'])) && (!isset($params['mobile']) || empty($params['mobile']))) {
            $this->api_response('fail', 'unionid或者手机号必填', null, 'E0001');
        }
        $companyId = $request->get('auth')['company_id'];
        $memberService = new MemberService();
        if (isset($params['mobile']) && $params['mobile']) {
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
        } else {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $memberInfo = $membersAssociationsRepository->get(['unionid' => $params['unionid'], 'company_id' => $companyId, 'user_type' => 'wechat']);
        }
        if (!$memberInfo) {
            $this->api_response('fail', '参数无效', null, 'E0001');
        }
        $memberBrowseHistoryService = new MemberBrowseHistoryService();
        $page = 1;
        $pageSize = 10;
        $orderBy = ['updated' => 'DESC'];
        if ($params['page'] ?? 0) {
            $page = $params['page'];
            $pageSize = $params['page_size'];
        }
        $memberBrowseHistoryTemp = $memberBrowseHistoryService->lists(['user_id' => $memberInfo['user_id'], 'company_id' => $companyId], $page, $pageSize, $orderBy);
        $return = ['count' => 0, 'list' => []];
        $historyList = array_column($memberBrowseHistoryTemp['list'], null, 'item_id');
        if ($historyList) {
            $itemService = new ItemsService();
            $itemIds = array_keys($historyList);
            $itemListTemp = $itemService->getItemsList(['item_id|in' => $itemIds]);
            $itemList = $itemListTemp['list'] ?? [];
            foreach ($itemList as $item) {
                $return['list'][] = [
                    'item_id' => $item['item_id'],
                    'item_name' => $item['item_name'],
                    'price' => bcdiv($item['price'], 100, 2),
                    'updated' => $historyList[$item['item_id']]['updated'],
                    'pic' => isset($item['pics'][0]) ? $item['pics'][0] : ''
                ];
            }
            array_multisort(array_column($return['list'], 'updated'), SORT_DESC, $return['list']); //重新按update排序
        }
        $return['count'] = count($return['list']);
        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    public function memberInfoList(Request $request)
    {
        $params = $request->all();
        $rules = [
            'unionid' => ['sometimes|string', '请填写unionid'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        if ((!isset($params['unionid']))) {
            $this->api_response('fail', 'unionid必填', null, 'E0001');
        }
        $companyId = $request->get('auth')['company_id'];
        $filter = [];
        $return = [];
        $page = 1;
        $pageSize = 10;
        if ($params['page'] ?? 0) {
            $page = $params['page'];
            $pageSize = $params['page_size'];
        }
        $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $exist = $membersAssociationsRepository->lists(['unionid' => explode(',', $params['unionid']), 'company_id' => $companyId, 'user_type' => 'wechat'], 'user_id,unionid', $page, $pageSize);
        if (!$exist) {
            $this->api_response('true', '操作成功', $return, 'E0000');
        }
        $exist = array_column($exist, null, 'user_id');
        $filter['user_id'] = array_keys($exist);

        $return = ['count' => 0, 'list' => []];
        $memberService = new MemberService();
        $memberInfoList = $memberService->getMemberInfoList($filter);
        $memberBrowseHistoryService = new MemberBrowseHistoryService();
        $itemService = new ItemsService();
        $orderService = $this->getOrderService('normal');
        foreach ($memberInfoList['list'] as $key => &$value) {
            $total_amount = $orderService->sum(['user_id' => $value['user_id'], 'company_id' => $companyId], 'total_fee');
            $browseHistory = $memberBrowseHistoryService->lists(['user_id' => $value['user_id'], 'company_id' => $companyId], 1, 99999, ['updated' => 'DESC']); //浏览商品历史
            if ($browseHistory['list']) {
                $latestItem = $itemService->getItem(['item_id' => $browseHistory['list'][0]['item_id']]);
                $value['browse_count'] = $browseHistory['total_count'];
                $value['browse_time'] = date('Y-m-d H:i:s', $browseHistory['list'][0]['updated']);
                $value['browse_item_name'] = $latestItem['item_name'] ?? null;
                $value['browse_item_id'] = $browseHistory['list'][0]['item_id'];
            }
            $value['total_amount'] = $total_amount;//消费总额
            $value['unionid'] = $exist[$value['user_id']]['unionid'];
        }

        $total_fee = $orderService->sum($filter, 'total_fee');
        $return['list'] = $memberInfoList['list'];
        $return['count'] = $memberInfoList['total_count'];
        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    public function getMemberOrderLists(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $orderFilter = $this->getFilter($companyId, $params);
        $orderFilter['order_type'] = 'normal';
        // $orderFilter['order_status'] = 'DONE';
        $orderFilter['order_status|notin'] = ['NOTPAY','CANCEL'];
        empty($params['order_class'] ?? null) ?: $orderFilter['order_class'] = $params['order_class'];
        $orderService = $this->getOrderService($orderFilter['order_type']);

        app('log')->info('[getMemberOrderLists] 获取订单列表', [
            'orderFilter' => $orderFilter
        ]);
        $result = $orderService->getOrderItemLists($orderFilter, $page, $pageSize);
        $total_amount = $orderService->getOrderTotalAmount($orderFilter);
        $result['pager']['total_amount'] = $total_amount;
        $result['total_count'] = $result['pager']['count'];
        $result['total_amount'] = $total_amount[$orderFilter['user_id']] ?? 0;
        if ($result['total_count'] > 0 && $result['total_amount']) {
            $result['avg_amount'] = bcdiv($result['total_amount'], $result['total_count'], 0);
        } else {
            $result['avg_amount'] = 0;
        }
        // 获取订单支付时间
        if (empty($result['list'] ?? '')) {
            $this->api_response('true', '操作成功', $result, 'E0000');
        }
        $order_ids = array_column($result['list'], 'order_id');
        // 查询会员浏览总数
        $filter = [
            'company_id' => $companyId,
            'order_id|in' => '"'.join('","', $order_ids).'"'
        ];
        $tradeService = new TradeService();
        $order_trades = $tradeService->getOrderTradeInfo($filter);
        foreach ($result['list'] as &$order) {
            $membe_order = [
                'order_id' => $order['order_id'],
                'order_status' => $order['order_status'],
                'order_status_msg' => $order['order_status_msg'],
                'order_status_des' => $order['order_status_des'],
                'pay_status' => $order['pay_status'],
                'total_fee' => $order['total_fee'],
                'user_id' => $order['user_id']
            ];
            // if ($order_trades[$order['order_id']] ?? '') {
            //     $membe_order['pay_time'] = $order_trades[$order['order_id']]['pay_time'];
            // } else {
            //     $membe_order['pay_time'] = '';
            // }
            $membe_order['pay_time'] = date('Y-m-d H:i:s', $order['create_time']);
            $buy_num = 0;
            $gift = 'normal';
            foreach ($order['items'] as &$item) {
                if ($item['item_spec_desc'] ?? '') {
                    $item_spec = explode(':', $item['item_spec_desc']);
                    $item['item_spec'] = $item_spec[1];
                } else {
                    $item['item_spec'] = '';
                }
                $order_good = [
                    'id' => $item['id'],
                    'order_id' => $order['order_id'],
                    'user_id' => $order['user_id'],
                    'item_id' => $item['item_id'],
                    'item_bn' => $item['item_bn'],
                    'item_name' => $item['item_name'],
                    'pic' => $item['pic'],
                    'num' => $item['num'],
                    'price' => $item['price'],
                    'item_fee' => $item['item_fee'],
                    'item_spec' => $item['item_spec'],
                    'order_item_type' => $item['order_item_type']
                ];
                if ($item['order_item_type'] == 'gift') {
                    $gift = 'gift';
                }
                $buy_num += $item['num'];
                $item = $order_good;
            }
            $membe_order['buy_num'] = $buy_num;
            $membe_order['gift'] = $gift;
            $membe_order['items'] = $order['items'];
            $order = $membe_order;
        }
        $this->api_response('true', '操作成功', $result, 'E0000');
    }

    public function geMembertBrowseList(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $orderBy = ['updated' => 'DESC'];
        $filter = $this->getFilter($companyId, $params);
        $memberBrowseService = new MemberBrowseHistoryService();
        $result = $memberBrowseService->geMembertBrowseList($filter, $page, $pageSize, $orderBy);
        if ($result['list']) {
            foreach ($result['list'] as &$list) {
                $list['create_time'] = date('Y-m-d H:i:s', $list['created']);
                if ($list['itemData'] ?? '') {
                    $good = [
                        'item_id' => $list['itemData']['item_id'],
                        'item_bn' => $list['itemData']['item_bn'],
                        'item_name' => $list['itemData']['item_name'],
                        'pics' => $list['itemData']['pics'],
                        'price' => $list['itemData']['price'],
                    ];
                    $list['itemData'] = $good;
                }
            }
        }
        $this->api_response('true', '操作成功', $result, 'E0000');
    }

    private function getFilter($companyId, $params)
    {
        app('log')->info('[getFilter] 获取会员信息过滤条件', [
            'params' => $params
        ]);
        if(isset($params['external_member_id']) && $params['external_member_id']){
            $filter = [
                'company_id' => $companyId,
                'user_id' => $params['external_member_id'],
            ];
            app('log')->info('[getFilter] 获取会员信息过滤条件', [
                'filter' => $filter
            ]);
            return $filter;
        }

        $rules = [
            'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'unionid' => ['sometimes|string', '请填写unionid'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        if ((!isset($params['unionid']) || empty($params['unionid'])) && (!isset($params['mobile']) || empty($params['mobile']))) {
            $this->api_response('fail', 'unionid或者手机号必填', null, 'E0001');
        }
        $memberService = new MemberService();
        if (isset($params['mobile']) && $params['mobile']) {
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
        } else {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $memberInfo = $membersAssociationsRepository->get(['unionid' => $params['unionid'], 'company_id' => $companyId, 'user_type' => 'wechat']);
        }
        if (!$memberInfo) {
            $this->api_response('fail', '会员信息获取失败', null, 'E0001');
        }
        $filter = [
            'company_id' => $companyId,
            'user_id' => $memberInfo['user_id'],
        ];
        return $filter;
    }

    public function unbindMemberAndSalesperson(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();
        app('log')->info('解绑会员和导购::params=' . json_encode($params).':companyId='.$companyId);
        $dmMemberService = new DmMemberService($companyId);
        if ($dmMemberService->isOpen) {
            app('log')->info('解绑会员和导购::dmMemberService->isOpen=true::同步达摩CRM会员信息更新，服务导购和服务门店信息设置为空::params=' . json_encode($params).':companyId='.$companyId);
            $dmMemberService->updateMemberInfoByMobile([
                'mobile' => $params['mobile'],
                'mainClerkCode' => '',
                'mainStoreCode' => '',
            ]);
        }
        $this->api_response('true', '操作成功', null, 'E0000');
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.tagLibrary",
     *     summary="获取标签库（标签和标签组）",
     *     tags={"会员"},
     *     description="获取标签库，包括所有标签组及其下的标签列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagLibrary" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="true", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="group_id", type="integer", example=1, description="标签组ID"),
     *                  @SWG\Property( property="group_name", type="string", example="促销标签", description="标签组名称"),
     *                  @SWG\Property( property="description", type="string", example="促销相关的标签", description="标签组描述"),
     *                  @SWG\Property( property="created", type="string", example="2021-06-28 15:28:14", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="2021-06-28 15:28:14", description="更新时间"),
     *                  @SWG\Property( property="taglist", type="array", description="标签列表",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="tag_id", type="integer", example=1, description="标签ID"),
     *                          @SWG\Property( property="tag_name", type="string", example="VIP会员", description="标签名称"),
     *                          @SWG\Property( property="description", type="string", example="VIP会员标签", description="标签描述"),
     *                          @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                          @SWG\Property( property="tag_icon", type="string", example="", description="标签图标"),
     *                          @SWG\Property( property="tag_status", type="string", example="online", description="标签状态"),
     *                          @SWG\Property( property="source", type="string", example="self", description="标签来源"),
     *                          @SWG\Property( property="self_tag_count", type="integer", example=10, description="标签下会员数量"),
     *                          @SWG\Property( property="created", type="string", example="2021-06-29 10:35:13", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="2021-06-29 10:35:13", description="更新时间"),
     *                      ),
     *                  ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function tagLibrary(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $em = app('registry')->getManager('default');

        // 获取标签组列表（过滤掉企业微信标签组）
        $qb = $em->createQueryBuilder();
        $qb->select('g.group_id', 'g.group_name', 'g.description', 'g.created', 'g.updated')
            ->from(MemberTagGroup::class, 'g')
            ->where('g.company_id = :company_id')
            ->setParameter('company_id', $companyId);

        // 过滤掉企业微信标签组（wechat_group_id 不为空的）
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('g.wechat_group_id'),
            $qb->expr()->eq('g.wechat_group_id', $qb->expr()->literal(''))
        ));

        $groupList = $qb->getQuery()->getResult();

        // 获取标签组与标签的关系
        $tagGroupRelRepository = app('registry')->getManager('default')->getRepository(MemberTagGroupRel::class);
        $groupRelList = $tagGroupRelRepository->getLists(['company_id' => $companyId], 'group_id,tag_id');

        // 构建 group_id => tag_ids 的映射
        $groupTagsMap = [];
        foreach ($groupRelList as $rel) {
            $groupId = $rel['group_id'];
            if (!isset($groupTagsMap[$groupId])) {
                $groupTagsMap[$groupId] = [];
            }
            $groupTagsMap[$groupId][] = $rel['tag_id'];
        }

        // 获取所有标签（过滤掉企业微信标签）
        $qb2 = $em->createQueryBuilder();
        $qb2->select('t')
            ->from(MemberTags::class, 't')
            ->where('t.company_id = :company_id')
            ->setParameter('company_id', $companyId)
            ->orderBy('t.created', 'DESC');

        // 过滤掉企业微信标签（wechat_tag_id 不为空的）
        $qb2->andWhere($qb2->expr()->orX(
            $qb2->expr()->isNull('t.wechat_tag_id'),
            $qb2->expr()->eq('t.wechat_tag_id', $qb2->expr()->literal(''))
        ));

        $tagListEntities = $qb2->getQuery()->getResult();

        // 转换为数组格式
        $tagList = ['list' => []];
        foreach ($tagListEntities as $entity) {
            $tagList['list'][] = [
                'tag_id' => $entity->getTagId(),
                'tag_name' => $entity->getTagName(),
                'description' => $entity->getDescription(),
                'tag_color' => $entity->getTagColor(),
                'font_color' => $entity->getFontColor(),
                'tag_icon' => $entity->getTagIcon(),
                'tag_status' => $entity->getTagStatus(),
                'source' => $entity->getSource(),
                'created' => $entity->getCreated(),
                'updated' => $entity->getUpdated(),
            ];
        }

        // 构建 tag_id => tag_data 的映射
        $tagsMap = [];
        foreach ($tagList['list'] as $tag) {
            $tagsMap[$tag['tag_id']] = $tag;
        }

        // 获取标签会员数量统计
        $tagIds = array_column($tagList['list'], 'tag_id');
        $countList = [];
        if ($tagIds) {
            $memberTagsService = new MemberTagsService();
            $countFilter = [
                'company_id' => $companyId,
                'tag_id' => $tagIds,
            ];
            $countList = $memberTagsService->entityRepository->getCountList($countFilter);
            $countList = array_column($countList, 'num', 'tag_id');
        }

        app('log')->info('[tagLibrary] 已过滤企业微信标签和标签组', [
            'company_id' => $companyId,
            'group_count' => count($groupList),
            'tag_count' => count($tagList['list']),
        ]);

        // 组装返回数据
        $return = [];
        foreach ($groupList as $group) {
            $groupId = $group['group_id'];
            $taglist = [];

            // 获取该标签组下的所有标签
            if (isset($groupTagsMap[$groupId])) {
                foreach ($groupTagsMap[$groupId] as $tagId) {
                    if (isset($tagsMap[$tagId])) {
                        $tag = $tagsMap[$tagId];
                        $taglist[] = [
                            'tag_id' => $tag['tag_id'],
                            'tag_name' => $tag['tag_name'],
                            'description' => $tag['description'] ?? '',
                            'tag_color' => $tag['tag_color'] ?? '#ff1939',
                            'font_color' => $tag['font_color'] ?? '#ffffff',
                            'tag_icon' => $tag['tag_icon'] ?? '',
                            'tag_status' => $tag['tag_status'] ?? 'online',
                            'source' => $tag['source'] ?? 'self',
                            'self_tag_count' => $countList[$tag['tag_id']] ?? 0,
                            'created' => isset($tag['created']) ? date('Y-m-d H:i:s', $tag['created']) : '',
                            'updated' => isset($tag['updated']) ? date('Y-m-d H:i:s', $tag['updated']) : '',
                        ];
                    }
                }
            }

            $return[] = [
                'group_id' => $groupId,
                'group_name' => $group['group_name'],
                'description' => $group['description'] ?? '',
                'created' => isset($group['created']) ? date('Y-m-d H:i:s', $group['created']) : '',
                'updated' => isset($group['updated']) ? date('Y-m-d H:i:s', $group['updated']) : '',
                'taglist' => $taglist,
            ];
        }

        // 处理未分组的标签
        $groupedTagIds = [];
        foreach ($groupTagsMap as $tagIds) {
            $groupedTagIds = array_merge($groupedTagIds, $tagIds);
        }
        $groupedTagIds = array_unique($groupedTagIds);

        $ungroupedTags = [];
        foreach ($tagsMap as $tagId => $tag) {
            if (!in_array($tagId, $groupedTagIds)) {
                $ungroupedTags[] = [
                    'tag_id' => $tag['tag_id'],
                    'tag_name' => $tag['tag_name'],
                    'description' => $tag['description'] ?? '',
                    'tag_color' => $tag['tag_color'] ?? '#ff1939',
                    'font_color' => $tag['font_color'] ?? '#ffffff',
                    'tag_icon' => $tag['tag_icon'] ?? '',
                    'tag_status' => $tag['tag_status'] ?? 'online',
                    'source' => $tag['source'] ?? 'self',
                    'self_tag_count' => $countList[$tag['tag_id']] ?? 0,
                    'created' => isset($tag['created']) ? date('Y-m-d H:i:s', $tag['created']) : '',
                    'updated' => isset($tag['updated']) ? date('Y-m-d H:i:s', $tag['updated']) : '',
                ];
            }
        }

        // 如果有未分组的标签，添加到返回数据中
        if (!empty($ungroupedTags)) {
            $return[] = [
                'group_id' => 0,
                'group_name' => '未分组',
                'description' => '未分配到任何标签组的标签',
                'created' => '',
                'updated' => '',
                'taglist' => $ungroupedTags,
            ];
        }

        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.tagLibrary.push",
     *     summary="推送标签库到云店",
     *     tags={"会员"},
     *     description="从企业微信等第三方系统推送标签库数据到云店，支持标签组和标签的创建/更新",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagLibrary.push" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="body", name="tag_library", required=true, @SWG\Schema(type="array",
     *         description="标签库数据数组",
     *         @SWG\Items(type="object",
     *             @SWG\Property(property="group_id", type="string", description="标签组ID"),
     *             @SWG\Property(property="group_name", type="string", description="标签组名称"),
     *             @SWG\Property(property="wechat_group_id", type="string", description="企微标签组ID（用于去重）"),
     *             @SWG\Property(property="ori_source", type="string", description="来源标识（wechat）"),
     *             @SWG\Property(property="description", type="string", description="标签组说明"),
     *             @SWG\Property(property="created", type="string", description="创建时间"),
     *             @SWG\Property(property="updated", type="string", description="更新时间"),
     *             @SWG\Property(property="taglist", type="array", description="标签列表",
     *                 @SWG\Items(type="object",
     *                     @SWG\Property(property="tag_id", type="string", description="标签ID"),
     *                     @SWG\Property(property="tag_name", type="string", description="标签名称"),
     *                     @SWG\Property(property="wechat_tag_id", type="string", description="企微标签ID（用于去重）"),
     *                     @SWG\Property(property="ori_source", type="string", description="来源标识（wechat）"),
     *                     @SWG\Property(property="description", type="string", description="标签说明"),
     *                     @SWG\Property(property="tag_color", type="string", description="标签颜色"),
     *                     @SWG\Property(property="font_color", type="string", description="字体颜色"),
     *                     @SWG\Property(property="tag_icon", type="string", description="标签图标"),
     *                     @SWG\Property(property="tag_status", type="string", description="标签状态"),
     *                     @SWG\Property(property="source", type="string", description="来源（wechat）"),
     *                 ),
     *             ),
     *         ),
     *     )),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="success_count", type="integer", description="成功处理的标签组数量"),
     *              @SWG\Property( property="fail_count", type="integer", description="失败的标签组数量"),
     *              @SWG\Property( property="created_groups", type="integer", description="新建的标签组数量"),
     *              @SWG\Property( property="updated_groups", type="integer", description="更新的标签组数量"),
     *              @SWG\Property( property="created_tags", type="integer", description="新建的标签数量"),
     *              @SWG\Property( property="updated_tags", type="integer", description="更新的标签数量"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function tagLibraryPush(Request $request)
    {
        $params = $request->all();
        $companyId = $request->get('auth')['company_id'];

        // 验证必填参数
        if (empty($params['tag_library']) || !is_array($params['tag_library'])) {
            $this->api_response('fail', '标签库数据不能为空', [], 'E1001');
            return;
        }

        try {
            // 调用 Service 处理业务逻辑
            $tagLibraryPushService = new TagLibraryPushService();
            $stats = $tagLibraryPushService->pushTagLibrary($companyId, $params['tag_library']);

            $this->api_response('true', '操作成功', $stats, 'E0000');

        } catch (\Exception $e) {
            $this->api_response('fail', '标签库推送失败：' . $e->getMessage(), [], 'E9999');
        }
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.tag.relation.push",
     *     summary="导购平台推送会员标签关系到云店",
     *     tags={"会员"},
     *     description="导购平台推送会员标签关系到云店，支持两种模式：1. 正常模式：先删除会员所有标签，再重新打标签；2. 清空模式：仅删除会员所有标签",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tag.relation.push" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="body", name="action", required=false, @SWG\Schema(type="string", description="操作类型：clear=清空会员所有标签（此时relations可为空）") ),
     *     @SWG\Parameter( in="body", name="user_id", required=false, @SWG\Schema(type="string", description="会员ID（清空模式必填）") ),
     *     @SWG\Parameter( in="body", name="mobile", required=false, @SWG\Schema(type="string", description="会员手机号（清空模式备用查找）") ),
     *     @SWG\Parameter( in="body", name="relations", required=false, @SWG\Schema(type="array",
     *         description="标签关系数据数组（正常模式必填，清空模式可为空）",
     *         @SWG\Items(type="object",
     *             @SWG\Property(property="user_id", type="string", description="会员ID"),
     *             @SWG\Property(property="mobile", type="string", description="会员手机号"),
     *             @SWG\Property(property="tag_id", type="string", description="标签ID（导购平台的）"),
     *             @SWG\Property(property="tag_name", type="string", description="标签名称"),
     *             @SWG\Property(property="tag_type", type="string", description="标签类型：self/wechat/staff"),
     *             @SWG\Property(property="wechat_tag_id", type="string", description="企微标签ID（可选）"),
     *         )
     *     )),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="success_count", type="integer", description="成功处理的会员数量"),
     *              @SWG\Property( property="fail_count", type="integer", description="失败的会员数量"),
     *              @SWG\Property( property="processed_members", type="integer", description="处理的会员总数"),
     *              @SWG\Property( property="processed_tags", type="integer", description="处理的标签总数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function tagRelationPush(Request $request)
    {
        $params = $request->all();
        $companyId = $request->get('auth')['company_id'];

        // 特殊处理：清空会员标签
        if ((empty($params['relations']) || !is_array($params['relations'])) &&
            isset($params['action']) && $params['action'] === 'clear') {

            return $this->handleClearMode($request, $params, $companyId);
        }

        // 验证必填参数（正常模式）
        if (empty($params['relations']) || !is_array($params['relations'])) {
            app('log')->warning('[tagRelationPush] 标签关系数据不能为空', ['company_id' => $companyId, 'params' => $params]);
            $this->api_response('fail', '标签关系数据不能为空', [], 'E1001');
        }

        return $this->handleNormalMode($request, $params, $companyId);
    }

    /**
     * 处理清空模式
     */
    private function handleClearMode(Request $request, array $params, int $companyId)
    {
        app('log')->info('[tagRelationPush] 清空会员标签模式', [
            'company_id' => $companyId,
            'user_id' => $params['user_id'] ?? null,
        ]);

        // 验证 user_id
        if (empty($params['user_id'])) {
            app('log')->warning('[tagRelationPush] 清空模式需要提供 user_id', ['company_id' => $companyId, 'params' => $params]);
            $this->api_response('fail', '清空模式需要提供 user_id', [], 'E1001');
        }
        $stats = [
            'success_count' => 0,
            'fail_count' => 0,
            'processed_members' => 0,
            'processed_tags' => 0,
        ];

        try {
            $memberService = new MemberService();
            $memberTagsService = new MemberTagsService();

            // 查找会员
            $filter = ['company_id' => $companyId, 'user_id' => $params['user_id']];
            $member = $memberService->getMemberInfo($filter);

            // 如果没找到，尝试用手机号查找
            if (empty($member) && !empty($params['mobile'])) {
                $filter = ['company_id' => $companyId, 'mobile' => $params['mobile']];
                $member = $memberService->getMemberInfo($filter);
            }

            if (empty($member)) {
                app('log')->warning('[tagRelationPush] 未找到会员', [
                    'company_id' => $companyId,
                    'user_id' => $params['user_id'],
                    'mobile' => $params['mobile'] ?? null,
                ]);
                $this->api_response('fail', '未找到会员', [], 'E1001');
            }

            $actualUserId = $member['user_id'];

            // 删除该会员的所有标签
            $memberTagsService->deleteRelTagsByUserId($actualUserId, $companyId);

            app('log')->info('[tagRelationPush] 清空会员标签成功', [
                'company_id' => $companyId,
                'user_id' => $actualUserId,
            ]);

            $stats = [
                'success_count' => 1,
                'fail_count' => 0,
                'processed_members' => 1,
                'processed_tags' => 0,
            ];
        } catch (\Exception $e) {
            app('log')->error('[tagRelationPush] 清空会员标签失败', [
                'company_id' => $companyId,
                'user_id' => $params['user_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            $this->api_response('fail', '清空会员标签失败：' . $e->getMessage(), [], 'E9999');
        }
        $this->api_response('true', '操作成功', $stats, 'E0000');
    }

    /**
     * 处理正常模式
     */
    private function handleNormalMode(Request $request, array $params, int $companyId)
    {
        $memberService = new MemberService();
        $memberTagsService = new MemberTagsService();
        $em = app('registry')->getManager('default');

        $stats = [
            'success_count' => 0,
            'fail_count' => 0,
            'processed_members' => 0,
            'processed_tags' => 0,
        ];

        try {
            app('log')->info('[tagRelationPush] 开始处理（正常模式）', [
                'company_id' => $companyId,
                'count' => count($params['relations']),
            ]);

            // 按会员分组处理
            $memberRelations = [];
            foreach ($params['relations'] as $relation) {
                $userId = $relation['user_id'] ?? '';
                if (!$userId) {
                    continue;
                }

                if (!isset($memberRelations[$userId])) {
                    $memberRelations[$userId] = [
                        'mobile' => $relation['mobile'] ?? '',
                        'tags' => [],
                    ];
                }

                $memberRelations[$userId]['tags'][] = $relation;
            }

            $stats['processed_members'] = count($memberRelations);

            // 处理每个会员的标签
            foreach ($memberRelations as $userId => $data) {
                try {
                    // 1. 查找会员
                    $filter = ['company_id' => $companyId];

                    // 优先用 user_id 查找
                    if ($userId) {
                        $filter['user_id'] = $userId;
                        $member = $memberService->getMemberInfo($filter);
                    }

                    // 如果没找到，用手机号查找
                    if (empty($member) && !empty($data['mobile'])) {
                        $filter = ['company_id' => $companyId, 'mobile' => $data['mobile']];
                        $member = $memberService->getMemberInfo($filter);
                    }

                    if (empty($member)) {
                        app('log')->warning('[tagRelationPush] 未找到会员', [
                            'company_id' => $companyId,
                            'user_id' => $userId,
                            'mobile' => $data['mobile'],
                        ]);
                        $stats['fail_count']++;
                        continue;
                    }

                    $actualUserId = $member['user_id'];

                    // 2. 先删除该会员的所有标签
                    $deleteFilter = [
                        'company_id' => $companyId,
                        'user_id' => $actualUserId,
                    ];

                    $conn = $em->getConnection();
                    $conn->beginTransaction();

                    try {
                        // 删除所有标签关系
                        $memberTagsService->deleteRelTagsByUserId($actualUserId, $companyId);

                        // 3. 查找并打上新标签
                        $tagIds = [];
                        foreach ($data['tags'] as $tagRelation) {
                            $wechatTagId = $tagRelation['wechat_tag_id'] ?? null;
                            $tagId = $tagRelation['tag_id'] ?? null;

                            // 查找云店的标签
                            $tagFilter = ['company_id' => $companyId];

                            // 优先根据 wechat_tag_id 查找
                            if (!empty($wechatTagId)) {
                                $tagFilter['wechat_tag_id'] = $wechatTagId;
                            } else if (!empty($tagId)) {
                                // 否则根据 tag_id 查找
                                $tagFilter['tag_id'] = $tagId;
                            } else {
                                continue;
                            }

                            $qb = $em->createQueryBuilder();
                            $qb->select('t.tag_id')
                                ->from(MemberTags::class, 't')
                                ->where('t.company_id = :company_id')
                                ->setParameter('company_id', $companyId);

                            if (!empty($wechatTagId)) {
                                $qb->andWhere('t.wechat_tag_id = :wechat_tag_id')
                                    ->setParameter('wechat_tag_id', $wechatTagId);
                            } else {
                                $qb->andWhere('t.tag_id = :tag_id')
                                    ->setParameter('tag_id', $tagId);
                            }

                            $tag = $qb->getQuery()->getOneOrNullResult();

                            if ($tag) {
                                $tagIds[] = $tag['tag_id'];
                                $stats['processed_tags']++;
                            } else {
                                app('log')->warning('[tagRelationPush] 未找到标签', [
                                    'company_id' => $companyId,
                                    'wechat_tag_id' => $wechatTagId,
                                    'tag_id' => $tagId,
                                ]);
                            }
                        }

                        // 4. 给会员打上标签（跳过推送到导购平台，避免循环同步）
                        if (!empty($tagIds)) {
                            $memberTagsService->createRelTags([$actualUserId], $tagIds, $companyId, true, true);
                        }

                        $conn->commit();
                        $stats['success_count']++;

                        app('log')->info('[tagRelationPush] 会员标签处理成功', [
                            'company_id' => $companyId,
                            'user_id' => $actualUserId,
                            'tag_count' => count($tagIds),
                        ]);

                    } catch (\Exception $e) {
                        $conn->rollback();
                        throw $e;
                    }

                } catch (\Exception $e) {
                    $stats['fail_count']++;
                    app('log')->error('[tagRelationPush] 会员标签处理失败', [
                        'company_id' => $companyId,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            app('log')->info('[tagRelationPush] 处理完成', [
                'company_id' => $companyId,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            app('log')->error('[tagRelationPush] 处理失败', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->api_response('fail', '标签关系推送失败：' . $e->getMessage(), [], 'E9999');
        }

        $this->api_response('true', '操作成功', $stats, 'E0000');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.assignMemberToSalesperson",
     *     summary="分配客户回调通知",
     *     tags={"会员"},
     *     description="导购端分配会员给导购后，通知云店系统更新分配关系",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.assignMemberToSalesperson" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="employee_number", description="导购员工编号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="unionid", description="会员unionid" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="0", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="分配成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="employee_number", type="string", example="SP001", description="导购员工编号"),
     *              @SWG\Property( property="unionid", type="string", example="unionid_123456", description="会员unionid"),
     *              @SWG\Property( property="assign_time", type="string", example="2025-12-25 15:30:00", description="分配时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function assignMemberToSalesperson(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();

        // 参数验证
        $rules = [
            'employee_number' => ['required|string', '请填写导购员工编号'],
            'unionid' => ['required|string', '请填写会员unionid'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E4003');
        }

        app('log')->info('分配客户回调通知::params=' . json_encode($params).':companyId='.$companyId);

        try {

            // 2. 根据 unionid 查找会员
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $association = $membersAssociationsRepository->get([
                'unionid' => $params['unionid'],
                'company_id' => $companyId,
                'user_type' => 'wechat'
            ]);

            if (!$association) {
                app('log')->warning('分配客户回调通知::未找到会员 unionid='.$params['unionid'].':companyId='.$companyId);
                $this->api_response('fail', '会员不存在', null, 'E4002');
            }

            $userId = $association['user_id'];

            // 3. 更新会员的分配导购信息（支持幂等性）
            $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
            $member = $membersRepository->find($userId);

            if (!$member) {
                app('log')->warning('分配客户回调通知::未找到会员记录 user_id='.$userId);
                $this->api_response('fail', '会员不存在', null, 'E4002');
            }

            // 幂等性检查：如果已经是分配的同一个导购，直接返回成功
            if ($member->getFpSalesperson() == $params['employee_number'] && $member->getHasFp() == 1) {
                app('log')->info('分配客户回调通知::幂等性检查通过，已分配相同导购 user_id='.$userId.',employee_number='.$params['employee_number']);
                $assignTime = date('Y-m-d H:i:s');
                $this->api_response('true', '分配成功', [
                    'employee_number' => $params['employee_number'],
                    'unionid' => $params['unionid'],
                    'assign_time' => $assignTime,
                ], '0');
            }

            // 更新分配关系
            $member->setFpSalesperson($params['employee_number']);
            $member->setHasFp(1);

            $em = app('registry')->getManager('default');
            $em->persist($member);
            $em->flush();

            app('log')->info('分配客户回调通知::更新成功 user_id='.$userId.',employee_number='.$params['employee_number']);

            // 5. 同步达摩CRM（如果开启）
            $dmMemberService = new DmMemberService($companyId);

            // 6. 返回成功响应
            $assignTime = date('Y-m-d H:i:s');
        } catch (\Exception $e) {
            app('log')->error('分配客户回调通知::异常::'.$e->getMessage().'::params='.json_encode($params).'::trace='.$e->getTraceAsString());
            $this->api_response('fail', '系统错误：'.$e->getMessage(), null, 'E5001');
        }
        $this->api_response('true', '分配成功', [
            'employee_number' => $params['employee_number'],
            'unionid' => $params['unionid'],
            'assign_time' => $assignTime,
        ], '0');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.notifyBecomeFriend",
     *     summary="导购通知加好友",
     *     tags={"会员"},
     *     description="导购端通知云店，已分配的客户已经加导购为好友",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.notifyBecomeFriend" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="unionid", description="会员unionid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="salesperson_code", description="导购编号（导购的employee_number）" ),
     *     @SWG\Parameter( in="query", type="integer", required=true, name="is_become_friend", description="是否已加为好友，固定值：1" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="0", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="success", description="提示信息"),
     *          @SWG\Property( property="data", type="object", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function notifyBecomeFriend(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();

        // 参数验证
        $rules = [
            'unionid' => ['required|string', '请填写会员unionid'],
            'salesperson_code' => ['required|string', '请填写导购编号'],
            'is_become_friend' => ['required|integer|in:1', 'is_become_friend必须为1'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }

        app('log')->info('导购通知加好友::params=' . json_encode($params) . ':companyId=' . $companyId);

        try {
            // 调用 Service 处理核心业务逻辑
            $memberService = new MemberService();
            $result = $memberService->notifyBecomeFriend(
                $companyId,
                $params['unionid'],
                $params['salesperson_code']
            );

            // 返回成功响应
            $this->api_response('success', 'success', [], '0');

        } catch (\Exception $e) {
            app('log')->error('导购通知加好友::异常::' . $e->getMessage() . '::params=' . json_encode($params) . '::trace=' . $e->getTraceAsString());

            // 根据异常消息返回相应的错误码
            $errorCode = 'E5001';
            $errorMessage = '系统错误：' . $e->getMessage();

            if (strpos($e->getMessage(), '会员不存在') !== false) {
                $errorCode = 'E4002';
                $errorMessage = $e->getMessage();
            } elseif (strpos($e->getMessage(), '未分配给该导购') !== false) {
                $errorCode = 'E4004';
                $errorMessage = $e->getMessage();
            }

            $this->api_response('fail', $errorMessage, null, $errorCode);
        }
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.listFp",
     *     summary="会员列表查询（增强版）",
     *     tags={"会员"},
     *     description="会员列表查询，支持分页、生日范围、标签、分配状态、导购编号等筛选",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.listFp" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="integer", required=false, name="page", description="页码，默认1" ),
     *     @SWG\Parameter( in="query", type="integer", required=false, name="page_size", description="每页数量，默认10" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="birthday_start", description="生日开始日期，格式：YYYY-MM-DD" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="birthday_end", description="生日结束日期，格式：YYYY-MM-DD" ),
     *     @SWG\Parameter( in="query", type="array", required=false, name="tag_id", description="标签ID，支持多个，格式：tag_id[]=1&tag_id[]=2", @SWG\Items(type="integer") ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="type", description="分配状态：noassign=未分配，assign=已分配" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="salesperson_code", description="导购编号（work_userid）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="keyword", description="关键词（用于按手机号模糊查询）" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="count", type="integer", example=100, description="总数量"),
     *              @SWG\Property( property="list", type="array", description="会员列表",
     *                  @SWG\Items(type="object")
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function memberList(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();

        // 参数验证
        $rules = [
            'scope' => ['sometimes|in:fp,customer', 'scope参数错误，应为fp或customer'],
            'page' => ['sometimes|integer|min:1', '页码必须为正整数'],
            'page_size' => ['sometimes|integer', '每页数量必须在1-500之间'],
            'birthday_start' => ['sometimes|date_format:Y-m-d', '生日开始日期格式错误，应为YYYY-MM-DD'],
            'birthday_end' => ['sometimes|date_format:Y-m-d', '生日结束日期格式错误，应为YYYY-MM-DD'],
            'tag_id' => ['sometimes|array', '标签ID必须为数组'],
            'tag_id.*' => ['sometimes|integer', '标签ID数组中的每个元素必须为整数'],
            'type' => ['sometimes|in:noassign,assign', '分配状态类型错误，应为noassign或assign'],
            'salesperson_code' => ['sometimes|string', '导购编号必须为字符串'],
            'store_bn' => ['sometimes|string', '门店编号必须为字符串'],
            'point_start' => ['sometimes|integer|min:0', '积分开始值必须为非负整数'],
            'point_end' => ['sometimes|integer|min:0', '积分结束值必须为非负整数'],
            'grade_id' => ['sometimes|integer|min:1', '等级ID必须为正整数'],
            'buy_start' => ['sometimes|integer|min:0', '购买金额开始值必须为非负整数'],
            'buy_end' => ['sometimes|integer|min:0', '购买金额结束值必须为非负整数'],
            'keyword' => ['sometimes|string', '关键词（用于按手机号查询）'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }

        // 如果 scope 为 customer，走特殊逻辑
        $scope = $params['scope'] ?? 'fp';
        if ($scope === 'customer') {
            return $this->getCustomerUnionids($request, $companyId, $params);
        }

        // 分页参数
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $pageSize = isset($params['page_size']) ? (int)$params['page_size'] : 10;

        // 构建过滤条件
        $filter = [
            'company_id' => $companyId,
        ];

        // 生日范围查询
        if (!empty($params['birthday_start'])) {
            $filter['birthday|gte'] = $params['birthday_start'];
        }
        if (!empty($params['birthday_end'])) {
            $filter['birthday|lte'] = $params['birthday_end'];
        }

        // 标签查询（支持数组）
        if (!empty($params['tag_id'])) {
            // 如果是单个值，转换为数组
            if (!is_array($params['tag_id'])) {
                $params['tag_id'] = [(int)$params['tag_id']];
            } else {
                // 确保数组中的值都是整数
                $params['tag_id'] = array_map('intval', $params['tag_id']);
            }
            $filter['tag_id'] = $params['tag_id'];
        }

        // 分配状态查询
        if (!empty($params['type'])) {
            if ($params['type'] == 'noassign') {
                $filter['has_fp'] = 0;
            } elseif ($params['type'] == 'assign') {
                $filter['has_fp'] = 1;
                $filter['is_become_friend'] = 0;
            }
        }

        // 导购编号查询
        if (!empty($params['salesperson_code'])) {
            $filter['fp_salesperson'] = $params['salesperson_code'];
        }

        // 门店编号查询（根据store_bn查询op_distributor）
        if (!empty($params['store_bn'])) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple([
                'company_id' => $companyId,
                'shop_code' => $params['store_bn']
            ]);

            if ($distributorInfo && !empty($distributorInfo['distributor_id'])) {
                $filter['op_distributor'] = $distributorInfo['distributor_id'];
            } else {
                // 如果门店不存在，返回空结果
                $this->api_response('true', '操作成功', ['count' => 0, 'list' => []], 'E0000');
            }
        }

        // 积分范围查询
        if (!empty($params['point_start'])) {
            $filter['point|gte'] = (int)$params['point_start'];
        }
        if (!empty($params['point_end'])) {
            $filter['point|lte'] = (int)$params['point_end'];
        }

        // 等级查询
        if (!empty($params['grade_id'])) {
            $filter['grade_id'] = (int)$params['grade_id'];
        }

        // 关键词查询（按手机号模糊查询）
        if (!empty($params['keyword'])) {
            $filter['mobile|like'] = trim($params['keyword']);
        }

        // 调用会员服务获取列表和总数
        $memberService = new MemberService();

        // 获取总数
        $totalCount = $memberService->getMemberCount($filter);

        // 获取列表
        $memberList = $memberService->getMemberList($filter, $page, $pageSize, ['created' => 'DESC']);

        // 获取会员的unionid信息（getMemberList已经包含了unionid，但我们需要确保数据一致性）
        $userIdList = array_column($memberList, 'user_id');
        $unionidMap = [];
        if (!empty($userIdList)) {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $associations = $membersAssociationsRepository->lists([
                'user_id' => $userIdList,
                'company_id' => $companyId,
                'user_type' => 'wechat'
            ], 'user_id,unionid', 1, -1);

            if (!empty($associations)) {
                foreach ($associations as $assoc) {
                    $unionidMap[$assoc['user_id']] = $assoc['unionid'] ?? '';
                }
            }
        }

        // 处理返回数据，添加unionid和其他信息
        $memberBrowseHistoryService = new MemberBrowseHistoryService();
        $itemService = new ItemsService();
        $orderService = $this->getOrderService('normal');

        foreach ($memberList as $key => &$value) {
            // 确保unionid存在
            if (empty($value['unionid']) && isset($unionidMap[$value['user_id']])) {
                $value['unionid'] = $unionidMap[$value['user_id']];
            }

            // 消费总额
            $total_amount = $orderService->sum(['user_id' => $value['user_id'], 'company_id' => $companyId], 'total_fee');
            $value['total_amount'] = $total_amount;

            // 浏览历史
            $browseHistory = $memberBrowseHistoryService->lists(['user_id' => $value['user_id'], 'company_id' => $companyId], 1, 1, ['updated' => 'DESC']);
            if ($browseHistory['list']) {
                $latestItem = $itemService->getItem(['item_id' => $browseHistory['list'][0]['item_id']]);
                $value['browse_count'] = $browseHistory['total_count'];
                $value['browse_time'] = date('Y-m-d H:i:s', $browseHistory['list'][0]['updated']);
                $value['browse_item_name'] = $latestItem['item_name'] ?? null;
                $value['browse_item_id'] = $browseHistory['list'][0]['item_id'];
            } else {
                $value['browse_count'] = 0;
                $value['browse_time'] = '';
                $value['browse_item_name'] = null;
                $value['browse_item_id'] = null;
            }
        }
        unset($value);

        $return = [
            'count' => $totalCount,
            'list' => $memberList
        ];
        app('log')->info('david-memberList--->'. json_encode($return));
        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    /**
     * 获取导购客户 unionid 列表（scope=customer 时使用）
     *
     * @param Request $request
     * @param int $companyId
     * @param array $params
     * @return \Illuminate\Http\Response
     */
    private function getCustomerUnionids(Request $request, int $companyId, array $params)
    {
        // 必须提供导购编号
        if (empty($params['salesperson_code'])) {
            return $this->api_response('fail', 'scope为customer时，salesperson_code必填', null, 'E0001');
        }

        // 调用导购端接口获取所有 unionid（不带其他条件）
        try {
            $marketingCenterRequest = new MarketingCenterRequest();
            $requestData = [
                'employee_number' => $params['salesperson_code'],
            ];

            $result = $marketingCenterRequest->call($companyId, 'basics.salesperson.getBindMemberUnionids', $requestData);

            app('log')->info('获取导购客户unionid列表：导购端返回结果', [
                'company_id' => $companyId,
                'salesperson_code' => $params['salesperson_code'],
                'result' => $result,
            ]);

            // 处理返回结果
            if (empty($result) || !isset($result['errcode']) || $result['errcode'] != 0) {
                $errorMsg = $result['errmsg'] ?? '获取导购客户unionid列表失败';
                app('log')->warning('获取导购客户unionid列表：接口调用失败', [
                    'company_id' => $companyId,
                    'salesperson_code' => $params['salesperson_code'],
                    'error' => $errorMsg,
                    'result' => $result,
                ]);
                return $this->api_response('true', '操作成功', [], 'E0000');
            }

            // 获取 unionid 列表
            $unionids = $result['data']['unionids'] ?? [];
            if (empty($unionids) || !is_array($unionids)) {
                return $this->api_response('true', '操作成功', [], 'E0000');
            }

            // 根据 unionid 查询 user_id
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $associations = $membersAssociationsRepository->lists([
                'unionid' => $unionids,
                'company_id' => $companyId,
                'user_type' => 'wechat'
            ], 'user_id,unionid', 1, -1);

            if (empty($associations)) {
                return $this->api_response('true', '操作成功', [], 'E0000');
            }

            // 构建 user_id 和 unionid 的映射
            $userIdList = array_column($associations, 'user_id');
            $unionidMap = [];
            foreach ($associations as $assoc) {
                $unionidMap[$assoc['user_id']] = $assoc['unionid'];
            }

            // 构建过滤条件
            $filter = [
                'company_id' => $companyId,
                'user_id' => $userIdList,
            ];

            // 等级查询
            if (!empty($params['grade_id'])) {
                $filter['grade_id'] = (int)$params['grade_id'];
            }

            // 积分范围查询
            if (!empty($params['point_start'])) {
                $filter['point|gte'] = (int)$params['point_start'];
            }
            if (!empty($params['point_end'])) {
                $filter['point|lte'] = (int)$params['point_end'];
            }

            // 调用会员服务获取符合条件的会员列表
            $memberService = new MemberService();
            $memberList = $memberService->getMemberList($filter, 1, -1, ['created' => 'DESC']);

            // 如果设置了购买金额条件，需要进一步过滤
            $filteredUserIds = array_column($memberList, 'user_id');

            if (!empty($params['buy_start']) || !empty($params['buy_end'])) {
                $orderService = $this->getOrderService('normal');
                $finalUserIds = [];

                foreach ($filteredUserIds as $userId) {
                    // 计算该会员的购买总额
                    $totalAmount = $orderService->sum(['user_id' => $userId, 'company_id' => $companyId], 'total_fee');

                    // 判断是否满足购买金额条件
                    $meetBuyCondition = true;
                    if (!empty($params['buy_start']) && $totalAmount < (int)$params['buy_start']) {
                        $meetBuyCondition = false;
                    }
                    if (!empty($params['buy_end']) && $totalAmount > (int)$params['buy_end']) {
                        $meetBuyCondition = false;
                    }

                    if ($meetBuyCondition) {
                        $finalUserIds[] = $userId;
                    }
                }

                $filteredUserIds = $finalUserIds;
            }

            // 根据过滤后的 user_id 获取对应的 unionid
            $finalUnionids = [];
            foreach ($filteredUserIds as $userId) {
                if (isset($unionidMap[$userId])) {
                    $finalUnionids[] = $unionidMap[$userId];
                }
            }

            return $this->api_response('true', '操作成功', $finalUnionids, 'E0000');

        } catch (\Exception $e) {
            app('log')->error('获取导购客户unionid列表：系统异常', [
                'company_id' => $companyId,
                'salesperson_code' => $params['salesperson_code'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->api_response('fail', '获取导购客户unionid列表失败：' . $e->getMessage(), [], 'E9999');
        }
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.cardGrades",
     *     summary="获取会员卡等级列表",
     *     tags={"会员"},
     *     description="获取会员卡等级列表，包括等级信息、会员数量等",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.cardGrades" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="true", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="array", description="会员卡等级列表",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="grade_id", type="integer", example=1, description="等级ID"),
     *                  @SWG\Property( property="company_id", type="integer", example=1001, description="企业ID"),
     *                  @SWG\Property( property="grade_name", type="string", example="普通会员", description="等级名称"),
     *                  @SWG\Property( property="default_grade", type="boolean", example=true, description="是否默认等级"),
     *                  @SWG\Property( property="background_pic_url", type="string", example="https://example.com/bg.jpg", description="背景图片URL"),
     *                  @SWG\Property( property="promotion_condition", type="object", description="升级条件",
     *                      @SWG\Property( property="total_consumption", type="integer", example=10000, description="累计消费金额（分）"),
     *                      @SWG\Property( property="total_order_count", type="integer", example=10, description="累计订单数"),
     *                  ),
     *                  @SWG\Property( property="privileges", type="object", description="等级权益",
     *                      @SWG\Property( property="discount", type="integer", example=95, description="折扣（百分比，如95表示95折）"),
     *                      @SWG\Property( property="point_rate", type="integer", example=110, description="积分倍率（百分比，如110表示1.1倍）"),
     *                  ),
     *                  @SWG\Property( property="description", type="string", example="普通会员等级", description="等级描述"),
     *                  @SWG\Property( property="grade_background", type="string", example="#FF0000", description="等级背景色"),
     *                  @SWG\Property( property="member_count", type="integer", example=100, description="该等级下的会员数量"),
     *                  @SWG\Property( property="crm_open", type="string", example="false", description="CRM是否开启"),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getMemberCardGrades(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];

        try {
            $memberCardService = new MemberCardService();
            $isMemberCount = true; // 包含会员数量统计
            $result = $memberCardService->getCompanyGradeSimpleList($companyId);


        } catch (\Exception $e) {
            app('log')->error('获取会员卡等级列表失败::' . $e->getMessage() . '::companyId=' . $companyId . '::trace=' . $e->getTraceAsString());
            $this->api_response('fail', '获取会员卡等级列表失败：' . $e->getMessage(), [], 'E9999');
        }
        $this->api_response('true', '操作成功', $result, 'E0000');
    }
}
