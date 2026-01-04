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

namespace CommunityBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use CommunityBundle\Services\CommunityActivityService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrderProfitService;
use OrdersBundle\Services\DeliveryProcessLogServices;
use MembersBundle\Services\MemberService;
use CommunityBundle\Services\CommunityChiefDistributorService;
use EspierBundle\Jobs\ExportFileJob;

class CommunityOrder extends BaseController
{
    use GetOrderServiceTrait;

    /**
     * @SWG\Get(
     *     path="/community/orders",
     *     summary="获取订单列表",
     *     tags={"社区团管理端"},
     *     description="getOrderList",
     *     operationId="getWxShopsList",
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
        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        if ($request->input('time_start_begin')) {
            $timeStart = $request->input('time_start_begin');
            $timEnd = $request->input('time_start_end');
            if (false !== strpos($timeStart, '-')) {
                $timeStart = strtotime($timeStart.' 00:00:00');
                $timEnd = strtotime($timEnd.' 23:59:59');
            }
            $filter['create_time|gte'] = $timeStart;
            $filter['create_time|lte'] = $timEnd;
        }

        if ($request->input('delivery_time_begin')) {
            $deliveryTimeStart = $request->input('delivery_time_begin');
            $deliveryTimEnd = $request->input('delivery_time_end');
            if (false !== strpos($deliveryTimeStart, '-')) {
                $deliveryTimeStart = strtotime($deliveryTimeStart.' 00:00:00');
                $deliveryTimEnd = strtotime($deliveryTimEnd.' 23:59:59');
            }
            $filter['delivery_time|gte'] = $deliveryTimeStart;
            $filter['delivery_time|lte'] = $deliveryTimEnd;
        }

        $status = $request->input('order_status');
        if ($status) {
            switch ($status) {
                case 'ordercancel':   //已取消待退款
                    $filter['order_status'] = 'CANCEL_WAIT_PROCESS';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                    break;
                case 'refundprocess':    //已取消待退款
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'NO_APPLY_CANCEL';
                    break;
                case 'refundsuccess':    //已取消已退款
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'SUCCESS';
                    break;
                case 'notship':  //待发货
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status|in'] = ['NO_APPLY_CANCEL', 'FAILS'];
                    $filter['receipt_type'] = 'logistics';
                    break;
                case 'cancelapply':  //待退款
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                    break;
                case 'ziti':  //待自提
                    $filter['receipt_type'] = 'ziti';
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status'] = 'PENDING';
                    break;
                case 'shipping':  //带收货
                    $filter['order_status'] = 'WAIT_BUYER_CONFIRM';
                    $filter['delivery_status'] = ['DONE', 'PARTAIL'];
                    $filter['receipt_type'] = 'logistics';
                    break;
                case 'finish':  //已完成
                    $filter['order_status'] = 'DONE';
                    break;
                case 'reviewpass':  //待审核
                    $filter['order_status'] = 'REVIEW_PASS';
                    break;
                case 'done_noinvoice':  //已完成未开票
                    $filter['order_status'] = 'DONE';
                    $filter['invoice|neq'] = null;
                    $filter['is_invoiced'] = 0;
                    break;
                case 'done_invoice':  //已完成已开票
                    $filter['order_status'] = 'DONE';
                    $filter['invoice|neq'] = null;
                    $filter['is_invoiced'] = 1;
                    break;
                default:
                    $filter['order_status'] = strtoupper($status);
                    break;
            }
        }

        // 待支付订单
        if (isset($filter['order_status']) && $filter['order_status'] == 'NOTPAY') {
            // FrontApi:WxappOrder:getOrderList {status: 5} 待支付订单
            $filter['auto_cancel_time|gt'] = time();
        }

        if ($receiver_name = $request->input('receiver_name')) {
            $filter['receiver_name'] = $receiver_name;
        }

        if ($item_name = $request->input('item_name')) {
            // 关联查询参数
            $filter['item_name'] = $item_name;
        }

        if ($order_id = $request->input('order_id')) {
            if (strlen($order_id) < 16) {
                $filter['order_id|like'] = '%'.$order_id.'%';
            } else {
                $filter['order_id'] = $order_id;
            }
        }
        if ($request->input('title')) {
            $filter['title|like'] = '%' . $request->input('title') . '%';
        }
        if ($mobile = $request->input('mobile')) {
            $filter['mobile'] = $mobile;
        }
        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        }
        if ($request->input('source_id')) {
            $filter['source_id'] = $request->input('source_id');
        }

        $operator_type = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operator_type == 'distributor') {
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        if (!isset($filter['shop_id']) && $request->input('shop_id')) {
            $filter['shop_id'] = $request->input('shop_id');
        }
        if ($request->input('receipt_type')) {
            $filter['receipt_type'] = $request->input('receipt_type');
        }
        if ($request->input('is_invoiced')) {
            $filter['is_invoiced'] = intval($request->input('is_invoiced'));
        }

        // 按活动名称搜索
        $activity_name = $request->input('activity_name');
        $activity_status = $request->input('activity_status');
        if (!empty($activity_name) || !empty($activity_status)) {
            $activity_filter = [];
            if (!empty($activity_name)) {
                $activity_filter['activity_name|contains'] = $activity_name;
            }
            if (!empty($activity_status)) {
                $activity_filter['activity_status'] = $activity_status;
            }
            if (!empty($activity_filter)) {
                $activity_filter['company_id'] = $filter['company_id'];
                $activityService = new CommunityActivityService();
                $activity = $activityService->getLists($activity_filter, 'activity_id');
                $act_ids = array_merge([0], array_column($activity, 'activity_id'));
                if (!empty($act_ids)) {
                    $filter['act_id'] = $act_ids;
                }
            }
        }

        $orderServiceType = 'normal_community';
        $filter['order_type'] = 'normal';
        $filter['order_class'] = 'community';
        $orderService = $this->getOrderService($orderServiceType);
        $orderBy = ['create_time' => 'DESC'];
        if ($request->input('order_by') == 'asc') {
            $orderBy = ['create_time' => 'ASC'];
        }
        $result = $orderService->getOrderList($filter, $page, $limit, $orderBy);
        $result['filter'] = $filter;

        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        foreach ($result['list'] ?? [] as $k => $order) {
            if ($datapassBlock) {
                if (isset($order['app_info'])) {
                    $buttons = array_column($order['app_info']['buttons'], null, 'type');
                    if (isset($buttons['contact'])) {
                        unset($buttons['contact']);
                    }
                    $result['list'][$k]['app_info']['buttons'] = array_values($buttons);
                }
                $result['list'][$k]['mobile'] = data_masking('mobile', (string) $order['mobile']);
                $result['list'][$k]['receiver_name'] = data_masking('truename', (string) $order['receiver_name']);
                $result['list'][$k]['receiver_mobile'] = data_masking('mobile', (string) $order['receiver_mobile']);
                $result['list'][$k]['receiver_address'] = data_masking('address', (string) $order['receiver_address']);
                $order['operator_desc'] = $order['operator_desc'] ?? '';
                $operator_desc = explode(' : ', $order['operator_desc']);
                if (count($operator_desc) == 2) {
                    $operator_mobile = data_masking('mobile', (string) $operator_desc[0]);
                    $operator_name = data_masking('truename', (string) $operator_desc[1]);
                    $result['list'][$k]['operator_desc'] = $operator_mobile . ' : '. $operator_name;
                }
            }
            if (isset($order['app_info'])) {
                // 订单列表页添加备注按钮
                array_unshift($result['list'][$k]['app_info']['buttons'], ['type' => 'mark', 'name' => '备注']);
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/community/order/{order_id}",
     *     summary="获取订单详情",
     *     tags={"社区团管理端"},
     *     description="获取订单详情",
     *     operationId="getOrderDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="orderInfo", type="object", description="",
     *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                   @SWG\Property(property="title", type="string", example="测试商品会员价导入...", description="订单标题"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                   @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
     *                   @SWG\Property(property="freight_fee", type="integer", example="0", description="运费价格，以分为单位"),
     *                   @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *                   @SWG\Property(property="item_fee", type="string", example="100", description="商品总金额，以分为单位"),
     *                   @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                   @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
     *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
     *                   @SWG\Property(property="step_paid_fee", type="integer", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                   @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                   @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提;dada:同城配"),
     *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                   @SWG\Property(property="order_status", type="string", example="WAIT_BUYER_CONFIRM", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                   @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *                   @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                   @SWG\Property(property="auto_cancel_time", type="string", example="1612150545", description="订单自动取消时间"),
     *                   @SWG\Property(property="auto_cancel_seconds", type="integer", example="-12452", description=""),
     *                   @SWG\Property(property="auto_finish_time", type="string", example="1612755464", description="订单自动完成时间"),
     *                   @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                   @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                   @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                   @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                   @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                   @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100", description="快递代码来源"),
     *                   @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                   @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                   @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                   @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                   @SWG\Property(property="delivery_time", type="integer", example="1612150664", description="发货时间"),
     *                   @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                   @SWG\Property(property="end_date", type="string", example="", description=""),
     *                   @SWG\Property(property="receiver_name", type="string", example="1232", description="收货人姓名"),
     *                   @SWG\Property(property="receiver_mobile", type="string", example="17653569856", description="收货人手机号"),
     *                   @SWG\Property(property="receiver_zip", type="string", example="000000", description="收货人邮编"),
     *                   @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *                   @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *                   @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *                   @SWG\Property(property="receiver_address", type="string", example="123123", description="收货人详细地址"),
     *                   @SWG\Property(property="member_discount", type="integer", example="20", description="会员折扣金额，以分为单位"),
     *                   @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                   @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                   @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
     *                   @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
     *                   @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                   @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                   @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                   @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
     *                   @SWG\Property(property="pay_type", type="string", example="point", description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
     *                   @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *                  @SWG\Property(property="third_params", type="object", description="",
     *                           @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     *                  ),
     *                   @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *                   @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
     *                   @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                   @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
     *                   @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *                   @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                   @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                   @SWG\Property(property="point_fee", type="integer", example="80", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                   @SWG\Property(property="point_use", type="integer", example="16", description="积分抵扣使用的积分数"),
     *                   @SWG\Property(property="uppoint_use", type="integer", example="0", description="积分抵扣使用的积分升值数"),
     *                   @SWG\Property(property="point_up_use", type="integer", example="0", description=""),
     *                   @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                   @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
     *                   @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
     *                   @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                   @SWG\Property(property="pack", type="string", example="", description="包装"),
     *                   @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
     *                   @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                   @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账 2分账"),
     *                   @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账 2已分账"),
     *                   @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                   @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
     *                   @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *                   @SWG\Property(property="extra_points", type="integer", example="0", description="订单获取额外积分"),
     *                   @SWG\Property(property="type", type="integer", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                   @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
     *                   @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *                   @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *                   @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
     *                   @SWG\Property(property="discount_info", type="array", description="",
     *                     @SWG\Items(
     *                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                          @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                          @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="can_apply_aftersales", type="integer", example="0", description=""),
     *                   @SWG\Property(property="distributor_name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="items", type="array", description="",
     *                     @SWG\Items(
     *                                           @SWG\Property(property="id", type="string", example="8997", description="ID"),
     *                                           @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                                           @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                                           @SWG\Property(property="item_id", type="string", example="5461", description="商品id"),
     *                                           @SWG\Property(property="item_bn", type="string", example="gyct2021001", description="商品编码"),
     *                                           @SWG\Property(property="item_name", type="string", example="测试商品会员价导入", description="商品名称"),
     *                                           @SWG\Property(property="pic", type="string", example="", description="商品图片"),
     *                                           @SWG\Property(property="num", type="integer", example="1", description="购买商品数量"),
     *                                           @SWG\Property(property="price", type="integer", example="100", description="单价，以分为单位"),
     *                                           @SWG\Property(property="total_fee", type="integer", example="0", description="应付总金额,以分为单位"),
     *                                           @SWG\Property(property="templates_id", type="integer", example="1", description="运费模板id"),
     *                                           @SWG\Property(property="rebate", type="integer", example="0", description="单个分销金额，以分为单位"),
     *                                           @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                                           @SWG\Property(property="item_fee", type="integer", example="100", description="商品总金额，以分为单位"),
     *                                           @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
     *                                           @SWG\Property(property="item_unit", type="string", example="", description="商品计量单位"),
     *                                           @SWG\Property(property="member_discount", type="integer", example="20", description="会员折扣金额，以分为单位"),
     *                                           @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                                           @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                                           @SWG\Property(property="discount_info", type="array", description="",
     *                                             @SWG\Items(
     *                                                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                                                          @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                                                          @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                                                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                                                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                                           @SWG\Property(property="is_total_store", type="string", example="1", description="是否是总部库存(true:总部库存，false:店铺库存)"),
     *                                           @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                                           @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
     *                                           @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
     *                                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                                           @SWG\Property(property="aftersales_status", type="string", example="", description="售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1 商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3 售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"),
     *                                           @SWG\Property(property="refunded_fee", type="integer", example="0", description="退款金额，以分为单位"),
     *                                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                                           @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                                           @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
     *                                           @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                                           @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
     *                                           @SWG\Property(property="item_spec_desc", type="string", example="颜色:粉红大格110cm,尺码:20cm", description="商品规格描述"),
     *                                           @SWG\Property(property="order_item_type", type="string", example="normal", description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
     *                                           @SWG\Property(property="volume", type="integer", example="0", description="商品体积"),
     *                                           @SWG\Property(property="weight", type="integer", example="0", description="商品重量"),
     *                                           @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                                           @SWG\Property(property="auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                                           @SWG\Property(property="share_points", type="integer", example="16", description="积分抵扣时分摊的积分值"),
     *                                           @SWG\Property(property="point_fee", type="integer", example="80", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                                           @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                                           @SWG\Property(property="delivery_item_num", type="integer", example="1", description="发货单发货数量"),
     *                                           @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="order_status_des", type="string", example="WAIT_BUYER_CONFIRM", description=""),
     *                   @SWG\Property(property="order_status_msg", type="string", example="待收货", description=""),
     *                   @SWG\Property(property="latest_aftersale_time", type="integer", example="0", description=""),
     *                   @SWG\Property(property="estimate_get_points", type="string", example="0", description=""),
     *                   @SWG\Property(property="delivery_type", type="string", example="new", description=""),
     *                   @SWG\Property(property="is_all_delivery", type="string", example="1", description=""),
     *              ),
     *              @SWG\Property(property="tradeInfo", type="object", description="",
     *                   @SWG\Property(property="tradeId", type="string", example="12345673319460000520394", description=""),
     *                   @SWG\Property(property="orderId", type="string", example="3319460000470394", description=""),
     *                   @SWG\Property(property="shopId", type="string", example="0", description=""),
     *                   @SWG\Property(property="userId", type="string", example="20394", description=""),
     *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
     *                   @SWG\Property(property="openId", type="string", example="", description=""),
     *                  @SWG\Property(property="discountInfo", type="object", description="",
     *                          @SWG\Property(property="member_price0", type="object", description="",
     *                                           @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                                           @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                                           @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                                           @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                                           @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                          ),
     *                  ),
     *                   @SWG\Property(property="mchId", type="string", example="", description=""),
     *                   @SWG\Property(property="totalFee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="discountFee", type="integer", example="20", description=""),
     *                   @SWG\Property(property="feeType", type="string", example="CNY", description=""),
     *                   @SWG\Property(property="payFee", type="integer", example="16", description=""),
     *                   @SWG\Property(property="tradeState", type="string", example="SUCCESS", description=""),
     *                   @SWG\Property(property="payType", type="string", example="point", description=""),
     *                   @SWG\Property(property="transactionId", type="string", example="", description=""),
     *                   @SWG\Property(property="wxaAppid", type="string", example="", description=""),
     *                   @SWG\Property(property="bankType", type="string", example="积分", description=""),
     *                   @SWG\Property(property="body", type="string", example="测试商品会员价导入...", description="交易商品简单描述"),
     *                   @SWG\Property(property="detail", type="string", example="测试商品会员价导入...", description="交易商品详情"),
     *                   @SWG\Property(property="timeStart", type="string", example="1612150245", description=""),
     *                   @SWG\Property(property="timeExpire", type="string", example="1612150245", description=""),
     *                   @SWG\Property(property="companyId", type="string", example="1", description=""),
     *                   @SWG\Property(property="authorizerAppid", type="string", example="", description=""),
     *                   @SWG\Property(property="curFeeType", type="string", example="CNY", description=""),
     *                   @SWG\Property(property="curFeeRate", type="integer", example="1", description=""),
     *                   @SWG\Property(property="curFeeSymbol", type="string", example="￥", description=""),
     *                   @SWG\Property(property="curPayFee", type="integer", example="16", description=""),
     *                   @SWG\Property(property="distributorId", type="string", example="103", description=""),
     *                   @SWG\Property(property="tradeSourceType", type="string", example="normal", description=""),
     *                   @SWG\Property(property="couponFee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="couponInfo", type="string", example="", description=""),
     *                   @SWG\Property(property="initalRequest", type="string", example="", description=""),
     *                   @SWG\Property(property="initalResponse", type="string", example="", description=""),
     *                   @SWG\Property(property="payDate", type="string", example="2021-02-01 11:30:45", description=""),
     *              ),
     *              @SWG\Property(property="distributor", type="object", description="",
     *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="is_distributor", type="string", example="1", description=""),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="mobile", type="string", example="17638125092", description="购买用户手机号"),
     *                   @SWG\Property(property="address", type="string", example="淀区中关村东路123号院", description=""),
     *                   @SWG\Property(property="name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="auto_sync_goods", type="string", example="1", description=""),
     *                   @SWG\Property(property="logo", type="string", example="https://wemall-media-dev.s3.cn-northwest-1.amazonaws.com.cn/1606288539555.maomi_laoshi-003.jpg", description=""),
     *                   @SWG\Property(property="contract_phone", type="string", example="17638125092", description=""),
     *                   @SWG\Property(property="banner", type="string", example="", description=""),
     *                   @SWG\Property(property="contact", type="string", example="孙帅帅", description=""),
     *                   @SWG\Property(property="is_valid", type="string", example="true", description=""),
     *                   @SWG\Property(property="lng", type="string", example="116.333545", description=""),
     *                   @SWG\Property(property="lat", type="string", example="39.969303", description=""),
     *                   @SWG\Property(property="child_count", type="integer", example="0", description=""),
     *                   @SWG\Property(property="is_default", type="integer", example="0", description=""),
     *                   @SWG\Property(property="is_audit_goods", type="string", example="1", description=""),
     *                   @SWG\Property(property="is_ziti", type="string", example="1", description=""),
     *                   @SWG\Property(property="regions_id", type="array", description="",
     *                      @SWG\Items(
     *                         type="string", example="110000", description=""
     *                      ),
     *                   ),
     *                   @SWG\Property(property="regions", type="array", description="",
     *                      @SWG\Items(
     *                         type="string", example="北京市", description=""
     *                      ),
     *                   ),
     *                   @SWG\Property(property="is_domestic", type="integer", example="1", description=""),
     *                   @SWG\Property(property="is_direct_store", type="integer", example="1", description=""),
     *                   @SWG\Property(property="province", type="string", example="北京市", description=""),
     *                   @SWG\Property(property="is_delivery", type="string", example="1", description=""),
     *                   @SWG\Property(property="city", type="string", example="北京市", description=""),
     *                   @SWG\Property(property="area", type="string", example="东城区", description=""),
     *                   @SWG\Property(property="hour", type="string", example="08:00-21:00", description=""),
     *                   @SWG\Property(property="created", type="integer", example="1606288943", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1611123611", description=""),
     *                   @SWG\Property(property="shop_code", type="string", example="1234567", description=""),
     *                   @SWG\Property(property="wechat_work_department_id", type="integer", example="0", description=""),
     *                   @SWG\Property(property="distributor_self", type="integer", example="0", description=""),
     *                   @SWG\Property(property="regionauth_id", type="string", example="2", description=""),
     *                   @SWG\Property(property="is_open", type="string", example="true", description=""),
     *                   @SWG\Property(property="rate", type="string", example="1.00", description=""),
     *                   @SWG\Property(property="store_address", type="string", example="北京市东城区淀区中关村东路123号院", description=""),
     *                   @SWG\Property(property="store_name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="phone", type="string", example="17638125092", description=""),
     *              ),
     *               @SWG\Property(property="cancelData", type="string", description=""),
     *              @SWG\Property(property="profit", type="object", description="",
     *                   @SWG\Property(property="id", type="string", example="3960", description="ID"),
     *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                   @SWG\Property(property="order_profit_status", type="string", example="1", description="0 无效分润 1 冻结分润 2 分润成功"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
     *                   @SWG\Property(property="pay_fee", type="string", example="0", description="支付金额"),
     *                   @SWG\Property(property="profit_type", type="integer", example="2", description="分润类型 1 总部分润 2 自营门店分润 3 加盟门店分润"),
     *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                   @SWG\Property(property="dealer_id", type="string", example="0", description="区域经销商id"),
     *                   @SWG\Property(property="distributor_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="order_distributor_id", type="string", example="103", description="下单当前所在门店id"),
     *                   @SWG\Property(property="distributor_nid", type="string", example="0", description="拉新导购当前所在门店id"),
     *                   @SWG\Property(property="seller_id", type="string", example="0", description="拉新导购id"),
     *                   @SWG\Property(property="popularize_distributor_id", type="string", example="0", description="推广门店id"),
     *                   @SWG\Property(property="popularize_seller_id", type="string", example="0", description="推广导购id"),
     *                   @SWG\Property(property="proprietary", type="string", example="2", description="判断拉新门店 0 无门店 1 自营门店 2 加盟门店"),
     *                   @SWG\Property(property="popularize_proprietary", type="string", example="2", description="判断推广门店 0 无门店 1 自营门店 2 加盟门店"),
     *                   @SWG\Property(property="dealers", type="string", example="0", description="区域经销商分成"),
     *                   @SWG\Property(property="distributor", type="string", example="0", description="拉新门店分成"),
     *                   @SWG\Property(property="seller", type="string", example="0", description="拉新导购分成（分给门店）"),
     *                   @SWG\Property(property="popularize_distributor", type="string", example="0", description="推广门店分成"),
     *                   @SWG\Property(property="popularize_seller", type="string", example="0", description="推广导购分成（分给门店）"),
     *                   @SWG\Property(property="commission", type="string", example="0", description="总部手续费"),
     *                  @SWG\Property(property="rule", type="object", description="",
     *                           @SWG\Property(property="show", type="string", example="1", description=""),
     *                           @SWG\Property(property="seller", type="string", example="50", description="拉新导购分成（分给门店）"),
     *                           @SWG\Property(property="distributor", type="string", example="50", description="拉新门店分成"),
     *                           @SWG\Property(property="plan_limit_time", type="string", example="0", description=""),
     *                           @SWG\Property(property="popularize_seller", type="string", example="50", description="推广导购分成（分给门店）"),
     *                           @SWG\Property(property="distributor_seller", type="string", example="50", description=""),
     *                  ),
     *                   @SWG\Property(property="created", type="integer", example="1612150245", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1612150245", description=""),
     *                   @SWG\Property(property="plan_close_time", type="string", example="1927510245", description="计划结算时间"),
     *                   @SWG\Property(property="distributor_info", type="string", description=""),
     *                   @SWG\Property(property="seller_info", type="string", description=""),
     *                   @SWG\Property(property="popularize_seller_info", type="string", description=""),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getOrderDetail($order_id, Request $request)
    {
        // Ref: 1996368445
        $companyId = app('auth')->user()->get('company_id');
        $orderService = $this->getOrderService('normal_community');
        $result = $orderService->getOrderInfo($companyId, $order_id);

        $orderProfitService = new OrderProfitService();
        $result['profit'] = $orderProfitService->getOrderProfit($order_id);
        $memberService = new MemberService();
        $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId,'user_id' => $result['orderInfo']['user_id']]);
        $result['orderInfo']['user_delete'] = false;
        if (empty($memberInfo)) {
            $result['orderInfo']['user_delete'] = true;
        }
        // 完善店务端附加字段
        if (isset($result['orderInfo']['app_info'])) {
            $deliveryProcessServices = new DeliveryProcessLogServices();
            $result['orderInfo']['app_info']['delivery_log'] = $deliveryProcessServices->getListByOrderData($result);
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            if ($datapassBlock) {
                if (isset($result['orderInfo']['app_info'])) {
                    $buttons = array_column($result['orderInfo']['app_info']['buttons'], null, 'type');
                    if (isset($buttons['contact'])) {
                        unset($buttons['contact']);
                    }
                    $result['orderInfo']['app_info']['buttons'] = array_values($buttons);
                }
                $result['orderInfo']['mobile'] = data_masking('mobile', (string) $result['orderInfo']['mobile']);
                $result['orderInfo']['receiver_name'] = data_masking('truename', (string) $result['orderInfo']['receiver_name']);
                $result['orderInfo']['receiver_mobile'] = data_masking('mobile', (string) $result['orderInfo']['receiver_mobile']);
                $result['orderInfo']['receiver_address'] = data_masking('address', (string) $result['orderInfo']['receiver_address']);
            }
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
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="时间筛选结束时间", type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", type="string"),
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
        // Ref: 1996368445
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        if ($request->get('activity_id')) {
            $filter['activity_id'] = $request->get('activity_id');
        }

        if ($request->input('time_start_begin')) {
            $timeStart = $request->input('time_start_begin');
            $timEnd    = $request->input('time_start_end');
            if (false !== strpos($timeStart, '-')) {
                $timeStart = strtotime($timeStart . ' 00:00:00');
                $timEnd    = strtotime($timEnd . ' 23:59:59');
            }
            $filter['created_at|gte'] = $timeStart;
            $filter['created_at|lte'] = $timEnd;
        }

        $activityStatus = $request->input('activity_status');
        if ($activityStatus) {
            switch ($activityStatus) {
                case "waiting":
                    $filter['start_time|gte'] = time();
                    $filter['end_time|gte']   = time();
                    break;
                case "ongoing":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|gte']   = time();
                    break;
                case "end":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|lte']   = time();
                    break;
            }
        }

        if ($request->input('is_success')) {
            $filter['activity_status'] = 'success';
        }

        if ($request->input('activity_name')) {
            $filter['activity_name|contains'] = $request->input('activity_name');
        }

        $companyId = app('auth')->user()->get('company_id');
        $operatorId = app('auth')->user()->get('operator_id');

        $gotoJob = (new ExportFileJob('normal_community_order', $companyId, $filter, $operatorId))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
