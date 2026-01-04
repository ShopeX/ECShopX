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
use GoodsBundle\Services\ItemsService;
use OrdersBundle\Services\TradeService;
use ThirdPartyBundle\Services\DmCrm\MemberService as DmMemberService;

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
}
