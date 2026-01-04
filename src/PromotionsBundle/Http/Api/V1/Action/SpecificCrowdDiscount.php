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

namespace PromotionsBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\SpecificCrowdDiscountService;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\MemberTagsService;

class SpecificCrowdDiscount extends Controller
{
    public $service;
    public function __construct()
    {
        $this->service = new SpecificCrowdDiscountService();
    }

    /**
      * @SWG\Post(
      *     path="/specific/crowd/discount",
      *     summary="创建定向促销",
      *     tags={"营销"},
      *     description="创建定向促销",
      *     operationId="createSpecificCrowdDiscount",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="specific_type", in="formData", description="人群类型，默认member_tag:会员标签", type="string"),
      *     @SWG\Parameter( name="specific_id", in="formData", description="适用人群标签id", type="integer"),
      *     @SWG\Parameter( name="specific_name", in="formData", description="适用人群标签名称", type="string"),
      *     @SWG\Parameter( name="cycle_type", in="formData", description="周期类型，1：自然月；2：指定周期", type="integer"),
      *     @SWG\Parameter( name="start_time", in="formData", description="指定周期开始时间(时间戳)", type="string"),
      *     @SWG\Parameter( name="end_time", in="formData", description="指定周期结束时间(时间戳)", required=true, type="string"),
      *     @SWG\Parameter( name="discount", in="formData", description="优惠的折扣（%）", required=true, type="integer"),
      *     @SWG\Parameter( name="limit_total_money", in="formData", description="周期内最高优惠金额（元）", type="integer"),
      *     @SWG\Parameter( name="status", in="formData", description="状态，1:暂存，2:已发布, 3:停用", type="integer"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *             @SWG\Schema(
      *                 @SWG\Property(
      *                     property="data",
      *                     type="object",
      *                     @SWG\Property(property="id", type="string", example="37", description="营销id"),
      *                     @SWG\Property( property="specific_type", type="string", example="member_tag", description="特定人群类型"),
      *                     @SWG\Property( property="specific_id", type="string", example="238", description="定向条件id"),
      *                     @SWG\Property( property="cycle_type", type="string", example="1", description="周期类型,1:自然月;2:指定时段"),
      *                     @SWG\Property( property="start_time", type="string", example="1609430400", description="开始时间"),
      *                     @SWG\Property( property="end_time", type="string", example="1612108799", description="结束时间"),
      *                     @SWG\Property( property="discount", type="string", example="88", description="折扣值（百分比) "),
      *                     @SWG\Property( property="limit_total_money", type="string", example="100000", description="每人累计限额(分)"),
      *                     @SWG\Property( property="status", type="string", example="1", description="状态，1:暂存，2:已发布, 3:停用, 4:已过期"),
      *                     @SWG\Property( property="created", type="string", example="1611812910", description="创建时间"),
      *                     ),
      *             )
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */

    public function createSpecificCrowdDiscount(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $postdata = $request->all('specific_id', 'start_time', 'end_time', 'discount', 'limit_total_money', 'status');
        $postdata['company_id'] = $authUser['company_id'];
        $postdata['specific_type'] = $request->get('specific_type', 'member_tag');
        $postdata['cycle_type'] = $request->get('cycle_type', 1);
        if (($postdata['cycle_type'] ?? 1) == 2 && !($postdata['start_time'] ?? '') && !($postdata['start_time'] ?? '')) {
            throw new ResourceException(trans('PromotionsBundle.cycle_start_end_time_required'));
        }
        if (empty($postdata['specific_id'])) {
            throw new ResourceException(trans('PromotionsBundle.please_select_target_crowd'));
        }
        if (isset($postdata['start_time']) && strlen($postdata['start_time']) > 10) {
            $postdata['start_time'] = $postdata['start_time'] / 1000;
        }

        if (isset($postdata['end_time']) && strlen($postdata['end_time']) > 10) {
            $postdata['end_time'] = $postdata['end_time'] / 1000;
        }

        // 校验参数
        $postdata = $this->service->__checkPostData($postdata);
        $postdata['limit_total_money'] = bcmul($postdata['limit_total_money'], 100);
        if ($postdata['limit_total_money'] > 2147483647) {
            throw new ResourceException(trans('PromotionsBundle.discount_limit_exceeds_max'));
        }
        if ($request->get('id')) {
            $filter['company_id'] = $authUser['company_id'];
            $filter['id'] = $request->get('id');
            $result = $this->service->updateOneBy($filter, $postdata);
        } else {
            $result = $this->service->create($postdata);
        }
        return $this->response->array($result);
    }

