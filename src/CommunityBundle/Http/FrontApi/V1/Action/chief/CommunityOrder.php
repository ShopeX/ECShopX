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

namespace CommunityBundle\Http\FrontApi\V1\Action\chief;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;
use AftersalesBundle\Services\AftersalesService;
use AftersalesBundle\Services\AftersalesRefundService;
use Dingo\Api\Exception\ResourceException;
use CommunityBundle\Services\CommunityActivityService;
use EspierBundle\Services\Export\NormalCommunityOrderExportService;

class CommunityOrder extends BaseController
{
    use GetOrderServiceTrait;

    /**
     * @SWG\Get(
     *     path="/wxapp/community/orders",
     *     summary="获取订单列表",
     *     tags={"社区团"},
     *     description="getOrderList",
     *     operationId="getOrderList",
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
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_seller",
     *         in="query",
     *         description="是否团长端",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="activity_name",
     *         in="query",
     *         description="活动名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         type="string",
     *     ),
      *     @SWG\Parameter(
     *         name="activity_id",
     *         in="query",
     *         description="活动ID",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_begin",
     *         in="query",
     *         description="查询开始时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_end",
     *         in="query",
     *         description="查询结束时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="delivery_type",
     *         in="query",
     *         description="配送类型, normal: 普通快递, ziti: 自提, dada： 同城配",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_by",
     *         in="query",
     *         description="订单时间排序 asc:正序 desc:倒序",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="order_id", type="string", example="3321684000300350", description="订单号"),
     *                           @SWG\Property(property="title", type="string", example="测试多规格1...", description="订单标题"),
     *                           @SWG\Property(property="total_fee", type="string", example="16", description="订单金额，以分为单位"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="store_name", type="string", example="", description=""),
     *                           @SWG\Property(property="user_id", type="string", example="20350", description="用户id"),
     *                           @SWG\Property(property="mobile", type="string", example="17638125092", description="手机号"),
     *                           @SWG\Property(property="receipt_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单, ziti: 自提订单, dada: 同城"),
     *                           @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                           @SWG\Property(property="create_time", type="string", example="1612343172", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="string", example="1612343255", description="订单更新时间"),
     *                           @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                           @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                           @SWG\Property(property="authorizer_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="wxa_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                           @SWG\Property(property="total_rebate", type="string", example="0", description="订单总分销金额，以分为单位"),
     *                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                           @SWG\Property(property="delivery_time", type="string", example="1612343255", description="发货时间"),
     *                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                           @SWG\Property(property="member_discount", type="string", example="4", description="会员折扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                           @SWG\Property(property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                           @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                           @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                           @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                           @SWG\Property(property="promoter_user_id", type="string", example="", description=""),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="fee_rate", type="string", example="1", description="货币汇率"),
     *                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                           @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                           @SWG\Property(property="promoter_shop_id", type="string", example="0", description=""),
     *                           @SWG\Property(property="source_name", type="string", example="-", description=""),
     *                           @SWG\Property(property="create_date", type="string", example="2021-02-03 17:06:12", description=""),
     *                           @SWG\Property(property="user_delete", type="boolean", example="true", description="是否注销")
     *                 ),
     *               ),
     *              @SWG\Property(property="pager", type="object", description="",
     *                   @SWG\Property(property="count", type="string", example="7999", description="总记录数"),
     *                   @SWG\Property(property="page_no", type="integer", example="1", description="页码"),
     *                   @SWG\Property(property="page_size", type="integer", example="20", description="每页记录条数"),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getOrderList(Request $request)
    {
        // XXX: review this code
        $authInfo = $request->get('auth');
        $params = $request->input();
        $filter = [];
        if (isset($params['time_start_begin'])) {
            $filter['create_time|gte'] = $params['time_start_begin'];
            $filter['create_time|lte'] = $params['time_start_end'];
        }

        if (isset($params['mobile'])) {
            $filter['mobile'] = $params['mobile'];
        }

        $filter['order_type']  = isset($params['order_type']) ? $params['order_type'] : 'normal';
        $filter['order_class'] = isset($params['order_class']) ? $params['order_class'] : 'community';

        //订单状态 TODO
        if (isset($params['status'])) {
            $status = isset($params['status']) ? $params['status'] : 0;
            switch ($status) {
                case 1: //待发货 待收货
                    $filter['order_status|in'] = ['PAYED', 'WAIT_BUYER_CONFIRM'];
                    $filter['ziti_status']     = 'NOTZITI';
                    break;
                case 3: //已完成
                    $filter['order_status']    = 'DONE';
                    $filter['delivery_status'] = 'DONE';
                    $filter['ziti_status']     = 'DONE';
                    break;
                case 4: //待自提
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status']  = 'PENDING';
                    break;
                case 5: //待付款
                    $filter['order_status']        = 'NOTPAY';
                    $filter['auto_cancel_time|gt'] = time();
                    break;
                case 6: //待发货
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status']  = 'NOTZITI';
                    break;
                case 7: //待评价
                    $filter['order_status'] = 'DONE';
                    $filter['is_rate']      = $request->input('is_rate') ?? 0;
                    break;
            }
        }

        $page     = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $orderService = $this->getOrderService('normal_community');
        $orderBy              = ['create_time' => 'DESC'];
        $filter['company_id'] = $authInfo['company_id'];

        if (isset($params['is_seller']) && $params['is_seller'] == 1 && isset($authInfo['chief_id']) && $authInfo['chief_id'] > 0) {
            $filter['chief_id'] = $authInfo['chief_id'];
            if ($request->input('activity_id') ?? '') {
                $filter['activity_id'] = $request->input('activity_id');
            }
            $result = $orderService->getChiefOrderList($filter, $page, $pageSize, $orderBy, true, 'front_list');
        } else {
            if ($request->input('activity_id') ?? '') {
                $filter['act_id'] = $request->input('activity_id');
            }
            $filter['user_id'] = $authInfo['user_id'];
            $result = $orderService->getOrderList($filter, $page, $pageSize, $orderBy, true, 'front_list');
        }

        // 订单列表显示是否可以申请售后
        $totalFee = 0;
        $appliedTotalNum = 0;
        $appliedTotalRefundFee = 0;
        $aftersalesService = new AftersalesService();
        $aftersalesRefundService = new AftersalesRefundService();
        foreach ($result['list'] as &$order) {
            $totalFee +=$order['total_fee'];
            $totalLeftAftersalesNum = 0;
            $order['can_apply_aftersales'] = 0;
            $totalCanAftersalesNum = $order['total_num'];
            $appliedTotalRefundFee = $aftersalesRefundService->getTotalRefundFee($order['company_id'], $order['order_id']);
            foreach ($order['items'] as &$item) {
                $appliedNum = $aftersalesService->getAppliedNum($item['company_id'], $item['order_id'], $item['id']); // 已申请数量 如果是自提订单发货数量等于子订单商品数量

                $item['delivery_item_num'] = $order['receipt_type'] == 'ziti' && $order['ziti_status'] == 'DONE' ? $item['num'] : $item['delivery_item_num'];
                // 剩余申请数量
                $item['left_aftersales_num'] = $item['delivery_item_num'] + $item['cancel_item_num'] - $appliedNum;
                $item['show_aftersales'] = $appliedNum > $item['cancel_item_num'] ? 1 : 0;
                $totalLeftAftersalesNum += $item['left_aftersales_num'];
                $totalCanAftersalesNum = $totalCanAftersalesNum - $appliedNum - $item['delivery_item_num'];
                // 用于判断整个订单是否显示售后申请按钮，只有其中一个商品可以申请售后就显示
                if ($totalLeftAftersalesNum > 0) {
                    if ($item['auto_close_aftersales_time'] > 0 && $item['auto_close_aftersales_time'] < time()) {
                        continue;
                    }
                    $order['can_apply_aftersales'] = 1;
                }


            }

            if ($order['order_status'] == 'CANCEL') {
                $appliedTotalNum ++;
            }
        }

        if ( !isset($result['statistics']) ) {
            $result['statistics'] = [
                'totalFee' => 0,
                'appliedTotalNum' => 0,
                'appliedTotalRefundFee' => 0,
            ];
        }
        

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/community/orders/export",
     *     summary="导出社区团购订单",
     *     tags={"订单"},
     *     description="导出社区团购订单",
     *     operationId="exportOrderData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", type="string"),    
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function exportActivityOrderData(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!($authInfo['chief_id'] ?? 0)) {
            throw new ResourceException('只有团长可以导出订单');
        }

        $activityId = $request->get('activity_id');
        if (!$activityId) {
            throw new ResourceException('活动ID必填');
        }

        $activityService = new CommunityActivityService();
        $activity = $activityService->getInfoById($activityId);
        if (!$activity || $activity['chief_id'] != $authInfo['chief_id']) {
            throw new ResourceException('活动不存在');
        }

        $exportService = new NormalCommunityOrderExportService();
        $result = $exportService->exportData(['activity_id' => $activityId]);

        return response()->json($result);
    }

    /**
     * @SWG\Post(
     *     path="/community/orders/qr_writeoff",
     *     summary="扫码核销订单",
     *     tags={"订单"},
     *     description="扫码核销订单",
     *     operationId="writeoffQR",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="code",
     *         in="path",
     *         description="核销码",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *                   @SWG\Property(property="data", type="object"),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function writeoffQR(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!($authInfo['chief_id'] ?? 0)) {
            throw new ResourceException('只有团长可以核销订单');
        }

        // 使用自提码核销
        $code = $request->get('code');
        if (!$code) {
            throw new ResourceException('code参数必填');
        }

        $orderService = $this->getOrderService('normal_community');
        // 从核销码中获取orderId
        $orderId = $orderService->getOrderIdByCode($code);
        if (!$orderId) {
            throw new ResourceException('核销码已过期');
        }

        $orderInfo = $orderService->getOrderInfo($authInfo['company_id'], $orderId);
        if (!$orderInfo) {
            throw new ResourceException('订单不存在');
        }

        if (!$orderInfo['orderInfo']) {
            throw new ResourceException('订单不存在');
        }

        if (!$orderInfo['orderInfo']['community_info']) {
            throw new ResourceException('只能核销自己开团的订单');
        }

        if ($orderInfo['orderInfo']['community_info']['chief_id'] != $authInfo['chief_id']) {
            throw new ResourceException('只能核销自己开团的订单');
        }

        if ($orderInfo['orderInfo']['ziti_code'] != intval(substr($code, 0, 6))) {
            throw new ResourceException('核销自提订单有误');
        }

        if ($orderInfo['orderInfo']['ziti_status'] == 'DONE' && $orderInfo['orderInfo']['order_status'] == 'DONE') {
            throw new ResourceException('该订单已完成自提，请重新确认');
        }

        if ($orderInfo['orderInfo']['cancel_status'] == 'WAIT_PROCESS') {
            throw new ResourceException('订单有未处理的取消申请，不能核销');
        }

        $result = $orderService->orderZitiWriteoff($authInfo['company_id'], $orderId, false, $code, 'chief', $authInfo['chief_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/community/orders/batch_writeoff",
     *     summary="一键核销订单",
     *     tags={"订单"},
     *     description="一键核销订单",
     *     operationId="batchWriteoff",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="",
     *         in="path",
     *         description="核销码",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *                   @SWG\Property(property="data", type="object",
     *                       @SWG\Property(property="status", type="boolean", description="操作结果"),
     *                   ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function batchWriteoff(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!($authInfo['chief_id'] ?? 0)) {
            throw new ResourceException('只有团长可以核销订单');
        }

        $activityId = $request->get('activity_id');
        if (!$activityId) {
            throw new ResourceException('活动ID必填');
        }

        $activityService = new CommunityActivityService();
        $activity = $activityService->getInfoById($activityId);
        if (!$activity || $activity['chief_id'] != $authInfo['chief_id']) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['delivery_status'] != 'SUCCESS') {
            throw new ResourceException('未确认收货不能核销');
        }

        $filter = [
            'act_id' => $activityId,
            'pay_status' => 'PAYED',
            'cancel_status' => ['NO_APPLY_CANCEL', 'FAILS'],
            'ziti_status' => 'PENDING',
            'company_id' => $authInfo['company_id'],
        ];
        $orderService = $this->getOrderService('normal_community');
        $orderList = $orderService->getOrderList($filter);
        foreach ($orderList['list'] as $order) {
            $orderService->orderZitiWriteoff($authInfo['company_id'], $order['order_id'], false, '', 'chief', $authInfo['chief_id']);
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/community/orders/writeoff/{order_id}",
     *     summary="自提订单核销",
     *     tags={"订单"},
     *     description="自提订单核销",
     *     operationId="writeoff",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="path",
     *         description="订单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *                   @SWG\Property(property="data", type="object"),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function writeoff($order_id, Request $request)
    {
        // XXX: review this code
        $authInfo = $request->get('auth');

        $orderService = $this->getOrderService('normal_community');
        $orderInfo = $orderService->getOrderInfo($authInfo['company_id'], $order_id);
        if (!$orderInfo) {
            throw new ResourceException('订单不存在');
        }

        if (!$orderInfo['orderInfo']) {
            throw new ResourceException('订单不存在');
        }

        if ($orderInfo['orderInfo']['user_id'] != $authInfo['user_id']) {
            throw new ResourceException('只能核销自己跟团的订单');
        }

        if ($orderInfo['orderInfo']['ziti_status'] == 'DONE' && $orderInfo['orderInfo']['order_status'] == 'DONE') {
            throw new ResourceException('该订单已完成自提，请重新确认');
        }

        if ($orderInfo['orderInfo']['cancel_status'] == 'WAIT_PROCESS') {
            throw new ResourceException('订单有未处理的取消申请，不能核销');
        }

        $result = $orderService->orderZitiWriteoff($authInfo['company_id'], $order_id, false, '', 'user', $auth['user_id']);
        return $this->response->array($result);
    }
}