    /**
      * @SWG\Put(
      *     path="/specific/crowd/discount",
      *     summary="更新定向促销",
      *     tags={"营销"},
      *     description="更新定向促销",
      *     operationId="updateSpecificCrowdDiscount",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="id", in="query", description="适用店铺", type="string"),
      *     @SWG\Parameter( name="company_id", in="query", description="适用店铺", type="string"),
      *     @SWG\Parameter( name="specific_type", in="query", description="人群类型，默认member_tag:会员标签", type="string"),
      *     @SWG\Parameter( name="specific_id", in="query", description="适用人群标签id", type="integer"),
      *     @SWG\Parameter( name="specific_name", in="query", description="适用人群标签名称", type="string"),
      *     @SWG\Parameter( name="cycle_type", in="query", description="周期类型，1：自然月；2：指定周期", type="integer"),
      *     @SWG\Parameter( name="start_time", in="query", description="指定周期开始时间", type="string"),
      *     @SWG\Parameter( name="end_time", in="query", description="指定周期结束时间", required=true, type="string"),
      *     @SWG\Parameter( name="discount", in="query", description="优惠的折扣（%）", required=true, type="integer"),
      *     @SWG\Parameter( name="limit_total_money", in="query", description="周期内最高优惠金额（元）", type="integer"),
      *     @SWG\Parameter( name="status", in="query", description="状态", type="integer"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *             @SWG\Schema(
      *                 @SWG\Property(
      *                     property="data",
      *                     type="object",
      *                     @SWG\Property(property="id", type="string", example="37", description="营销id"),
      *                     @SWG\Property( property="specific_type", type="string", example="member_tag", description="特定人群类型"),
      *                     @SWG\Property( property="specific_id", type="string", example="238", description="定向条件id"),
      *                     @SWG\Property( property="cycle_type", type="string", example="1", description="周期类型,1:自然月;2:指定时段"),
      *                     @SWG\Property( property="start_time", type="string", example="1609430400", description="开始时间"),
      *                     @SWG\Property( property="end_time", type="string", example="1612108799", description="结束时间"),
      *                     @SWG\Property( property="discount", type="string", example="88", description="折扣值（百分比) "),
      *                     @SWG\Property( property="limit_total_money", type="string", example="100000", description="每人累计限额(分)"),
      *                     @SWG\Property( property="status", type="string", example="1", description="状态，1:暂存，2:已发布, 3:停用, 4:已过期"),
      *                     @SWG\Property( property="created", type="string", example="1611812910", description="创建时间"),
      *                     ),
      *             )
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function updateSpecificCrowdDiscount(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $postdata = $request->all('specific_id', 'cycle_type', 'start_time', 'end_time', 'discount', 'limit_total_money', 'status');
        $postdata['company_id'] = $authUser['company_id'];
        if (($postdata['cycle_type'] ?? 1) == 2 && !($postdata['start_time'] ?? '') && !($postdata['start_time'] ?? '')) {
            throw new ResourceException(trans('PromotionsBundle.cycle_start_end_time_required'));
        }

        if (isset($postdata['start_time']) && strlen($postdata['start_time']) > 10) {
            $postdata['start_time'] = $postdata['start_time'] / 1000;
        }

        if (isset($postdata['end_time']) && strlen($postdata['end_time']) > 10) {
            $postdata['end_time'] = $postdata['end_time'] / 1000;
        }

        $postdata['limit_total_money'] = bcmul($postdata['limit_total_money'], 100);
        $postdata['specific_type'] = $request->get('specific_type', 'member_tag');
        // 校验参数
        $postdata = $this->service->__checkPostData($postdata);
        $filter['company_id'] = $authUser['company_id'];
        $filter['id'] = $request->get('id');
        $result = $this->service->updateOneBy($filter, $postdata);
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/specific/crowd/discountList",
      *     summary="获取定向促销列表",
      *     tags={"营销"},
      *     description="获取定向促销列表",
      *     operationId="getSpecificCrowdDiscountList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="status", in="query", description="状态，1:暂存，2:已发布, 3:停用", type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *                  @SWG\Property( property="total_count", type="string", example="36", description="总条数"),
      *                  @SWG\Property( property="list", type="array",
      *                      @SWG\Items( type="object",
      *                          @SWG\Property( property="id", type="string", example="37", description="营销id"),
      *                          @SWG\Property( property="specific_type", type="string", example="member_tag", description="特定人群类型"),
      *                          @SWG\Property( property="specific_id", type="string", example="238", description="定向条件id"),
      *                          @SWG\Property( property="cycle_type", type="string", example="1", description="周期类型,1:自然月;2:指定时段"),
      *                          @SWG\Property( property="start_time", type="string", example="1609430400", description="开始时间"),
      *                          @SWG\Property( property="end_time", type="string", example="1612108799", description="结束时间"),
      *                          @SWG\Property( property="discount", type="string", example="88", description="折扣值(%)"),
      *                          @SWG\Property( property="limit_total_money", type="string", example="1000", description="每人累计限额"),
      *                          @SWG\Property( property="status", type="string", example="1", description="状态，1:暂存，2:已发布, 3:停用, 4:已过期"),
      *                          @SWG\Property( property="created", type="string", example="1611812910", description=""),
      *                          @SWG\Property( property="specific_name", type="string", example="11", description="定向条件名称"),
      *                          @SWG\Property( property="start_date", type="string", example="2021-01-01", description="开始时间"),
      *                          @SWG\Property( property="end_date", type="string", example="2021-01-31", description="结束时间"),
      *                       ),
      *                  ),
      *          ),
      *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function getSpecificCrowdDiscountList(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        if ($request->get('status')) {
            $filter['status'] = $request->get('status');
        }
        if ($request->get('specific_id')) {
            $filter['specific_id'] = $request->get('specific_id');
        }
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $orderBy = ['created' => 'desc', 'id' => 'asc'];
        $result = $this->service->lists($filter, "*", $page, $pageSize, $orderBy);
        if (!($result['list'] ?? [])) {
            return $this->response->array($result);
        }
        $memberTagsService = new MemberTagsService();
        $tf = [
            'company_id' => $authUser['company_id'],
            'tag_id' => array_column($result['list'], 'specific_id'),
        ];
        $tags = $memberTagsService->getLists($tf, 'tag_id,tag_name');
        $tags = array_column($tags, 'tag_name', 'tag_id');
        foreach ($result['list'] as &$value) {
            $value['specific_name'] = $tags[$value['specific_id']] ?? '';
            $value['start_date'] = date('Y-m-d', $value['start_time']);
            $value['end_date'] = date('Y-m-d', $value['end_time']);
            $value['limit_total_money'] = bcdiv($value['limit_total_money'], 100, 2);
        }
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/specific/crowd/discountInfo",
      *     summary="获取定向促销详情",
      *     tags={"营销"},
      *     description="获取定向促销详情",
      *     operationId="getSpecificCrowdDiscountInfo",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="id", in="query", description="营销id", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *             @SWG\Schema(
      *                 @SWG\Property(
      *                     property="data",
      *                     type="object",
      *                     @SWG\Property(property="id", type="string", example="37", description="营销id"),
      *                     @SWG\Property( property="specific_type", type="string", example="member_tag", description="特定人群类型"),
      *                     @SWG\Property( property="specific_id", type="string", example="238", description="定向条件id"),
      *                     @SWG\Property( property="cycle_type", type="string", example="1", description="周期类型,1:自然月;2:指定时段"),
      *                     @SWG\Property( property="start_time", type="string", example="1609430400", description="开始时间"),
      *                     @SWG\Property( property="end_time", type="string", example="1612108799", description="结束时间"),
      *                     @SWG\Property( property="discount", type="string", example="88", description="折扣值（百分比) "),
      *                     @SWG\Property( property="limit_total_money", type="string", example="100000", description="每人累计限额(分)"),
      *                     @SWG\Property( property="status", type="string", example="1", description="状态，1:暂存，2:已发布, 3:停用, 4:已过期"),
      *                     @SWG\Property( property="created", type="string", example="1611812910", description="创建时间"),
      *                     ),
      *             )
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function getSpecificCrowdDiscountInfo(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['id'] = $request->get('id');
        $result = $this->service->getInfo($filter);
        if ($result) {
            $memberTagsService = new MemberTagsService();
            $tag = $memberTagsService->getInfo(['company_id' => $authUser['company_id'], 'tag_id' => $result['specific_id']]);
            $result['specific_name'] = $tag['tag_name'];
            $result['start_date'] = date('Y-m-d', $result['start_time']);
            $result['end_date'] = date('Y-m-d', $result['end_time']);
            $result['limit_total_money'] = bcdiv($result['limit_total_money'], 100);
        }
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/specific/crowd/discountLogList",
      *     summary="定向促销优惠日志",
      *     tags={"营销"},
      *     description="定向促销优惠日志",
      *     operationId="getSpecificcrowddiscountLogList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="activity_id", in="query", description="促销id", required=true, type="integer"),
      *     @SWG\Parameter( name="mobile", in="query", description="会员手机号", type="string"),
      *     @SWG\Parameter( name="order_id", in="query", description="订单号", type="string"),
      *     @SWG\Parameter( name="time_start_begin", in="query", description="筛选开始时间(时间戳)", type="integer"),
      *     @SWG\Parameter( name="time_start_end", in="query", description="筛选结束时间(时间戳)", type="integer"),
      *     @SWG\Parameter( name="page", in="query", description="页数，默认1", type="integer"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页条数，默认-1", type="integer"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
      *                  @SWG\Property( property="list", type="array",
      *                      @SWG\Items( type="object",
      *                          @SWG\Property( property="id", type="string", example="524", description="营销id"),
      *                          @SWG\Property( property="user_id", type="string", example="20341", description="会员ID"),
      *                          @SWG\Property( property="order_id", type="string", example="3277682000070341", description="订单编号"),
      *                          @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
      *                          @SWG\Property( property="activity_id", type="string", example="36", description="活动ID "),
      *                          @SWG\Property( property="specific_id", type="string", example="210", description="定向条件id"),
      *                          @SWG\Property( property="specific_name", type="string", example="小伙子", description="定向条件名称"),
      *                          @SWG\Property( property="activity_month", type="string", example="12.21", description="促销月份"),
      *                          @SWG\Property( property="action_type", type="string", example="plus", description="操作方式，plus:加，less:减"),
      *                          @SWG\Property( property="created", type="string", example="1608541406", description=""),
      *                          @SWG\Property( property="user_mobile", type="string", example="15140566318", description="会员手机号"),
      *                       ),
      *                  ),
      *          ),
      *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones")))
      * )
      */
    public function getSpecificcrowddiscountLogList(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['activity_id'] = $request->get('activity_id');
        if ($request->get('mobile')) {
            $mf['company_id'] = $filter['company_id'];
            $mf['mobile'] = $request->get('mobile');
            $memberService = new MemberService();
            $member = $memberService->getMemberInfo($mf, false);
            if (!$member) {
                return $this->response->array(['list' => [], 'total_count' => 0]);
            }
            $filter['user_id'] = $member['user_id'];
        }
        if ($request->get('order_id')) {
            $filter['order_id'] = $request->get('order_id');
        }
        if ($request->get('time_start_begin')) {
            $filter['created|gte'] = $request->get('time_start_begin');
        }
        if ($request->get('time_start_end')) {
            $filter['created|lt'] = $request->get('time_start_end');
        }
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', -1);
        $result = $this->service->getDiscountLogList($filter, $page, $pageSize);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        if (($member ?? []) && ($result['list'] ?? [])) {
            foreach ($result['list'] as &$value) {
                $value['user_mobile'] = $member['mobile'];
                if ($datapassBlock) {
                    $value['user_mobile'] = data_masking('mobile', (string) $value['user_mobile']);
                }
            }
            unset($value);
        } elseif ($result['list'] ?? []) {
            $memberService = new MemberService();
            $userIds = array_column($result['list'], 'user_id');
            $user = $memberService->getMobileByUserIds($filter['company_id'], $userIds);
            foreach ($result['list'] as &$value) {
                $value['user_mobile'] = $user[$value['user_id']] ?? '-';
                if ($datapassBlock) {
                    $value['user_mobile'] != '-' and $value['user_mobile'] = data_masking('mobile', (string) $value['user_mobile']);
                }
            }
        }

        return $this->response->array($result);
    }
}
